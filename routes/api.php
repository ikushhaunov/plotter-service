<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Exports\QACheckExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Mail\QACheckReport;

Route::post('/trigger-qa-export', function (Request $request) {
    $expectedToken = config('services.export.token');
    
    if (!$expectedToken || $request->header('Authorization') !== 'Bearer ' . $expectedToken) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $filename = 'OTK_Check_' . date('Y-m-d') . '.xlsx';
    
    // Гарантируем создание папки
    Storage::makeDirectory('exports');
    
    // Генерируем Excel прямо в хранилище
    Excel::store(new QACheckExport, 'exports/' . $filename, 'local');
    
    $path = storage_path('app/exports/' . $filename);

    // Отправляем на email
    $email = config('services.qa_check.email');
    if ($email) {
        Mail::to($email)->send(new QACheckReport($path, $filename));
        return response()->json(['status' => 'success', 'message' => "Отчет успешно отправлен на {$email}"]);
    }

    return response()->json(['error' => 'Email не настроен'], 500);
});