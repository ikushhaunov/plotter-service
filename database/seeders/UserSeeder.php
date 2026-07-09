<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'Филаткин Д.', 'email' => 'filatkin@service.ru', 'password' => Hash::make('123456')],
            ['name' => 'Перемышлев П.', 'email' => 'peremyslev@service.ru', 'password' => Hash::make('123456')],
            ['name' => 'Валиев Д.', 'email' => 'valiev@service.ru', 'password' => Hash::make('123456')],
            ['name' => 'Назаров Т.', 'email' => 'nazarov@service.ru', 'password' => Hash::make('123456')],
        ];

        foreach ($users as $user) {
            User::create($user);
        }
    }
}