<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Device;
use Carbon\Carbon;

class SyncOkdeskController extends Controller
{
    /**
     * Запуск фоновой синхронизации через браузер
     * Используется для массового импорта без таймаутов
     */
    public function sync(Request $request)
    {
        $limit = $request->get('limit', 50);
        $startId = $request->get('start-id', 0);
        
        // Используем абсолютные пути, чтобы PHP точно нашел artisan
        $artisanPath = base_path('artisan');
        $phpBinary = PHP_BINARY ?: '/usr/bin/php';
        
        // Формируем команду для запуска в фоне (Linux/Render)
        $command = sprintf(
            '"%s" "%s" sync:okdesk --limit=%d --start-id=%d > /dev/null 2>&1 &', 
            $phpBinary, 
            $artisanPath, 
            (int)$limit, 
            (int)$startId
        );
        
        // Запускаем команду в фоне
        exec($command);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Фоновая синхронизация успешно запущена!',
            'info' => "Сервер проверяет {$limit} заявок, начиная с ID {$startId}. Подождите 5-10 минут и обновите страницу устройств."
        ]);
    }

    /**
     * Быстрая синхронизация по статусу (попытка фильтрации на стороне API)
     * Примечание: Okdesk API может игнорировать параметр status_code
     */
    public function syncByStatus()
    {
        $apiToken = config('services.okdesk.api_token');
        $account = config('services.okdesk.account');
        $statusCode = config('services.okdesk.status_code', 'Equipment_transferred_repair_VSP');
        
        try {
            $response = Http::get("https://{$account}.okdesk.ru/api/v1/issues/list", [
                'api_token' => $apiToken,
                'status_code' => $statusCode, 
                'limit' => 1000,
            ]);
            
            if (!$response->successful()) {
                return response()->json([
                    'error' => 'Okdesk API error', 
                    'status' => $response->status()
                ], 500);
            }
            
            $issues = $response->json();
            $created = 0;
            $updated = 0;
            
            foreach ($issues as $issue) {
                // Дополнительная проверка на стороне PHP
                if (($issue['status']['code'] ?? '') !== $statusCode) {
                    continue; 
                }

                $id = $issue['id'];
                $deviceNumber = $this->getDeviceNumberFromIssue($issue);
                $description = strip_tags($issue['description'] ?? '');
                $createdAt = $issue['created_at'] ?? now();
                
                $device = Device::where('issue_number', $id)->first();
                
                if ($device) {
                    $device->update([
                        'device_number' => $deviceNumber,
                        'fault_description' => $description,
                        'updated_at' => now(),
                    ]);
                    $updated++;
                } else {
                    Device::create([
                        'device_number' => $deviceNumber,
                        'issue_number' => $id,
                        'fault_description' => $description,
                        'status' => Device::STATUS_RECEIVED,
                        'received_date' => Carbon::parse($createdAt)->format('Y-m-d'),
                        'plotter_model_id' => null,
                    ]);
                    $created++;
                }
            }
            
            return response()->json([
                'status' => 'success',
                'total_found' => count($issues),
                'created' => $created,
                'updated' => $updated,
            ]);
            
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Диагностический метод для проверки фильтрации Okdesk API
     */
    public function debugStatus()
    {
        $apiToken = config('services.okdesk.api_token');
        $account = config('services.okdesk.account');
        $statusCode = config('services.okdesk.status_code', 'Equipment_transferred_repair_VSP');
        
        try {
            // Запрос БЕЗ фильтрации
            $responseNoFilter = Http::get("https://{$account}.okdesk.ru/api/v1/issues/list", [
                'api_token' => $apiToken,
                'limit' => 5,
            ]);
            
            // Запрос с фильтрацией по status_code
            $responseWithFilter = Http::get("https://{$account}.okdesk.ru/api/v1/issues/list", [
                'api_token' => $apiToken,
                'status_code' => $statusCode,
                'limit' => 5,
            ]);
            
            // Запрос с фильтрацией по status_id
            $responseWithStatusId = Http::get("https://{$account}.okdesk.ru/api/v1/issues/list", [
                'api_token' => $apiToken,
                'status_id' => $statusCode,
                'limit' => 5,
            ]);
            
            return response()->json([
                'target_status' => $statusCode,
                'without_filter' => $responseNoFilter->successful() ? $responseNoFilter->json() : $responseNoFilter->body(),
                'with_status_code_filter' => $responseWithFilter->successful() ? $responseWithFilter->json() : $responseWithFilter->body(),
                'with_status_id_filter' => $responseWithStatusId->successful() ? $responseWithStatusId->json() : $responseWithStatusId->body(),
            ]);
            
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Вспомогательный метод для получения номера устройства из заявки
     */
    private function getDeviceNumberFromIssue(array $issue): string
    {
        if (!empty($issue['equipments']) && is_array($issue['equipments'])) {
            foreach ($issue['equipments'] as $equipment) {
                $number = $equipment['inventory_number'] ?? $equipment['serial_number'] ?? $equipment['name'] ?? null;
                if ($number) return (string)$number;
            }
        }
        
        if (!empty($issue['equipment_ids'])) {
            foreach ($issue['equipment_ids'] as $equipmentId) {
                return "Equipment #{$equipmentId}";
            }
        }
        
        return 'Не указано';
    }
/**
 * Тестовый синхронный запуск (без фона)
 */
public function syncTest(Request $request)
{
    $limit = $request->get('limit', 5);
    $startId = $request->get('start-id', 0);
    
    try {
        $artisanPath = base_path('artisan');
        $phpBinary = PHP_BINARY ?: '/usr/bin/php';
        
        // Запускаем СИНХРОННО (ждем завершения)
        $command = sprintf(
            '"%s" "%s" sync:okdesk --limit=%d --start-id=%d 2>&1', 
            $phpBinary, 
            $artisanPath, 
            (int)$limit, 
            (int)$startId
        );
        
        $output = shell_exec($command);
        
        return response()->json([
            'status' => 'success',
            'output' => $output
        ]);
        
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

}