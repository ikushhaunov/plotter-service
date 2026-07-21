<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Artisan;
use App\Models\Device;
use Carbon\Carbon;

class SyncOkdeskController extends Controller
{
    /**
     * Фоновая синхронизация (на случай, если shell_exec заработает)
     */
    public function sync(Request $request)
    {
        $limit = $request->get('limit', 50);
        $startId = $request->get('start-id', 0);
        
        $artisanPath = base_path('artisan');
        $phpBinary = 'php'; 
        
        $command = sprintf(
            '%s %s sync:okdesk --limit=%d --start-id=%d > /dev/null 2>&1 &', 
            $phpBinary, 
            $artisanPath, 
            (int)$limit, 
            (int)$startId
        );
        
        exec($command);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Фоновая синхронизация запущена',
            'info' => "Проверяется {$limit} заявок от ID {$startId}"
        ]);
    }

    /**
     * Быстрая синхронизация по статусу
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
                return response()->json(['error' => 'Okdesk API error', 'status' => $response->status()], 500);
            }
            
            $issues = $response->json();
            $created = 0;
            $updated = 0;
            
            foreach ($issues as $issue) {
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
            $responseNoFilter = Http::get("https://{$account}.okdesk.ru/api/v1/issues/list", [
                'api_token' => $apiToken,
                'limit' => 5,
            ]);
            
            $responseWithFilter = Http::get("https://{$account}.okdesk.ru/api/v1/issues/list", [
                'api_token' => $apiToken,
                'status_code' => $statusCode,
                'limit' => 5,
            ]);
            
            return response()->json([
                'target_status' => $statusCode,
                'without_filter' => $responseNoFilter->successful() ? $responseNoFilter->json() : $responseNoFilter->body(),
                'with_status_code_filter' => $responseWithFilter->successful() ? $responseWithFilter->json() : $responseWithFilter->body(),
            ]);
            
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Тестовый синхронный запуск через Artisan::call() (ЕДИНСТВЕННЫЙ ЭКЗЕМПЛЯР)
     */
    public function syncTest(Request $request)
    {
        $limit = $request->get('limit', 20);
        $startId = $request->get('start-id', 0);
        
        try {
            $exitCode = Artisan::call('sync:okdesk', [
                '--limit' => (int)$limit,
                '--start-id' => (int)$startId,
            ]);
            
            $output = Artisan::output();
            
            return response()->json([
                'status' => 'success',
                'exit_code' => $exitCode,
                'output' => $output
            ]);
            
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Вспомогательный метод для получения номера устройства
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
     * Точечная проверка заявки 455988
     */
    public function debugSingleTicket()
    {
        $id = 455988;
        $targetStatus = config('services.okdesk.status_code', 'Equipment_transferred_repair_VSP');
        
        // 1. Проверяем, есть ли уже в базе
        $inDb = \App\Models\Device::where('issue_number', $id)->exists();
        
        // 2. Делаем запрос к API точно так же, как это делает скрипт
        $apiToken = config('services.okdesk.api_token');
        $account = config('services.okdesk.account');
        
        $response = Http::get("https://{$account}.okdesk.ru/api/v1/issues/{$id}", [
            'api_token' => $apiToken
        ]);
        
        $issue = $response->successful() ? $response->json() : null;
        $statusCode = $issue['status']['code'] ?? 'NOT_FOUND';
        $matches = ($statusCode === $targetStatus);
        
        return response()->json([
            'ticket_id' => $id,
            'already_in_database' => $inDb,
            'api_status_code' => $statusCode,
            'target_status_we_want' => $targetStatus,
            'status_matches' => $matches,
            'api_http_status' => $response->status(),
            'issue_brief' => $issue ? [
                'title' => $issue['title'], 
                'status_name' => $issue['status']['name']
            ] : null
        ]);
    }
}