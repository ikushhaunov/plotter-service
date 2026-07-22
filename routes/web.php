<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\EmployeeStatsController;
use App\Http\Controllers\SyncOkdeskController;
use App\Http\Controllers\AnalyticsController;

Route::get('/', function () {
    return redirect()->route('devices.index');
})->middleware('auth');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return redirect()->route('devices.index');
    })->name('dashboard');

    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
    Route::resource('devices', DeviceController::class);
    
    Route::get('/stats', [StatsController::class, 'index'])->name('stats.index');
    Route::get('/employees/{employee}', [EmployeeStatsController::class, 'show'])->name('employees.show');
    
    // Синхронизация с Okdesk
    Route::get('/sync-by-status', [SyncOkdeskController::class, 'syncByStatus'])->name('sync.by-status');
    Route::get('/sync-test', [SyncOkdeskController::class, 'syncTest'])->name('sync.test');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
Route::get('/debug-schema', function() {
    // Получаем все колонки таблицы devices
    $columns = \Illuminate\Support\Facades\Schema::getColumnListing('devices');
    
    // Получаем все константы из модели Device (чтобы найти статус "На проверке ОТК")
    $reflection = new ReflectionClass(\App\Models\Device::class);
    $constants = $reflection->getConstants();

    return response()->json([
        'columns_in_devices_table' => $columns,
        'device_constants' => $constants,
    ]);
});
Route::get('/test-email-export', function() {
    $report = [];
    
    // 1. Проверяем устройства со статусом ОТК
    try {
        $devices = \App\Models\Device::where('status', \App\Models\Device::STATUS_OTK)->get();
        $report['step_1_devices_count'] = $devices->count();
        $report['step_1_sample'] = $devices->take(2)->toArray();
    } catch (\Exception $e) {
        $report['step_1_error'] = $e->getMessage();
    }
    
    // 2. Проверяем настройки почты
    $report['step_2_mail_config'] = [
        'MAIL_MAILER' => config('mail.mailers.smtp.transport') ?? config('mail.default'),
        'MAIL_HOST' => config('mail.mailers.smtp.host') ?? 'не настроен',
        'MAIL_PORT' => config('mail.mailers.smtp.port') ?? 'не настроен',
        'MAIL_USERNAME' => config('mail.mailers.smtp.username') ?? 'не настроен',
        'MAIL_FROM_ADDRESS' => config('mail.from.address') ?? 'не настроен',
        'QA_CHECK_EMAIL' => config('services.qa_check.email') ?? 'НЕ НАСТРОЕН!',
    ];
    
    // 3. Пытаемся сгенерировать Excel
    try {
        $filename = 'TEST_' . date('Y-m-d_H-i-s') . '.xlsx';
        \Maatwebsite\Excel\Facades\Excel::store(
            new \App\Exports\QACheckExport, 
            'exports/' . $filename, 
            'local'
        );
        $report['step_3_excel_status'] = '✅ Файл создан: ' . $filename;
        $report['step_3_file_path'] = storage_path('app/exports/' . $filename);
        $report['step_3_file_exists'] = file_exists(storage_path('app/exports/' . $filename));
    } catch (\Exception $e) {
        $report['step_3_excel_error'] = $e->getMessage();
    }
    
    // 4. Пытаемся отправить тестовое письмо (без вложения, просто текст)
    try {
        \Illuminate\Support\Facades\Mail::raw('Это тестовое письмо от системы ремонтов. Если вы его получили — почта работает!', function($message) {
            $message->to(config('services.qa_check.email', 'islam.kushkhaunov@armorjack.ru'))
                    ->subject('🧪 ТЕСТ: Проверка работы почты');
        });
        $report['step_4_mail_status'] = '✅ Письмо отправлено (проверьте почту)';
    } catch (\Exception $e) {
        $report['step_4_mail_error'] = $e->getMessage();
    }
    
    return response()->json($report);
});

require __DIR__.'/auth.php';