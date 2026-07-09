<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class OkdeskGetIssueDetails extends Command
{
    protected $signature = 'okdesk:get-issue {issueId}';
    protected $description = 'Получить детали конкретной заявки из Okdesk';

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
                $this->info("Описание: " . ($issue['description'] ?? 'Пусто'));
                $this->info("Статус: " . $issue['status']['name'] . " (код: " . $issue['status']['code'] . ")");
                
                $this->info("\nОборудование:");
                if (!empty($issue['equipments'])) {
                    foreach ($issue['equipments'] as $index => $equipment) {
                        $this->info("--- Оборудование #" . ($index + 1) . " ---");
                        $this->info("ID: " . $equipment['id']);
                        $this->info("Название: " . ($equipment['name'] ?? 'N/A'));
                        $this->info("Серийный номер: " . ($equipment['serial_number'] ?? 'N/A'));
                        $this->info("Инвентарный номер: " . ($equipment['inventory_number'] ?? 'N/A'));
                        
                        if (!empty($equipment['equipment_model'])) {
                            $this->info("Модель: " . $equipment['equipment_model']['name']);
                        }
                        
                        if (!empty($equipment['equipment_kind'])) {
                            $this->info("Тип: " . $equipment['equipment_kind']['name']);
                        }
                        
                        $this->info("Все поля: " . json_encode($equipment, JSON_UNESCAPED_UNICODE));
                    }
                } else {
                    $this->warn("Оборудование не указано");
                }
                
            } else {
                $this->error("Ошибка HTTP: " . $response->status());
            }
            
        } catch (\Exception $e) {
            $this->error("Исключение: " . $e->getMessage());
        }
    }
}