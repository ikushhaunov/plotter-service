<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class SyncOkdeskController extends Controller
{
    public function sync(Request $request)
    {
        $limit = $request->input('limit', 10);
        
        try {
            // Запускаем artisan команду
            Artisan::call('sync:okdesk', ['--limit' => $limit]);
            $output = Artisan::output();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Синхронизация завершена',
                'output' => $output
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}