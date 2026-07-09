<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Device;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    public function index()
    {
        $employees = Employee::all();
        
        $stats = [];
        
        foreach ($employees as $employee) {
            $stats[] = [
                'employee' => $employee,
                'received' => Device::where('employee_id', $employee->id)
                    ->where('status', Device::STATUS_RECEIVED)->count(),
                'diagnostics' => Device::where('employee_id', $employee->id)
                    ->where('status', Device::STATUS_DIAGNOSTICS)->count(),
                'in_repair' => Device::where('employee_id', $employee->id)
                    ->where('status', Device::STATUS_REPAIR)->count(),
                'otk' => Device::where('employee_id', $employee->id)
                    ->where('status', Device::STATUS_OTK)->count(),
                'disassembled' => Device::where('employee_id', $employee->id)
                    ->where('status', Device::STATUS_DISASSEMBLED)->count(),
                'repaired' => Device::where('employee_id', $employee->id)
                    ->where('status', Device::STATUS_REPAIRED)->count(),
                'total' => Device::where('employee_id', $employee->id)->count(),
            ];
        }
        
        $totalStats = [
            'received' => Device::where('status', Device::STATUS_RECEIVED)->count(),
            'diagnostics' => Device::where('status', Device::STATUS_DIAGNOSTICS)->count(),
            'in_repair' => Device::where('status', Device::STATUS_REPAIR)->count(),
            'otk' => Device::where('status', Device::STATUS_OTK)->count(),
            'disassembled' => Device::where('status', Device::STATUS_DISASSEMBLED)->count(),
            'repaired' => Device::where('status', Device::STATUS_REPAIRED)->count(),
            'total' => Device::count(),
        ];
        
        return view('stats.index', compact('stats', 'totalStats'));
    }
}