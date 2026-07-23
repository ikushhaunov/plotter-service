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
    
    // ✅ ДОБАВЛЕН ПРОПУЩЕННЫЙ МАРШРУТ, КОТОРЫЙ ТРЕБУЕТ ШАБЛОН
    Route::post('/sync-okdesk', [SyncOkdeskController::class, 'syncByStatus'])->name('sync.okdesk');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Экспорт устройств на проверке ОТК в Excel
    Route::get('/export-qa-check', function() {
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\QACheckExport,
            'OTK_Check_' . date('Y-m-d_H-i') . '.xlsx'
        );
    })->name('export.qa-check');
});




require __DIR__.'/auth.php';