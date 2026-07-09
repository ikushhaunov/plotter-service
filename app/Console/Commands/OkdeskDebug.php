<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class OkdeskDebug extends Command
{
    protected $signature = 'okdesk:debug';
    protected $description = 'Отладка подключения к Okdesk API';

    public function handle()
    {
        $this->info('=== Поиск правильного URL для заявок ===');
        
        $apiToken = config('services.okdesk.api_token');
        $account = config('services.okdesk.account');
        
        $this->info("API Token: " . substr($apiToken, 0, 10) . '...');
        $this->info("Account: " . $account);
        
        $tests = [
            [
                'name' => 'Список заявок (list)',
                'url' => "https://{$account}.okdesk.ru/api/v1/issues/list",
                'params' => ['api_token' => $apiToken, 'limit' => 2],
            ],
            [
                'name' => 'Список заявок (get)',
                'url' => "https://{$account}.okdesk.ru/api/v1/issues/get",
                'params' => ['api_token' => $apiToken, 'limit' => 2],
            ],
            [
                'name' => 'Список заявок (all)',
                'url' => "https://{$account}.okdesk.ru/api/v1/issues/all",
                'params' => ['api_token' => $apiToken, 'limit' => 2],
            ],
            [
                'name' => 'Список заявок (search)',
                'url' => "https://{$account}.okdesk.ru/api/v1/issues/search",
                'params' => ['api_token' => $apiToken, 'limit' => 2],
            ],
            [
                'name' => 'Список заявок (index)',
                'url' => "https://{$account}.okdesk.ru/api/v1/issues/index",
                'params' => ['api_token' => $apiToken, 'limit' => 2],
            ],
            [
                'name' => 'Заявки (без v1, list)',
                'url' => "https://{$account}.okdesk.ru/api/issues/list",
                'params' => ['api_token' => $apiToken, 'limit' => 2],
            ],
            [
                'name' => 'Список статусов',
                'url' => "https://{$account}.okdesk.ru/api/v1/issues/status/list",
                'params' => ['api_token' => $apiToken],
            ],
            [
                'name' => 'Список статусов (вариант 2)',
                'url' => "https://{$account}.okdesk.ru/api/v1/statuses/list",
                'params' => ['api_token' => $apiToken],
            ],
        ];
        
        foreach ($tests as $i => $test) {
            $this->info("\n--- Тест " . ($i + 1) . ": " . $test['name'] . " ---");
            $this->info("URL: {$test['url']}");
            
            try {
                $response = Http::timeout(15)->get($test['url'], $test['params']);
                
                $this->info("HTTP Status: " . $response->status());
                
                $body = $response->body();
                
                if (str_starts_with(trim($body), '<!DOCTYPE') || str_starts_with(trim($body), '<html')) {
                    $this->error("❌ Получена HTML-страница");
                    continue;
                }
                
                if ($response->successful()) {
                    $this->info("✅ УСПЕХ! JSON-ответ получен!");
                    $this->line("Ответ (первые 800 символов):");
                    $this->line(substr($body, 0, 800));
                    
                    $data = $response->json();
                    if (is_array($data)) {
                        $this->info("\nСтруктура ответа:");
                        $this->info("- Тип: " . gettype($data));
                        $this->info("- Количество элементов: " . count($data));
                        if (!empty($data)) {
                            $first = is_array($data) ? reset($data) : null;
                            if (is_array($first)) {
                                $this->info("- Ключи первого элемента: " . implode(', ', array_keys($first)));
                            }
                        }
                    }
                    
                    if (str_contains($test['url'], 'issues')) {
                        $this->info("\n=== Это похоже на список заявок! ===");
                        return;
                    }
                } else {
                    $this->error("❌ Ошибка HTTP: " . $response->status());
                    $this->line("Ответ: " . substr($body, 0, 200));
                }
                
            } catch (\Exception $e) {
                $this->error("❌ Исключение: " . $e->getMessage());
            }
        }
        
        $this->info("\n=== Не удалось найти правильный URL для заявок ===");
    }
}