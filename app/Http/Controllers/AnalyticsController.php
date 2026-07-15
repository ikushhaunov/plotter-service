<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $month = $request->get('month', date('m'));

        // Данные по месяцам за выбранный год (ИСПРАВЛЕНО: добавлена запятая и TO_CHAR для PostgreSQL)
        $monthlyData = Device::select(
            DB::raw("TO_CHAR(received_date, 'MM') as month_num"), // <-- Здесь была пропущена запятая
            DB::raw("COUNT(*) as total"),
            DB::raw("SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as received"),
            DB::raw("SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) as diagnostics"),
            DB::raw("SUM(CASE WHEN status = 3 THEN 1 ELSE 0 END) as repair"),
            DB::raw("SUM(CASE WHEN status = 6 THEN 1 ELSE 0 END) as otk"),
            DB::raw("SUM(CASE WHEN status = 4 THEN 1 ELSE 0 END) as disassembled"),
            DB::raw("SUM(CASE WHEN status = 5 THEN 1 ELSE 0 END) as repaired")
        )
        ->whereYear('received_date', $year)
        ->groupBy('month_num')
        ->orderBy('month_num')
        ->get();

        // Названия месяцев
        $monthNames = [
            '01' => 'Январь', '02' => 'Февраль', '03' => 'Март',
            '04' => 'Апрель', '05' => 'Май', '06' => 'Июнь',
            '07' => 'Июль', '08' => 'Август', '09' => 'Сентябрь',
            '10' => 'Октябрь', '11' => 'Ноябрь', '12' => 'Декабрь'
        ];

        // Подготовка данных для графиков
        $labels = [];
        $totals = [];
        $receivedData = [];
        $diagnosticsData = [];
        $repairData = [];
        $otkData = [];
        $disassembledData = [];
        $repairedData = [];

        for ($m = 1; $m <= 12; $m++) {
            $monthKey = str_pad($m, 2, '0', STR_PAD_LEFT);
            $labels[] = $monthNames[$monthKey];
            
            $data = $monthlyData->firstWhere('month_num', $monthKey);
            $totals[] = $data ? (int)$data->total : 0;
            $receivedData[] = $data ? (int)$data->received : 0;
            $diagnosticsData[] = $data ? (int)$data->diagnostics : 0;
            $repairData[] = $data ? (int)$data->repair : 0;
            $otkData[] = $data ? (int)$data->otk : 0;
            $disassembledData[] = $data ? (int)$data->disassembled : 0;
            $repairedData[] = $data ? (int)$data->repaired : 0;
        }

        // Статистика по сотрудникам за выбранный месяц
        $employeeStats = Employee::all()->map(function($employee) use ($year, $month) {
            $query = Device::where('employee_id', $employee->id)
                ->whereYear('received_date', $year)
                ->whereMonth('received_date', $month);
            
            return [
                'name' => $employee->name,
                'total' => $query->count(),
                'repaired' => (clone $query)->where('status', Device::STATUS_REPAIRED)->count(),
                'in_repair' => (clone $query)->whereIn('status', [
                    Device::STATUS_RECEIVED,
                    Device::STATUS_DIAGNOSTICS,
                    Device::STATUS_REPAIR,
                    Device::STATUS_OTK
                ])->count(),
            ];
        });

        // Общая статистика за выбранный месяц
        $monthStats = [
            'total' => Device::whereYear('received_date', $year)->whereMonth('received_date', $month)->count(),
            'received' => Device::whereYear('received_date', $year)->whereMonth('received_date', $month)->where('status', Device::STATUS_RECEIVED)->count(),
            'diagnostics' => Device::whereYear('received_date', $year)->whereMonth('received_date', $month)->where('status', Device::STATUS_DIAGNOSTICS)->count(),
            'repair' => Device::whereYear('received_date', $year)->whereMonth('received_date', $month)->where('status', Device::STATUS_REPAIR)->count(),
            'otk' => Device::whereYear('received_date', $year)->whereMonth('received_date', $month)->where('status', Device::STATUS_OTK)->count(),
            'disassembled' => Device::whereYear('received_date', $year)->whereMonth('received_date', $month)->where('status', Device::STATUS_DISASSEMBLED)->count(),
            'repaired' => Device::whereYear('received_date', $year)->whereMonth('received_date', $month)->where('status', Device::STATUS_REPAIRED)->count(),
        ];

        // Доступные годы (ИСПРАВЛЕНО: заменено strftime на TO_CHAR для PostgreSQL)
        $availableYears = Device::selectRaw("TO_CHAR(received_date, 'YYYY') as year")
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->filter()
            ->values();

        if ($availableYears->isEmpty()) {
            $availableYears = collect([date('Y')]);
        }

        return view('analytics.index', compact(
            'year', 'month', 'monthNames',
            'labels', 'totals', 'receivedData', 'diagnosticsData', 
            'repairData', 'otkData', 'disassembledData', 'repairedData',
            'employeeStats', 'monthStats', 'availableYears'
        ));
    }
}