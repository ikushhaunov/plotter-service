<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Artisan;
use App\Models\Device;
use Carbon\Carbon;

class SyncOkdeskController extends Controller
{
    public function syncByStatus()
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Синхронизация временно приостановлена для настройки. Сайт работает.'
        ]);
    }

    public function syncTest(Request $request)
    {
        return response()->json(['status' => 'success', 'message' => 'Test endpoint works']);
    }
}