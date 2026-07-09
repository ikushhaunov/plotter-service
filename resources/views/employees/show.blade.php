@extends('layouts.app')

@section('title', 'Статистика: ' . $employee->name)

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Сотрудник: {{ $employee->name }}</h1>
        <a href="{{ route('stats.index') }}" class="btn btn-secondary">Назад к статистике</a>
    </div>

    <!-- Статистика по статусам -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Статистика по устройствам</h5>
        </div>
        <div class="card-body">
            <div class="row text-center">
                <div class="col">
                    <a href="{{ route('employees.show', $employee) }}" class="text-decoration-none">
                        <div class="h3 mb-1">{{ $stats['total'] }}</div>
                        <div class="text-muted">Всего</div>
                    </a>
                </div>
                <div class="col">
                    <a href="{{ route('employees.show', [$employee, 'status' => 1]) }}" class="text-decoration-none">
                        <div class="h3 mb-1 text-secondary">{{ $stats['received'] }}</div>
                        <div class="text-muted">Принято</div>
                    </a>
                </div>
                <div class="col">
                    <a href="{{ route('employees.show', [$employee, 'status' => 2]) }}" class="text-decoration-none">
                        <div class="h3 mb-1 text-info">{{ $stats['diagnostics'] }}</div>
                        <div class="text-muted">Диагностика</div>
                    </a>
                </div>
                <div class="col">
                    <a href="{{ route('employees.show', [$employee, 'status' => 3]) }}" class="text-decoration-none">
                        <div class="h3 mb-1 text-warning">{{ $stats['in_repair'] }}</div>
                        <div class="text-muted">В ремонте</div>
                    </a>
                </div>
                <div class="col">
                    <a href="{{ route('employees.show', [$employee, 'status' => 6]) }}" class="text-decoration-none">
                        <div class="h3 mb-1 text-primary">{{ $stats['otk'] }}</div>
                        <div class="text-muted">ОТК</div>
                    </a>
                </div>
                <div class="col">
                    <a href="{{ route('employees.show', [$employee, 'status' => 4]) }}" class="text-decoration-none">
                        <div class="h3 mb-1 text-danger">{{ $stats['disassembled'] }}</div>
                        <div class="text-muted">Списано</div>
                    </a>
                </div>
                <div class="col">
                    <a href="{{ route('employees.show', [$employee, 'status' => 5]) }}" class="text-decoration-none">
                        <div class="h3 mb-1 text-success">{{ $stats['repaired'] }}</div>
                        <div class="text-muted">На складе</div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Фильтры -->
    <form method="GET" action="{{ route('employees.show', $employee) }}" class="mb-4">
        <div class="row g-3">
            <div class="col-md-6">
                <select name="status" class="form-select">
                    <option value="">Все статусы</option>
                    <option value="1" {{ request('status') == 1 ? 'selected' : '' }}>Принято в ремонт</option>
                    <option value="2" {{ request('status') == 2 ? 'selected' : '' }}>На диагностике</option>
                    <option value="3" {{ request('status') == 3 ? 'selected' : '' }}>В ремонте</option>
                    <option value="6" {{ request('status') == 6 ? 'selected' : '' }}>На проверке ОТК</option>
                    <option value="4" {{ request('status') == 4 ? 'selected' : '' }}>Списано/разукомплектовано</option>
                    <option value="5" {{ request('status') == 5 ? 'selected' : '' }}>Отремонтировано (на складе)</option>
                </select>
            </div>
            <div class="col-md-6">
                <button type="submit" class="btn btn-secondary">Фильтровать</button>
                <a href="{{ route('employees.show', $employee) }}" class="btn btn-outline-secondary">Сбросить</a>
            </div>
        </div>
    </form>

    <!-- Список устройств -->
    <div class="card">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">Устройства сотрудника</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Номер устройства</th>
                            <th>Статус</th>
                            <th>Дата приема</th>
                            <th>Дата ремонта</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($devices as $device)
                            <tr>
                                <td>{{ $device->id }}</td>
                                <td>
                                    <a href="{{ route('devices.show', $device) }}" class="text-decoration-none">
                                        {{ $device->device_number }}
                                    </a>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $device->status_color }}">
                                        {{ $device->status_name }}
                                    </span>
                                </td>
                                <td>{{ $device->received_date->format('d.m.Y') }}</td>
                                <td>{{ $device->repair_date ? $device->repair_date->format('d.m.Y') : '-' }}</td>
                                <td>
                                    <a href="{{ route('devices.show', $device) }}" class="btn btn-sm btn-info">Просмотр</a>
                                    <a href="{{ route('devices.edit', $device) }}" class="btn btn-sm btn-warning">Редактировать</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">Устройства не найдены</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $devices->links() }}
        </div>
    </div>
@endsection