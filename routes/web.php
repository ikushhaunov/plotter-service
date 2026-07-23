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

// Диагностический маршрут для проверки email (БЕЗ авторизации для теста)
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
        'MAIL_MAILER' => config('mail.default'),
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
    
    // 4. Пытаемся отправить тестовое письмо
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
Route::get('/check-mail-config', function() {
    return response()->json([
        'MAIL_MAILER' => config('mail.default'),
        'MAIL_HOST' => config('mail.mailers.smtp.host') ?? 'не задан',
        'MAIL_PORT' => config('mail.mailers.smtp.port') ?? 'не задан',
        'MAIL_USERNAME' => config('mail.mailers.smtp.username') ?? 'не задан',
        'QA_CHECK_EMAIL' => config('services.qa_check.email') ?? 'НЕ НАСТРОЕН!',
        'devices_otk_count' => \App\Models\Device::where('status', \App\Models\Device::STATUS_OTK)->count(),
        'message' => 'Если вы видите этот текст, сервер работает быстро и настройки загружены!'
    ]);
});
Route::get('/test-smtp-only', function() {
    try {
        \Illuminate\Support\Facades\Mail::raw('Это тестовое сообщение для проверки SMTP соединения.', function($message) {
            $message->to('islam.kushkhaunov@armorjack.ru')
                    ->subject('🔧 Тест SMTP соединения');
        });
        return response()->json([
            'status' => 'success', 
            'message' => '✅ Письмо успешно отправлено через SMTP!'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'error_message' => $e->getMessage(),
            'hint' => 'Именно эта ошибка вызывает зависание и последующий 504 таймаут.'
        ], 500);
    }
});
Route::get('/seed-parts', function() {
    // Ваш точный список запчастей и компонентов
    $partsData = [
        ['name' => 'Планшет Digma Z10 серый 4G 10.1"', 'is_active' => true],
        ['name' => 'Стандартный нож для плоттеров Cameo/Portrait/Curio/Craftrobo 45гр 0,1-1 черный', 'is_active' => true],
        ['name' => 'Плата печатная Cutter_board_rev_D_Docs_12032021', 'is_active' => true],
        ['name' => '10.1inch HDMI LCD WAVESHAR электронные модули', 'is_active' => true],
        ['name' => '7inch HDMI LCD [C]/WAVESHAR// дисплей', 'is_active' => true],
        ['name' => 'Микрокомпьютер Raspberry Pi 3', 'is_active' => true],
        ['name' => 'Полуфабрикат боковины правой белой PS AJP100-XS/AJP200-XS 180*170*86мм AJ', 'is_active' => true],
        ['name' => 'Полуфабрикат боковины левой белой PS AJP100-XS/AJP200-XS 180*170*86мм AJ', 'is_active' => true],
        ['name' => 'Поперечная планка горизонтальная PS для AJP100-XS/AJP200-XS 298*28*6мм AJ', 'is_active' => true],
        ['name' => 'Панель передняя белая PS для AJP100-XS/AJP200-XS 298*44*6мм AJ', 'is_active' => true],
        ['name' => 'Панель задняя белая PS для AJP100-XS/AJP200-XS 298*90*6мм AJ', 'is_active' => true],
        ['name' => 'Платформа верхняя белая PS для AJP100-XS/AJP200-XS 298*148*6 мм AJ', 'is_active' => true],
        ['name' => 'Корпус планшета Digma 1314C 10.1"', 'is_active' => true],
        ['name' => 'Комплект крючков и опор к корпусу планшета Digma CITI 1314C 10.1"', 'is_active' => true],
        ['name' => 'Жгут основной для плоттеров AJP(Переименовать)', 'is_active' => true],
    ];

    $count = 0;
    foreach ($partsData as $data) {
        \App\Models\Part::updateOrCreate(
            ['name' => $data['name']],
            ['is_active' => $data['is_active']]
        );
        $count++;
    }

    return response()->json([
        'status' => 'success',
        'message' => "✅ Успешно добавлено/обновлено {$count} позиций!",
        'total_parts_in_db' => \App\Models\Part::count()
    ], 200, [], JSON_UNESCAPED_UNICODE);
});
require __DIR__.'/auth.php';