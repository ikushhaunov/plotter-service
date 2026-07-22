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
     * Быстрая синхронизация по статусу (если Okdesk API поддерживает фильтрацию)
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
     * Тестовый синхронный запуск через Artisan::call()
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
}