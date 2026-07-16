<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Device;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class SyncFromOkdesk extends Command
{
    protected $signature = 'sync:okdesk {--dry-run} {--limit=5000}';
    protected $description = 'Синхронизация заявок из Okdesk (перебор по ID)';

    private $apiToken;
    private $account;
    private $targetStatusCode;
    private $httpClient;

    public function handle()
    {
        $this->apiToken = config('services.okdesk.api_token', env('OKDESK_API_TOKEN'));
$this->account = config('services.okdesk.account', env('OKDESK_ACCOUNT'));
$this->targetStatusCode = config('services.okdesk.status_code', env('OKDESK_STATUS_CODE', 'Equipment transferred repair VSP'));
        
        $dryRun = $this->option('dry-run');
        $limit = (int)$this->option('limit');

        // Создаём HTTP-клиент с keep-alive
        $this->httpClient = Http::withOptions([
            'curl.options' => [
                CURLOPT_FORBID_REUSE => false,
                CURLOPT_FRESH_CONNECT => false,
            ],
        ])->timeout(15);

        $this->info('=== Синхронизация заявок из Okdesk ===');
        $this->info("Аккаунт: {$this->account}");
        $this->info("Целевой статус: {$this->targetStatusCode}");
        $this->info("Лимит перебора: {$limit} заявок");
        
        if ($dryRun) {
            $this->warn("РЕЖИМ ПРОБНОГО ЗАПУСКА - данные не будут сохранены");
        }

        $created = 0;
        $updated = 0;
        $totalChecked = 0;
        $emptyStreak = 0;
        $failedRequests = 0;

        try {
            // Шаг 1: Находим максимальный ID
            $this->info("\n🔍 Ищем максимальный ID заявки...");
            $lastIssue = $this->getLastIssue();
            
            if (!$lastIssue) {
                $this->error("Не удалось получить последнюю заявку из Okdesk");
                return;
            }
            
            $maxId = $lastIssue['id'];
            $this->info("✅ Максимальный ID: {$maxId}");
            $this->info("\n🔄 Начинаем перебор всех заявок (от {$maxId} вниз)...");

            // Шаг 2: Перебираем заявки по ID
            for ($id = $maxId; $id >= 1 && $emptyStreak < $limit; $id--) {
                $startTime = microtime(true);
                
                // Проверяем, есть ли уже это устройство в базе
                $existingDevice = Device::where('issue_number', $id)->first();
                if ($existingDevice) {
                    // Устройство уже есть — пропускаем запрос к API
                    $emptyStreak++;
                    continue;
                }

                $issue = $this->getIssueByIdWithRetry($id);
                $requestTime = round(microtime(true) - $startTime, 2);
                
                if ($issue === false) {
                    // Ошибка запроса — пропускаем
                    $failedRequests++;
                    if ($failedRequests > 10) {
                        $this->error("Слишком много ошибок запросов. Останавливаемся.");
                        break;
                    }
                    continue;
                }
                
                if ($issue === null) {
                    // Заявки с таким ID нет
                    continue;
                }
                
                $failedRequests = 0; // Сбрасываем счётчик ошибок
                $totalChecked++;
                
                // ИСПРАВЛЕНО: Okdesk возвращает код статуса именно здесь
                $statusCode = $issue['status']['code'] ?? '';
                
                if ($statusCode === $this->targetStatusCode) {
                    $emptyStreak = 0;
                    
                    $this->info("\n✅ Заявка #{$id} — статус: {$issue['status']['name']} (за {$requestTime}с)");
                    
                    $description = strip_tags($issue['description'] ?? '');
                    $title = $issue['title'] ?? 'Без названия';
                    $createdAt = $issue['created_at'] ?? now();
                    
                    $this->info("   Название: {$title}");
                    $this->info("   Описание: " . mb_substr($description, 0, 80));
                    
                    $deviceNumber = $this->getDeviceNumber($issue);
                    $this->info("   Номер устройства: {$deviceNumber}");
                    
                    $device = Device::where('issue_number', $id)->first();
                    
                    if ($device) {
                        $this->info("   ✓ Уже существует (ID: {$device->id})");
                        if (!$dryRun) {
                            $device->update([
                                'device_number' => $deviceNumber,
                                'fault_description' => $description,
                                'updated_at' => now(),
                            ]);
                        }
                        $updated++;
                    } else {
                        if (!$dryRun) {
                            $device = Device::create([
                                'device_number' => $deviceNumber,
                                'issue_number' => $id,
                                'fault_description' => $description,
                                'status' => Device::STATUS_RECEIVED,
                                'received_date' => Carbon::parse($createdAt)->format('Y-m-d'),
                                'plotter_model_id' => null,
                            ]);
                            
                            $device->addHistory(
                                Device::STATUS_RECEIVED,
                                null,
                                "Импортировано из Okdesk (заявка #{$id})"
                            );
                        }
                        $created++;
                        $this->info("   ✓ Создано устройство");
                    }
                } else {
                    $emptyStreak++;
                }
                
                // Прогресс каждые 50 заявок
                if ($totalChecked % 50 === 0) {
                    $this->info("   📊 Проверено: {$totalChecked} | Найдено: " . ($created + $updated) . " | Текущий ID: {$id} | Среднее время: {$requestTime}с");
                }
                
                // Задержка между запросами
                usleep(100000); // 0.1 секунды
            }

            $this->info("\n=== Результаты ===");
            $this->info("Всего проверено заявок: {$totalChecked}");
            $this->info("Создано: {$created}");
            $this->info("Обновлено: {$updated}");

        } catch (\Exception $e) {
            $this->error("Ошибка: " . $e->getMessage());
            $this->error($e->getTraceAsString());
        }
    }

    private function getLastIssue(): ?array
    {
        for ($attempt = 1; $attempt <= 2; $attempt++) {
            try {
                $url = "https://{$this->account}.okdesk.ru/api/v1/issues/list";
                $response = $this->httpClient->get($url, [
                    'api_token' => $this->apiToken,
                    'limit' => 1,
                ]);
                
                if ($response->successful()) {
                    $issues = $response->json();
                    return $issues[0] ?? null;
                }
            } catch (\Exception $e) {
                $this->warn("Попытка {$attempt} не удалась: " . $e->getMessage());
                sleep(2);
            }
        }
        return null;
    }

    private function getIssueByIdWithRetry(int $id)
    {
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            try {
                $url = "https://{$this->account}.okdesk.ru/api/v1/issues/{$id}";
                $response = $this->httpClient->get($url, [
                    'api_token' => $this->apiToken,
                ]);
                
                if ($response->successful()) {
                    return $response->json();
                }
                
                if ($response->status() === 404) {
                    return null; // Заявки нет
                }
                
                // Другие ошибки — пробуем ещё раз
                $this->warn("   ⚠️ Заявка #{$id}: HTTP {$response->status()} (попытка {$attempt})");
                
            } catch (\Exception $e) {
                $this->warn("   ⚠️ Заявка #{$id}: " . $e->getMessage() . " (попытка {$attempt})");
            }
            
            // Увеличиваем задержку между попытками
            sleep($attempt);
        }
        
        return false; // Все попытки не удались
    }

    private function getDeviceNumber(array $issueDetails): string
    {
        if (!empty($issueDetails['equipments']) && is_array($issueDetails['equipments'])) {
            foreach ($issueDetails['equipments'] as $equipment) {
                $number = $equipment['inventory_number'] ?? $equipment['serial_number'] ?? $equipment['name'] ?? null;
                if ($number) return (string)$number;
            }
        }

        if (!empty($issueDetails['equipment_ids'])) {
            foreach ($issueDetails['equipment_ids'] as $equipmentId) {
                $equipmentDetails = $this->fetchEquipmentDetails($equipmentId);
                if (!empty($equipmentDetails)) {
                    $number = $equipmentDetails['inventory_number'] 
                        ?? $equipmentDetails['serial_number'] 
                        ?? $equipmentDetails['name'] 
                        ?? null;
                    if ($number) return (string)$number;
                }
            }
        }

        return 'Не указано';
    }

    private function fetchEquipmentDetails(int $equipmentId): array
    {
        try {
            $url = "https://{$this->account}.okdesk.ru/api/v1/equipments/{$equipmentId}";
            $response = $this->httpClient->get($url, [
                'api_token' => $this->apiToken,
            ]);
            
            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            // ignore
        }
        return [];
    }
}