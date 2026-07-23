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


Route::get('/seed-team-users', function() {
    $team = [
        // Мастера
        ['name' => 'Перемышлев П.', 'email' => 'peremyshlev@armorjack.ru', 'role' => 'master', 'password' => 'master123'],
        ['name' => 'Филаткин Д.', 'email' => 'filatkin@armorjack.ru', 'role' => 'master', 'password' => 'master123'],
        ['name' => 'Назаров Т.', 'email' => 'nazarov@armorjack.ru', 'role' => 'master', 'password' => 'master123'],
        ['name' => 'Валиев Д.', 'email' => 'valiev@armorjack.ru', 'role' => 'master', 'password' => 'master123'],
        // ОТК
        ['name' => 'Зинченко В.', 'email' => 'zinchenko@armorjack.ru', 'role' => 'otk', 'password' => 'otk123'],
        ['name' => 'Крамаренко И.', 'email' => 'kramarenko@armorjack.ru', 'role' => 'otk', 'password' => 'otk123'],
    ];

    $result = [];
    foreach ($team as $user) {
        $created = \App\Models\User::updateOrCreate(
            ['email' => $user['email']],
            [
                'name' => $user['name'],
                'role' => $user['role'],
                'password' => bcrypt($user['password'])
            ]
        );
        $result[] = [
            'name' => $created->name,
            'email' => $created->email,
            'role' => $created->role,
            'password_to_login' => $user['password']
        ];
    }

    return response()->json([
        'status' => 'success',
        'message' => '✅ Аккаунты команды успешно созданы или обновлены!',
        'users' => $result
    ], 200, [], JSON_UNESCAPED_UNICODE);
});



require __DIR__.'/auth.php';