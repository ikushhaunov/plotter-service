<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SyncOkdeskController extends Controller
{
    public function syncByStatus()
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Синхронизация временно отключена для стабилизации сайта.'
        ]);
    }

    public function syncTest(Request $request)
    {
        return response()->json(['status' => 'success', 'message' => 'OK']);
    }
}