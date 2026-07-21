<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Device;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class SyncFromOkdesk extends Command
{
    protected $signature = 'sync:okdesk {--dry-run} {--limit=5000} {--start-id=0}';
    protected $description = 'Строгая синхронизация заявок из Okdesk (перебор по ID без ранней остановки)';

    private $apiToken;
    private $account;
    private $targetStatusCode;
    private $httpClient;

    public function handle()
    {
        $this->apiToken = config('services.okdesk.api_token');
        $this->account = config('services.okdesk.account');
        $this->targetStatusCode = config('services.okdesk.status_code', 'Equipment_transferred_repair_VSP');
        
        $dryRun = $this->option('dry-run');
        $limit = (int)$this->option('limit');
        $startId = (int)$this->option('start-id');

        $this->httpClient = Http::withOptions([
            'curl.options' => [
                CURLOPT_FORBID_REUSE => false,
                CURLOPT_FRESH_CONNECT => false,
            ],
        ])->timeout(15);

        $this->info('=== Строгая синхронизация заявок из Okdesk ===');
        $this->info("Аккаунт: {$this->account}");
        $this->info("Целевой статус: {$this->targetStatusCode}");
        $this->info("Будет проверено ровно: {$limit} заявок");
        
        if ($dryRun) {
            $this->warn("РЕЖИМ ПРОБНОГО ЗАПУСКА - данные не будут сохранены");
        }

        $created = 0;
        $updated = 0;
        $totalChecked = 0;
        $failedRequests = 0;

        try {
            if ($startId > 0) {
                $maxId = $startId;
                $this->info(" Начинаем с указанного ID: {$maxId}");
            } else {
                $this->info("\n🔍 Ищем максимальный ID заявки...");
                $lastIssue = $this->getLastIssue();
                if (!$lastIssue) {
                    $this->error("Не удалось получить последнюю заявку из Okdesk");
                    return;
                }
                $maxId = $lastIssue['id'];
                $this->info("✅ Максимальный ID: {$maxId}");
            }

            $this->info("\n🔄 Начинаем строгий перебор заявок (от {$maxId} вниз)...");

            // ИСПРАВЛЕНО: Убрана переменная $emptyStreak. 
            // Теперь цикл гарантированно проверит ровно $limit заявок, даже если подряд идут тысячи неподходящих.
            for ($id = $maxId; $id >= 1 && $totalChecked < $limit; $id--) {
                $startTime = microtime(true);
                
                $existingDevice = Device::where('issue_number', $id)->first();
                if ($existingDevice) {
                    $totalChecked++;
                    continue;
                }

                $issue = $this->getIssueByIdWithRetry($id);
                $requestTime = round(microtime(true) - $startTime, 2);
                
                if ($issue === false) {
                    $failedRequests++;
                    if ($failedRequests > 10) {
                        $this->error("Слишком много ошибок запросов. Останавливаемся.");
                        break;
                    }
                    continue;
                }
                
                if ($issue === null) {
                    $totalChecked++;
                    continue;
                }
                
                $failedRequests = 0;
                $totalChecked++;
                
                $statusCode = $issue['status']['code'] ?? '';
                
                if ($statusCode === $this->targetStatusCode) {
                    $this->info("\n✅ Заявка #{$id} — статус: {$issue['status']['name']} (за {$requestTime}с)");
                    
                    $description = strip_tags($issue['description'] ?? '');
                    $title = $issue['title'] ?? 'Без названия';
                    $createdAt = $issue['created_at'] ?? now();
                    $deviceNumber = $this->getDeviceNumber($issue);
                    
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
                }
                
                if ($totalChecked % 100 === 0) {
                    $this->info("   📊 Проверено: {$totalChecked}/{$limit} | Найдено: " . ($created + $updated) . " | Текущий ID: {$id}");
                }
                
                usleep(100000); // 0.1 секунды задержка
            }

            $this->info("\n=== Результаты ===");
            $this->info("Всего проверено заявок: {$totalChecked}");
            $this->info("Создано: {$created}");
            $this->info("Обновлено: {$updated}");

        } catch (\Exception $e) {
            $this->error("Ошибка: " . $e->getMessage());
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
                    return $response->json()[0] ?? null;
                }
            } catch (\Exception $e) {
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
                $response = $this->httpClient->get($url, ['api_token' => $this->apiToken]);
                
                if ($response->successful()) return $response->json();
                if ($response->status() === 404) return null;
            } catch (\Exception $e) {
                sleep($attempt);
            }
        }
        return false;
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
                $details = $this->fetchEquipmentDetails($equipmentId);
                if (!empty($details)) {
                    $number = $details['inventory_number'] ?? $details['serial_number'] ?? $details['name'] ?? null;
                    if ($number) return (string)$number;
                }
            }
        }
        return 'Не указано';
    }

    private function fetchEquipmentDetails(int $equipmentId): array
    {
        try {
            $response = $this->httpClient->get("https://{$this->account}.okdesk.ru/api/v1/equipments/{$equipmentId}", [
                'api_token' => $this->apiToken,
            ]);
            if ($response->successful()) return $response->json();
        } catch (\Exception $e) {}
        return [];
    }
}