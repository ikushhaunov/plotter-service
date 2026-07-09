<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class OkdeskShowEquipment extends Command
{
    protected $signature = 'okdesk:show-equipment {equipmentId}';
    protected $description = 'Показать детали оборудования из Okdesk';

    public function handle()
    {
        $equipmentId = $this->argument('equipmentId');
        $apiToken = config('services.okdesk.api_token');
        $account = config('services.okdesk.account');
        
        $this->info("=== Детали оборудования #{$equipmentId} ===");
        
        // Пробуем разные URL
        $urls = [
            "https://{$account}.okdesk.ru/api/v1/equipments/{$equipmentId}",
            "https://{$account}.okdesk.ru/api/v1/equipment/{$equipmentId}",
            "https://{$account}.okdesk.ru/api/v1/equipments/{$equipmentId}/view",
            "https://{$account}.okdesk.ru/api/v1/equipment/{$equipmentId}/view",
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
                    
                    $equipment = $response->json();
                    $this->info("✅ Успех!");
                    
                    $this->info("\nВсе поля оборудования:");
                    foreach ($equipment as $key => $value) {
                        if (is_array($value)) {
                            $this->info("  {$key}: " . json_encode($value, JSON_UNESCAPED_UNICODE));
                        } elseif (is_null($value)) {
                            $this->info("  {$key}: null");
                        } else {
                            $this->info("  {$key}: {$value}");
                        }
                    }
                    
                    $this->info("\nПолный JSON:");
                    $this->line(json_encode($equipment, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                    return;
                } else {
                    $this->error("❌ Ошибка HTTP: " . $response->status());
                }
                
            } catch (\Exception $e) {
                $this->error("❌ Исключение: " . $e->getMessage());
            }
        }
        
        $this->error("\nНе удалось получить детали оборудования");
    }
}