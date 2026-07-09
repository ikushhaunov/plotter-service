<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Device;
use Illuminate\Http\Request;

class EmployeeStatsController extends Controller
{
    public function show(Employee $employee, Request $request)
    {
        $query = Device::where('employee_id', $employee->id);

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        $devices = $query->orderBy('created_at', 'desc')->paginate(15);

        $stats = [
            'received' => Device::where('employee_id', $employee->id)->where('status', Device::STATUS_RECEIVED)->count(),
            'diagnostics' => Device::where('employee_id', $employee->id)->where('status', Device::STATUS_DIAGNOSTICS)->count(),
            'in_repair' => Device::where('employee_id', $employee->id)->where('status', Device::STATUS_REPAIR)->count(),
            'otk' => Device::where('employee_id', $employee->id)->where('status', Device::STATUS_OTK)->count(),
            'disassembled' => Device::where('employee_id', $employee->id)->where('status', Device::STATUS_DISASSEMBLED)->count(),
            'repaired' => Device::where('employee_id', $employee->id)->where('status', Device::STATUS_REPAIRED)->count(),
            'total' => Device::where('employee_id', $employee->id)->count(),
        ];

        return view('employees.show', compact('employee', 'devices', 'stats'));
    }
}