<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class OkdeskShowIssue extends Command
{
    protected $signature = 'okdesk:show-issue {issueId}';
    protected $description = 'Показать детали конкретной заявки из Okdesk';

    public function handle()
    {
        $issueId = $this->argument('issueId');
        $apiToken = config('services.okdesk.api_token');
        $account = config('services.okdesk.account');
        
        $this->info("=== Детали заявки #{$issueId} ===");
        
        $url = "https://{$account}.okdesk.ru/api/v1/issues/{$issueId}";
        
        try {
            $response = Http::timeout(15)->get($url, [
                'api_token' => $apiToken,
            ]);
            
            if ($response->successful()) {
                $issue = $response->json();
                
                $this->info("\nОсновная информация:");
                $this->info("ID: " . $issue['id']);
                $this->info("Название: " . $issue['title']);
                $this->info("Создана: " . $issue['created_at']);
                $this->info("Статус: " . $issue['status']['name'] . " (код: " . $issue['status']['code'] . ")");
                $this->info("Тип: " . ($issue['type']['name'] ?? 'N/A'));
                $this->info("Приоритет: " . ($issue['priority']['name'] ?? 'N/A'));
                $this->info("Компания: " . ($issue['company']['name'] ?? 'N/A'));
                $this->info("Объект обслуживания: " . ($issue['service_object']['name'] ?? 'N/A'));
                
                $this->info("\nОборудование:");
                if (!empty($issue['equipments'])) {
                    foreach ($issue['equipments'] as $index => $equipment) {
                        $this->info("--- Оборудование #" . ($index + 1) . " ---");
                        $this->info("ID: " . $equipment['id']);
                        $this->info("Все ключи: " . implode(', ', array_keys($equipment)));
                        
                        // Показываем все поля оборудования
                        foreach ($equipment as $key => $value) {
                            if (is_array($value)) {
                                $this->info("  {$key}: " . json_encode($value, JSON_UNESCAPED_UNICODE));
                            } else {
                                $this->info("  {$key}: " . $value);
                            }
                        }
                    }
                } else {
                    $this->warn("Оборудование не указано");
                }
                
                $this->info("\nПолный JSON ответ:");
                $this->line(json_encode($issue, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                
            } else {
                $this->error("Ошибка HTTP: " . $response->status());
                $this->line($response->body());
            }
            
        } catch (\Exception $e) {
            $this->error("Исключение: " . $e->getMessage());
        }
    }
}