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

    // 1. Проверяем таблицу users (основная таблица авторизации Laravel)
    if (class_exists('\App\Models\User')) {
        $users = \App\Models\User::all()->map(function($user) {
            return [
                'id' => $user->id,
                'name' => $user->name ?? 'Не указано',
                'email' => $user->email ?? 'Не указано',
                'role_column' => $user->role ?? 'Колонка role отсутствует',
                'is_admin' => $user->is_admin ?? ($user->role === 'admin' ? true : false),
                'is_master' => method_exists($user, 'isMaster') ? $user->isMaster() : false,
                'is_otk' => method_exists($user, 'isOtk') ? $user->isOtk() : false,
            ];
        })->toArray();
        $result['users_table'] = $users;
    }

    // 2. Проверяем таблицу employees (если сотрудники хранятся отдельно)
    if (class_exists('\App\Models\Employee')) {
        $employees = \App\Models\Employee::all()->map(function($emp) {
            return [
                'id' => $emp->id,
                'name' => $emp->name ?? 'Не указано',
                'role' => $emp->role ?? 'Не указана',
            ];
        })->toArray();
        $result['employees_table'] = $employees;
    }

    return response()->json($result, 200, [], JSON_UNESCAPED_UNICODE);
});
require __DIR__.'/auth.php';