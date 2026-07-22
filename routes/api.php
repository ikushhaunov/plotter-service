<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Exports\QACheckExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Mail\QACheckReport;

Route::post('/trigger-qa-export', function (Request $request) {
    // Защита через токен из переменных окружения
    $expectedToken = config('services.export.token');
    
    if (!$expectedToken || $request->header('Authorization') !== 'Bearer ' . $expectedToken) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $filename = 'OTK_Check_' . date('Y-m-d') . '.xlsx';
    $path = storage_path('app/exports/' . $filename);

    // Создаем директорию, если её нет
    if (!file_exists(dirname($path))) {
        mkdir(dirname($path), 0755, true);
    }

    // Генерируем Excel
    Excel::store(new QACheckExport, 'exports/' . $filename, 'local');

    // Отправляем на email
    $email = config('services.qa_check.email');
    if ($email) {
        Mail::to($email)->send(new QACheckReport($path, $filename));
        return response()->json(['status' => 'success', 'message' => "Отчет успешно отправлен на {$email}"]);
    }

    return response()->json(['error' => 'Email не настроен в переменных окружения'], 500);
});