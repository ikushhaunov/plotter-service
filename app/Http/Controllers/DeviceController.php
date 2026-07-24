<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Employee;
use App\Models\Part;
use App\Models\PlotterModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeviceController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Device::query();

        // === ФИЛЬТРАЦИЯ ПО РОЛЯМ ===
        if ($user->isMaster()) {
            // Мастер видит:
            // 1. ВСЕ устройства в статусах "Принято" (1) и "Диагностика" (2) - общий пул
            // 2. Устройства, которые закреплены лично за ним (любого статуса)
            $query->where(function ($q) use ($user) {
                $q->whereIn('status', [Device::STATUS_RECEIVED, Device::STATUS_DIAGNOSTICS])
                  ->orWhere('employee_id', $user->employee_id);
            });
        } elseif ($user->isOtk()) {
            $query->where('status', Device::STATUS_OTK);
        }

        // Стандартные фильтры
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }
        if ($request->has('plotter_model_id') && $request->plotter_model_id) {
            $query->where('plotter_model_id', $request->plotter_model_id);
        }
        if ($request->has('employee_id') && $request->employee_id) {
            $query->where('employee_id', $request->employee_id);
        }
        if ($request->has('date_from') && $request->date_from) {
            $query->where('received_date', '>=', $request->date_from);
        }
        if ($request->has('date_to') && $request->date_to) {
            $query->where('received_date', '<=', $request->date_to);
        }
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('device_number', 'like', '%' . $search . '%')
                  ->orWhere('issue_number', 'like', '%' . $search . '%');
            });
        }

        $devices = $query->with(['employee', 'plotterModel'])->orderBy('received_date', 'desc')->get();
        $employees = Employee::all();
        $plotterModels = PlotterModel::active()->get();

        // Подсчёт для карточек
        $dateQuery = Device::query();
        if ($user->isMaster()) {
            $dateQuery->where(function ($q) use ($user) {
                $q->whereIn('status', [Device::STATUS_RECEIVED, Device::STATUS_DIAGNOSTICS])
                  ->orWhere('employee_id', $user->employee_id);
            });
        } elseif ($user->isOtk()) {
            $dateQuery->where('status', Device::STATUS_OTK);
        }

        if ($request->has('date_from') && $request->date_from) $dateQuery->where('received_date', '>=', $request->date_from);
        if ($request->has('date_to') && $request->date_to) $dateQuery->where('received_date', '<=', $request->date_to);

        $statusCounts = [
            'received' => (clone $dateQuery)->where('status', Device::STATUS_RECEIVED)->count(),
            'diagnostics' => (clone $dateQuery)->where('status', Device::STATUS_DIAGNOSTICS)->count(),
            'repair' => (clone $dateQuery)->where('status', Device::STATUS_REPAIR)->count(),
            'otk' => (clone $dateQuery)->where('status', Device::STATUS_OTK)->count(),
            'disassembled' => (clone $dateQuery)->where('status', Device::STATUS_DISASSEMBLED)->count(),
            'repaired' => (clone $dateQuery)->where('status', Device::STATUS_REPAIRED)->count(),
            'total' => (clone $dateQuery)->count(),
        ];

        return view('devices.index', compact('devices', 'employees', 'plotterModels', 'statusCounts'));
    }

    public function create()
    {
        if (Auth::user()->isOtk()) abort(403, 'Доступ запрещен');
        $employees = Employee::all();
        $parts = Part::active()->get();
        $plotterModels = PlotterModel::active()->get();
        return view('devices.create', compact('employees', 'parts', 'plotterModels'));
    }

    public function store(Request $request)
    {
        if (Auth::user()->isOtk()) abort(403);
        
        $validated = $request->validate([
            'device_number' => 'required|string|max:255',
            'issue_number' => 'nullable|string|max:255|unique:devices,issue_number',
            'plotter_model_id' => 'nullable|exists:plotter_models,id',
            'received_date' => 'required|date',
            'fault_description' => 'required|string',
            'employee_id' => 'nullable|exists:employees,id',
            'parts_list' => 'nullable|array',
            'parts_list.*' => 'exists:parts,id',
            'replaced_parts_custom' => 'nullable|string|max:2000',
        ]);

        $replacedParts = $this->buildPartsString($validated['parts_list'] ?? [], $validated['replaced_parts_custom'] ?? '');

        $device = Device::create([
            'device_number' => $validated['device_number'],
            'issue_number' => $validated['issue_number'] ?? null,
            'plotter_model_id' => $validated['plotter_model_id'] ?? null,
            'received_date' => $validated['received_date'],
            'fault_description' => $validated['fault_description'],
            'employee_id' => $validated['employee_id'] ?? Auth::user()->employee_id,
            'replaced_parts' => $replacedParts,
        ]);

        $device->addHistory(Device::STATUS_RECEIVED, Auth::id(), 'Устройство принято в ремонт');

        return redirect()->route('devices.index')->with('success', 'Устройство успешно добавлено');
    }

    public function show(Device $device)
    {
        $user = Auth::user();
        
        // Мастер может смотреть свои устройства ИЛИ устройства в общем пуле (статус 1 или 2)
        if ($user->isMaster()) {
            $isOwn = ($device->employee_id == $user->employee_id);
            $isSharedPool = in_array($device->status, [Device::STATUS_RECEIVED, Device::STATUS_DIAGNOSTICS]);

            if (!$isOwn && !$isSharedPool) {
                abort(403, 'Вы можете просматривать только свои устройства или устройства в общем пуле.');
            }
        }

        if ($user->isOtk() && $device->status !== Device::STATUS_OTK) {
            abort(403, 'ОТК может просматривать только устройства на проверке.');
        }

        $device->load(['employee', 'histories.changedBy', 'plotterModel']);
        return view('devices.show', compact('device'));
    }

    public function edit(Device $device)
    {
        $user = Auth::user();
        if ($user->isMaster() && $device->employee_id !== $user->employee_id) abort(403);
        if ($user->isOtk() && $device->status !== Device::STATUS_OTK) abort(403, 'ОТК может редактировать только устройства на проверке');

        $employees = Employee::all();
        $parts = Part::active()->get();
        $plotterModels = PlotterModel::active()->get();
        return view('devices.edit', compact('device', 'employees', 'parts', 'plotterModels'));
    }

    public function update(Request $request, Device $device)
    {
        $user = Auth::user();
        if ($user->isMaster() && $device->employee_id !== $user->employee_id) abort(403);
        if ($user->isOtk() && $device->status !== Device::STATUS_OTK) abort(403);

        $validated = $request->validate([
            'device_number' => 'required|string|max:255',
            'issue_number' => 'nullable|string|max:255|unique:devices,issue_number,' . $device->id,
            'plotter_model_id' => 'nullable|exists:plotter_models,id',
            'status' => 'required|integer|in:1,2,3,4,5,6',
            'received_date' => 'required|date',
            'repair_date' => 'nullable|date',
            'fault_description' => 'required|string',
            'warehouse_date' => 'nullable|date',
            'employee_id' => 'nullable|exists:employees,id',
            'comment' => 'nullable|string|max:1000',
            'parts_list' => 'nullable|array',
            'parts_list.*' => 'exists:parts,id',
            'replaced_parts_custom' => 'nullable|string|max:2000',
        ]);

        if ($user->isOtk() && !in_array($validated['status'], [Device::STATUS_REPAIRED, Device::STATUS_REPAIR])) {
            abort(403, 'ОТК может только завершать ремонт или возвращать устройство мастеру');
        }

        $oldStatus = $device->status;
        $newStatus = $validated['status'];
        $comment = $validated['comment'] ?? null;
        $replacedParts = $this->buildPartsString($validated['parts_list'] ?? [], $validated['replaced_parts_custom'] ?? '');

        $device->update([
            'device_number' => $validated['device_number'],
            'issue_number' => $validated['issue_number'] ?? null,
            'plotter_model_id' => $validated['plotter_model_id'] ?? null,
            'status' => $newStatus,
            'received_date' => $validated['received_date'],
            'repair_date' => $validated['repair_date'] ?? null,
            'fault_description' => $validated['fault_description'],
            'warehouse_date' => $validated['warehouse_date'] ?? null,
            'employee_id' => $validated['employee_id'] ?? $device->employee_id,
            'replaced_parts' => $replacedParts,
        ]);

        if ($oldStatus != $newStatus) {
            $device->addHistory($newStatus, Auth::id(), $comment);
        }

        return redirect()->route('devices.show', $device)->with('success', 'Устройство успешно обновлено');
    }

    public function destroy(Device $device)
    {
        if (!Auth::user()->isAdmin()) abort(403, 'Удалять устройства может только администратор');
        $device->delete();
        return redirect()->route('devices.index')->with('success', 'Устройство удалено');
    }

    // === НОВЫЙ МЕТОД: Взять устройство в работу ===
    public function take(Device $device)
    {
        $user = Auth::user();
        
        if (!$user || !$user->isMaster()) {
            return redirect()->back()->with('error', "❌ Ошибка доступа: только мастер может взять устройство в работу.");
        }

        if (!in_array($device->status, [Device::STATUS_RECEIVED, Device::STATUS_DIAGNOSTICS])) {
            return redirect()->back()->with('error', "❌ Это устройство нельзя взять в работу. Текущий статус: {$device->status}.");
        }

        // Берем в работу (перезаписываем employee_id на текущего мастера)
        $device->update([
            'employee_id' => $user->employee_id,
            'status' => Device::STATUS_REPAIR,
        ]);

        $device->addHistory(Device::STATUS_REPAIR, $user->id, 'Мастер взял устройство в работу');

        return redirect()->route('devices.index')->with('success', '✅ Вы успешно взяли устройство #' . $device->device_number . ' в работу!');
    }

    // === ВАЖНЫЙ МЕТОД: Сборка строки запчастей ===
    private function buildPartsString(array $partsIds, string $customText): string
    {
        $parts = [];
        if (!empty($partsIds)) {
            $selectedParts = Part::whereIn('id', $partsIds)->pluck('name')->toArray();
            $parts = array_merge($parts, $selectedParts);
        }
        $customText = trim($customText);
        if (!empty($customText)) {
            $parts[] = $customText;
        }
        return implode("\n", $parts);
    }
}