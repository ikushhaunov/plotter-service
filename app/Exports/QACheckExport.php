<?php

namespace App\Exports;

use App\Models\Device;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class QACheckExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function collection()
    {
        // Получаем устройства со статусом "На проверке ОТК" (STATUS_OTK = 6)
        return Device::where('status', Device::STATUS_OTK)
            ->with(['employee', 'plotterModel'])
            ->orderBy('repair_date', 'desc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Номер устройства',
            'Исполнитель',
            'Замененные запчасти',
            'Дата ремонта',
            'Модель плоттера',
        ];
    }

    public function map($device): array
    {
        // Безопасное получение имени исполнителя
        $executor = 'Не указан';
        if ($device->employee_id) {
            $executor = optional($device->employee)->name 
                     ?? optional($device->user)->name 
                     ?? 'Сотрудник #' . $device->employee_id;
        }

        // Безопасное получение названия модели
        $model = optional($device->plotterModel)->name ?? 'Не указана';

        return [
            $device->device_number,
            $executor,
            $device->replaced_parts ?: 'Не указаны',
            $device->repair_date ? \Carbon\Carbon::parse($device->repair_date)->format('d.m.Y') : 'Не указана',
            $model,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}