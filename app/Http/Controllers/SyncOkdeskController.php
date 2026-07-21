<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Device;
use Carbon\Carbon;

class SyncOkdeskController extends Controller
{
    // Старый метод (для совместимости с GitHub Actions)
    public function sync(Request $request)
    {
        $limit = $request->get('limit', 50);
        $startId = $request->get('start-id', 0);
        
        $command = "php artisan sync:okdesk --limit={$limit}";
        if ($startId > 0) {
            $command .= " --start-id={$startId}";
        }
        
        $output = shell_exec($command . " 2>&1");
        
        return response()->json([
            'status' => 'success',
            'message' => 'Синхронизация завершена',
            'output' => $output
        ]);
    }

    // ✨ НОВЫЙ метод: Мгновенная синхронизация по статусу (без перебора!)
    public function syncByStatus()
    {
        $apiToken = config('services.okdesk.api_token');
        $account = config('services.okdesk.account');
        $statusCode = config('services.okdesk.status_code', 'Equipment_transferred_repair_VSP');
        
        try {
            // Запрашиваем заявки, отфильтрованные ПО СТАТУСУ через API Okdesk
            $response = Http::get("https://{$account}.okdesk.ru/api/v1/issues/list", [
                'api_token' => $apiToken,
                'status_code' => $statusCode, 
                'limit' => 1000, // Получаем до 1000 заявок за один раз
            ]);
            
            if (!$response->successful()) {
                return response()->json([
                    'error' => 'Okdesk API error', 
                    'status' => $response->status(),
                    'response' => $response->body()
                ], 500);
            }
            
            $issues = $response->json();
            $created = 0;
            $updated = 0;
            
            foreach ($issues as $issue) {
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
}