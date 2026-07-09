<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class OkdeskListAllStatuses extends Command
{
    protected $signature = 'okdesk:list-all-statuses';
    protected $description = 'Показать все уникальные статусы из существующих заявок';

    public function handle()
    {
        $apiToken = config('services.okdesk.api_token');
        $account = config('services.okdesk.account');
        
        $this->info('=== Получение всех уникальных статусов из заявок ===');
        
        $url = "https://{$account}.okdesk.ru/api/v1/issues/list";
        
        try {
            // Увеличиваем лимит до 500 заявок
            $response = Http::timeout(30)->get($url, [
                'api_token' => $apiToken,
                'limit' => 500,
            ]);
            
            if (!$response->successful()) {
                $this->error("Ошибка HTTP: " . $response->status());
                return;
            }
            
            $issues = $response->json();
            $this->info("Получено заявок: " . count($issues));
            
            // Собираем уникальные статусы
            $statuses = [];
            foreach ($issues as $issue) {
                if (isset($issue['status'])) {
                    $code = $issue['status']['code'];
                    $name = $issue['status']['name'];
                    if (!isset($statuses[$code])) {
                        $statuses[$code] = [
                            'name' => $name,
                            'count' => 0,
                        ];
                    }
                    $statuses[$code]['count']++;
                }
            }
            
            $this->info("\n=== Найденные статусы ===");
            $this->info(str_repeat('-', 80));
            
            foreach ($statuses as $code => $info) {
                $this->line(sprintf(
                    "Код: <comment>%s</comment> | Название: %s | Кол-во заявок: %d",
                    $code,
                    $info['name'],
                    $info['count']
                ));
            }
            
            $this->info(str_repeat('-', 80));
            $this->info("\nНайдите статус 'ВСП Оборудование передано в ремонт'");
            $this->info("Скопируйте его код и вставьте в .env как OKDESK_STATUS_CODE");
            
        } catch (\Exception $e) {
            $this->error("Исключение: " . $e->getMessage());
        }
    }
}