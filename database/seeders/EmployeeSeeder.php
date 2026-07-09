<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $employees = [
            ['name' => 'Филаткин Д.'],
            ['name' => 'Перемышлев П.'],
            ['name' => 'Валиев Д.'],
            ['name' => 'Назаров Т.'],
        ];

        foreach ($employees as $employee) {
            Employee::create($employee);
        }
    }
}