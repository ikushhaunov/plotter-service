<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TestOkdeskController extends Controller
{
    public function checkTicket($ticketId)
    {
        $token = env('OKDESK_API_TOKEN');
        $account = env('OKDESK_ACCOUNT');
        $targetStatus = env('OKDESK_STATUS_CODE');
        
        try {
            // Получаем заявку из Okdesk
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->get("https://{$account}.okdesk.ru/api/v1/tickets/{$ticketId}");
            
            if ($response->successful()) {
                $ticket = $response->json();
                
                return response()->json([
                    'ticket_id' => $ticketId,
                    'ticket_data' => $ticket,
                    'target_status' => $targetStatus,
                    'ticket_status' => $ticket['status'] ?? 'NOT_FOUND',
                    'status_matches' => ($ticket['status'] ?? '') === $targetStatus,
                    'status_type' => gettype($ticket['status'] ?? null),
                ]);
            } else {
                return response()->json(['error' => 'API error', 'code' => $response->status()], 500);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}