<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class OtkEmployeeSeeder extends Seeder
{
    public function run(): void
    {
        // Добавляем сотрудников ОТК
        $otkEmployees = [
            ['name' => 'Зинченко В.'],
            ['name' => 'Крамаренко И.'],
        ];

        foreach ($otkEmployees as $employee) {
            Employee::firstOrCreate(['name' => $employee['name']]);
        }

        // Добавляем пользователей для входа в систему
        $otkUsers = [
            ['name' => 'Зинченко В.', 'email' => 'zinchenko@service.ru', 'password' => Hash::make('123456')],
            ['name' => 'Крамаренко И.', 'email' => 'kramarenko@service.ru', 'password' => Hash::make('123456')],
        ];

        foreach ($otkUsers as $user) {
            User::firstOrCreate(['email' => $user['email']], $user);
        }
    }
}