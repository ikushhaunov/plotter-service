<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\EmployeeStatsController;
use App\Http\Controllers\SyncOkdeskController;
use App\Http\Controllers\TestOkdeskController;
use App\Http\Controllers\AnalyticsController;

// ==========================================================
// 1. ОТЛАДОЧНЫЕ МАРШРУТЫ (Без middleware auth для быстрой проверки)
// ==========================================================
Route::get('/debug-okdesk', [TestOkdeskController::class, 'debug']);
Route::get('/test-ticket/{id}', [TestOkdeskController::class, 'checkTicket']);

// ==========================================================
// 2. ОСНОВНЫЕ МАРШРУТЫ
// ==========================================================
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
    
    // Маршрут для запуска синхронизации В ФОНЕ (через кнопку на сайте, только POST)
    Route::post('/sync-okdesk-bg', function () {
        $phpBinary = PHP_BINARY;
        $artisanPath = base_path('artisan');
        $logPath = storage_path('logs/sync-okdesk.log');
        $command = "{$phpBinary} {$artisanPath} sync:okdesk > {$logPath} 2>&1";
        
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            pclose(popen("start /B {$phpBinary} {$artisanPath} sync:okdesk > {$logPath} 2>&1", 'r'));
        } else {
            exec("{$command} &");
        }
        
        return redirect()->route('devices.index')->with('success', '🔄 Синхронизация запущена в фоне!');
    })->name('sync.okdesk.bg');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ==========================================================
// 3. МАРШРУТ ДЛЯ GITHUB ACTIONS И ПРЯМОГО GET-ЗАПРОСА (возвращает JSON)
// ==========================================================
Route::get('/sync-okdesk', [SyncOkdeskController::class, 'sync']);
Route::get('/sync-by-status', [SyncOkdeskController::class, 'syncByStatus']);
Route::get('/debug-status', [SyncOkdeskController::class, 'debugStatus']);
Route::get('/check-db', function() {
    $totalDevices = \App\Models\Device::count();
    $recentDevices = \App\Models\Device::orderBy('id', 'desc')->limit(20)->get();
    
    $devicesList = $recentDevices->map(function($device) {
        return [
            'id' => $device->id,
            'issue_number' => $device->issue_number,
            'device_number' => $device->device_number,
            'status' => $device->status,
            'created_at' => $device->created_at,
        ];
    });
    
    return response()->json([
        'total_devices_in_db' => $totalDevices,
        'last_20_devices' => $devicesList
    ]);
});
Route::get('/sync-test', [SyncOkdeskController::class, 'syncTest']);

require __DIR__.'/auth.php';