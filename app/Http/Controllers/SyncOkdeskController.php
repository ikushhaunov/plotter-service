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
        
        // Запрос С фильтрацией по status_code
        $responseWithFilter = Http::get("https://{$account}.okdesk.ru/api/v1/issues/list", [
            'api_token' => $apiToken,
            'status_code' => $statusCode,
            'limit' => 5,
        ]);
        
        // Запрос с фильтрацией по status_id (возможно, нужен ID вместо кода)
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