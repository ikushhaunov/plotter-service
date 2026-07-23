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

Route::get('/seed-parts-final', function() {
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
        ['name' => 'Жгут основной для плоттеров AJP', 'is_active' => true], // Переименовал для аккуратности
    ];

    $count = 0;
    foreach ($partsData as $data) {
        \App\Models\Part::updateOrCreate(
            ['name' => $data['name']],
            ['is_active' => $data['is_active']]
        );
        $count++;
    }

    // Удаляем тестовую запчасть, чтобы не мешала
    \App\Models\Part::where('name', 'TEST_DEBUG_PART_123')->delete();

    return response()->json([
        'status' => 'success',
        'message' => "✅ Успешно добавлено/обновлено {$count} позиций!",
        'total_parts_in_db' => \App\Models\Part::where('is_active', true)->count()
    ], 200, [], JSON_UNESCAPED_UNICODE);
});require __DIR__.'/auth.php';