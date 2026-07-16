<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;

class TestOkdeskController extends Controller
{
    public function debug()
    {
        $token = config('services.okdesk.api_token');
        $account = config('services.okdesk.account');
        
        // ВАЖНО: Здесь должны быть ПРОБЕЛЫ, как вы указали
        $targetStatus = config('services.okdesk.status_code', 'Equipment transferred repair VSP');

        if (empty($token) || empty($account)) {
            return response()->json(['ERROR' => 'Переменные OKDESK_API_TOKEN или OKDESK_ACCOUNT пустые!'], 500);
        }

        // ИСПРАВЛЕНО: Передаем api_token как параметр запроса (правильный способ для Okdesk)
        $response = Http::get("https://{$account}.okdesk.ru/api/v1/issues/455988", [
            'api_token' => $token
        ]);

        return response()->json([
            'account_used' => $account,
            'target_status_we_are_looking_for' => $targetStatus,
            'okdesk_http_status' => $response->status(),
            'okdesk_response' => $response->successful() ? $response->json() : $response->body()
        ]);
    }
}