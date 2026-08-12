<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Device;
use Carbon\Carbon;

class SyncOkdeskController extends Controller
{
    public function syncByStatus()
    {
        $apiToken = config('services.okdesk.api_token');
        $account = config('services.okdesk.account');
        $statusCode = config('services.okdesk.status_code', 'Equipment_transferred_repair_VSP');
        $dateFrom = now()->subDays(7)->format('Y-m-d');
        
        try {
            $response = Http::get("https://{$account}.okdesk.ru/api/v1/issues/list", [
                'api_token' => $apiToken,
                'created_at[from]' => $dateFrom,
                'limit' => 5000, 
            ]);
            
            if (!$response->successful()) {
                return response()->json(['error' => 'Okdesk API error', 'status' => $response->status()], 500);
            }
            
            $allIssues = $response->json();
            $created = 0; 
            $updated = 0; 
            $skipped = 0;
            
            foreach ($allIssues as $issue) {
                if (($issue['status']['code'] ?? '') !== $statusCode) { 
                    $skipped++; 
                    continue; 
                }

                $id = (string) $issue['id'];
                $deviceNumber = $this->getDeviceNumberFromIssue($issue);
                $description = strip_tags($issue['description'] ?? 'Описание отсутствует');
                $createdAt = $issue['created_at'] ?? now();
                
                $device = Device::where('issue_number', $id)->first();
                
                if ($device) {
                    $device->update(['device_number' => $deviceNumber, 'fault_description' => $description, 'updated_at' => now()]);
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
                'message' => 'Синхронизация завершена', 
                'period' => "За 7 дней (с {$dateFrom})",
                'total_fetched' => count($allIssues), 
                'skipped' => $skipped, 
                'created' => $created, 
                'updated' => $updated,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Sync failed', 'message' => $e->getMessage()], 500);
        }
    }

    private function getDeviceNumberFromIssue(array $issue): string {
        if (!empty($issue['equipments']) && is_array($issue['equipments'])) {
            foreach ($issue['equipments'] as $equipment) {
                $number = $equipment['inventory_number'] ?? $equipment['serial_number'] ?? $equipment['name'] ?? null;
                if ($number) return (string)$number;
            }
        }
        return 'Не указано';
    }

    public function syncTest(Request $request) {
        return response()->json(['status' => 'success', 'message' => 'OK']);
    }
}