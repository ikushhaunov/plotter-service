<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;

class TestOkdeskController extends Controller
{
    public function debug()
    {
        // Читаем ТОЛЬКО из config (это работает в production на 100%)
        $token = config('services.okdesk.api_token');
        $account = config('services.okdesk.account');
        $targetStatus = config('services.okdesk.status_code', 'Equipment transferred repair VSP');

        // Проверка: пустые ли переменные?
        if (empty($token) || empty($account)) {
            return response()->json([
                'ERROR' => 'Переменные OKDESK_API_TOKEN или OKDESK_ACCOUNT ПУСТЫЕ в конфиге!',
                'ACTION' => 'Проверьте Environment Variables в Render. Убедитесь, что нет пробелов в начале или конце.'
            ], 500);
        }

        // Пробуем запросить конкретную заявку 455988
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->get("https://{$account}.okdesk.ru/api/v1/issues/455988");

        return response()->json([
            'account_used' => $account,
            'token_starts_with' => substr($token, 0, 6) . '...',
            'target_status_we_are_looking_for' => $targetStatus,
            'okdesk_http_status' => $response->status(),
            'okdesk_response' => $response->successful() ? $response->json() : $response->body()
        ]);
    }
}