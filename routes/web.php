<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\EmployeeStatsController;

Route::get('/', function () {
    return redirect()->route('devices.index');
})->middleware('auth');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return redirect()->route('devices.index');
    })->name('dashboard');
Route::get('/analytics', [App\Http\Controllers\AnalyticsController::class, 'index'])->name('analytics.index');

    Route::resource('devices', DeviceController::class);
    
    Route::get('/stats', [StatsController::class, 'index'])->name('stats.index');
    Route::get('/employees/{employee}', [EmployeeStatsController::class, 'show'])->name('employees.show');
    
    // Маршрут для синхронизации с Okdesk
    Route::post('/sync-okdesk', function () {
    // Определяем путь к PHP и artisan
    $phpBinary = PHP_BINARY;
    $artisanPath = base_path('artisan');
    $logPath = storage_path('logs/sync-okdesk.log');
    
    // Формируем команду для запуска в фоне
    $command = "{$phpBinary} {$artisanPath} sync:okdesk > {$logPath} 2>&1";
    
    // Запускаем в фоне (разные команды для Windows и Linux)
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Windows - запускаем в фоне через pclose/popen
        pclose(popen("start /B {$phpBinary} {$artisanPath} sync:okdesk > {$logPath} 2>&1", 'r'));
    } else {
        // Linux - запускаем в фоне через exec
        exec("{$command} &");
    }
    
    return redirect()->route('devices.index')->with('success', '🔄 Синхронизация запущена в фоне! Обновите страницу через 1-2 минуты, чтобы увидеть новые устройства.');
})->name('sync.okdesk');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';