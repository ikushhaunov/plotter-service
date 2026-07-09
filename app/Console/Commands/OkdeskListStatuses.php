<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class OkdeskListStatuses extends Command
{
    protected $signature = 'okdesk:show-statuses';
    protected $description = 'Показать все статусы заявок из Okdesk';

    public function handle()
    {
        $apiToken = config('services.okdesk.api_token');
        $account = config('services.okdesk.account');
        
        $this->info('=== Получение списка статусов ===');
        
        // Пробуем разные URL для получения статусов
        $urls = [
            "https://{$account}.okdesk.ru/api/v1/statuses/list",
            "https://{$account}.okdesk.ru/api/v1/issues/status/list",
            "https://{$account}.okdesk.ru/api/v1/statuses",
        ];
        
        foreach ($urls as $url) {
            $this->info("\nПытаюсь: {$url}");
            
            try {
                $response = Http::timeout(15)->get($url, [
                    'api_token' => $apiToken,
                ]);
                
                if ($response->successful()) {
                    $body = $response->body();
                    
                    if (str_starts_with(trim($body), '<!DOCTYPE') || str_starts_with(trim($body), '<html')) {
                        $this->error("❌ Получена HTML-страница");
                        continue;
                    }
                    
                    $data = $response->json();
                    $this->info("✅ Успех! Получено элементов: " . count($data));
                    
                    foreach ($data as $status) {
                        $code = $status['code'] ?? 'N/A';
                        $name = $status['name'] ?? 'N/A';
                        $id = $status['id'] ?? 'N/A';
                        $this->line("  ID: {$id} | Код: <comment>{$code}</comment> | Название: {$name}");
                    }
                    
                    $this->info("\nНайдите статус, связанный с 'Переведено на производство' или 'Производство'");
                    $this->info("Скопируйте его код и вставьте в .env как OKDESK_STATUS_CODE");
                    return;
                } else {
                    $this->error("❌ Ошибка HTTP: " . $response->status());
                }
                
            } catch (\Exception $e) {
                $this->error("❌ Исключение: " . $e->getMessage());
            }
        }
        
        $this->error("\nНе удалось получить список статусов");
    }
}