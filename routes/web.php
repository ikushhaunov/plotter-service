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
    
    Route::get('/sync-by-status', [SyncOkdeskController::class, 'syncByStatus'])->name('sync.by-status');
    Route::get('/sync-test', [SyncOkdeskController::class, 'syncTest'])->name('sync.test');
// Экспорт устройств на проверке ОТК в Excel
Route::get('/export-qa-check', function() {
    $filename = 'OTK_Check_' . date('Y-m-d_H-i') . '.xlsx';
    
    return \Maatwebsite\Excel\Facades\Excel::download(
        new \App\Exports\QACheckExport,
        $filename
    );
})->name('export.qa-check');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/debug-users-list', function() {
    $result = [];

   Route::get('/make-islam-admin', function() {
    // Находим пользователя по email
    $user = \App\Models\User::where('email', 'kushkhaunov@service.com')->first();

    if ($user) {
        // Меняем роль на admin
        $user->role = 'admin';
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => '✅ Роль пользователя Islam успешно изменена на "admin"!',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role
            ]
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    return response()->json([
        'status' => 'error', 
        'message' => 'Пользователь с таким email не найден'
    ], 404, [], JSON_UNESCAPED_UNICODE);
});
require __DIR__.'/auth.php';