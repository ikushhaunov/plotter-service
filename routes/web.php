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
Route::post('/devices/{device}/take', [DeviceController::class, 'take'])->name('devices.take');
    
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


Route::get('/debug-user-employee-link', function() {
    $result = [];
    
    // 1. Проверяем структуру таблицы users
    $userColumns = \Illuminate\Support\Facades\Schema::getColumnListing('users');
    $result['users_table_columns'] = $userColumns;
    
    // 2. Получаем всех мастеров и их employee_id
    $masters = \App\Models\User::where('role', 'master')->get()->map(function($user) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'employee_id' => $user->employee_id ?? 'НЕ ЗАПОЛНЕНО',
        ];
    })->toArray();
    $result['masters'] = $masters;
    
    // 3. Проверяем таблицу employees
    $employees = \App\Models\Employee::all()->map(function($emp) {
        return [
            'id' => $emp->id,
            'name' => $emp->name,
        ];
    })->toArray();
    $result['employees'] = $employees;
    
    // 4. Проверяем, как работает метод index в DeviceController
    $controller = new \App\Http\Controllers\DeviceController();
    $reflection = new ReflectionMethod($controller, 'index');
    $result['index_method_code'] = file_get_contents($reflection->getFileName());
    
    return response()->json($result, 200, [], JSON_UNESCAPED_UNICODE);
});






Route::get('/debug-employees-schema', function() {
    try {
        $columns = \Illuminate\Support\Facades\Schema::getColumnListing('employees');
        $count = \App\Models\Employee::count();
        $sample = \App\Models\Employee::limit(3)->get()->toArray();

        return response()->json([
            'table' => 'employees',
            'columns' => $columns,
            'total_records' => $count,
            'sample_data' => $sample
        ], 200, [], JSON_UNESCAPED_UNICODE);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500, [], JSON_UNESCAPED_UNICODE);
    }
});







require __DIR__.'/auth.php';