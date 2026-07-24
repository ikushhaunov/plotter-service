@extends('layouts.app')

@section('title', 'Список устройств')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Система учёта устройств</h1>
        <div class="d-flex gap-2">
            @if(!Auth::user()->isOtk())
                <a href="{{ route('devices.create') }}" class="btn btn-primary">Добавить устройство</a>
                
                @if(Auth::user()->isAdmin())
                    <form action="{{ route('sync.okdesk') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-success" onclick="return confirm('Начать синхронизацию с Okdesk?')">
                            🔄 Синхронизировать с Okdesk
                        </button>
                    </form>
                @endif
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <!-- Карточки со статусами -->
    <div class="row g-3 mb-4">
        <div class="col-md-6 col-lg">
            <a href="{{ route('devices.index', ['status' => 1]) }}" class="text-decoration-none">
                <div class="card border-secondary h-100 status-card">
                    <div class="card-body text-center">
                        <div class="h2 mb-1 text-secondary fw-bold">{{ $statusCounts['received'] }}</div>
                        <div class="text-muted small">Принято в ремонт</div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-6 col-lg">
            <a href="{{ route('devices.index', ['status' => 2]) }}" class="text-decoration-none">
                <div class="card border-info h-100 status-card">
                    <div class="card-body text-center">
                        <div class="h2 mb-1 text-info fw-bold">{{ $statusCounts['diagnostics'] }}</div>
                        <div class="text-muted small">На диагностике</div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-6 col-lg">
            <a href="{{ route('devices.index', ['status' => 3]) }}" class="text-decoration-none">
                <div class="card border-warning h-100 status-card">
                    <div class="card-body text-center">
                        <div class="h2 mb-1 text-warning fw-bold">{{ $statusCounts['repair'] }}</div>
                        <div class="text-muted small">В ремонте</div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-6 col-lg">
            <a href="{{ route('devices.index', ['status' => 6]) }}" class="text-decoration-none">
                <div class="card border-primary h-100 status-card">
                    <div class="card-body text-center">
                        <div class="h2 mb-1 text-primary fw-bold">{{ $statusCounts['otk'] }}</div>
                        <div class="text-muted small">На проверке ОТК</div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-6 col-lg">
            <a href="{{ route('devices.index', ['status' => 4]) }}" class="text-decoration-none">
                <div class="card border-danger h-100 status-card">
                    <div class="card-body text-center">
                        <div class="h2 mb-1 text-danger fw-bold">{{ $statusCounts['disassembled'] }}</div>
                        <div class="text-muted small">Списано</div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-6 col-lg">
            <a href="{{ route('devices.index', ['status' => 5]) }}" class="text-decoration-none">
                <div class="card border-success h-100 status-card">
                    <div class="card-body text-center">
                        <div class="h2 mb-1 text-success fw-bold">{{ $statusCounts['repaired'] }}</div>
                        <div class="text-muted small">Отремонтировано</div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- Кнопка экспорта -->
    <div class="mb-3">
        <a href="{{ route('export.qa-check') }}" class="btn btn-success">
            <i class="bi bi-file-earmark-excel"></i> Выгрузить ОТК в Excel
        </a>
    </div>

    <!-- Активный фильтр -->
    @if(request('status') || request('date_from') || request('date_to') || request('search') || request('employee_id'))
        <div class="alert alert-info d-flex justify-content-between align-items-center">
            <div>
                <strong>Активные фильтры:</strong> 
                @if(request('status'))
                    <span class="badge bg-{{ [1 => 'secondary', 2 => 'info', 3 => 'warning', 4 => 'danger', 5 => 'success', 6 => 'primary'][request('status')] ?? 'secondary' }}">
                        {{ \App\Models\Device::getStatuses()[request('status')] ?? 'Неизвестно' }}
                    </span>
                @endif
                @if(request('employee_id'))
                    @php $emp = $employees->firstWhere('id', request('employee_id')); @endphp
                    <span class="badge bg-dark ms-1">👤 {{ $emp->name ?? 'Неизвестный' }}</span>
                @endif
                @if(request('date_from') || request('date_to'))
                    <span class="badge bg-dark ms-1">
                        📅 {{ request('date_from', 'начало') }} — {{ request('date_to', 'сейчас') }}
                    </span>
                @endif
                <span class="ms-2">Найдено: {{ $devices->count() }}</span>
            </div>
            <a href="{{ route('devices.index') }}" class="btn btn-sm btn-outline-secondary">✕ Сбросить фильтры</a>
        </div>
    @endif

    <!-- Форма поиска и фильтров -->
    <form method="GET" action="{{ route('devices.index') }}" class="mb-4">
        <div class="row g-3">
            <div class="col-md-2">
                <input type="text" name="search" class="form-control" placeholder="Поиск по номеру" value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
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
            <div class="col-md-2">
                <select name="plotter_model_id" class="form-select">
                    <option value="">Все модели</option>
                    @foreach($plotterModels as $model)
                        <option value="{{ $model->id }}" {{ request('plotter_model_id') == $model->id ? 'selected' : '' }}>
                            {{ $model->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="employee_id" class="form-select">
                    <option value="">Все исполнители</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}" {{ request('employee_id') == $employee->id ? 'selected' : '' }}>
                            {{ $employee->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted">Дата с</label>
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted">Дата по</label>
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
            </div>
        </div>
        <div class="row g-3 mt-1">
            <div class="col-md-12">
                <button type="submit" class="btn btn-secondary">🔍 Фильтровать</button>
                <a href="{{ route('devices.index') }}" class="btn btn-outline-secondary">✕ Сбросить</a>
            </div>
        </div>
    </form>

    <!-- Таблица устройств -->
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Номер устройства</th>
                    <th>№ Заявки</th>
                    <th>Модель</th>
                    <th>Статус</th>
                    <th>Исполнитель</th>
                    <th>Дата приема</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                @forelse($devices as $device)
                    <tr>
                        <td>{{ $device->id }}</td>
                        <td>
                            <a href="{{ route('devices.show', $device) }}" class="text-decoration-none fw-bold">
                                {{ $device->device_number }}
                            </a>
                        </td>
                        <td>
                            @if($device->issue_number)
                                <a href="https://{{ config('services.okdesk.account') }}.okdesk.ru/issues/{{ $device->issue_number }}" 
                                   target="_blank" 
                                   class="badge bg-info text-decoration-none">
                                    {{ $device->issue_number }}
                                    <span class="ms-1">↗</span>
                                </a>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($device->plotterModel)
                                <span class="badge bg-dark">{{ $device->plotterModel->name }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-{{ $device->status_color }}">
                                {{ $device->status_name }}
                            </span>
                        </td>
                        <td>
                            @if($device->employee)
                                {{ $device->employee->name }}
                            @else
                                <span class="text-muted">Не назначен</span>
                            @endif
                        </td>
                        <td>{{ $device->received_date->format('d.m.Y') }}</td>
                        <td>
                            <div class="d-flex gap-1 flex-wrap">
                                
                                {{-- ОТЛАДКА: Показывает точные значения, чтобы понять, почему кнопка скрыта --}}
                                @if(Auth::user()->isMaster())
                                    <small class="d-block w-100 text-muted mb-1" style="font-size: 10px;">
                                        [Debug: Мой emp_id={{ Auth::user()->employee_id }}, Устр-во emp_id={{ $device->employee_id ?? 'NULL' }}, Статус={{ $device->status }}]
                                    </small>
                                @endif

                                {{-- 1. Кнопка "Взять в работу" (используем == null для максимальной надежности) --}}
                                @if(Auth::user()->isMaster() && $device->employee_id == null && in_array($device->status, [\App\Models\Device::STATUS_RECEIVED, \App\Models\Device::STATUS_DIAGNOSTICS]))
                                    <form action="{{ route('devices.take', $device) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Взять устройство #{{ $device->device_number }} в работу?')">
                                            ⚡ Взять в работу
                                        </button>
                                    </form>
                                @endif

                                {{-- 2. Кнопка "Просмотр" --}}
                                <a href="{{ route('devices.show', $device) }}" class="btn btn-sm btn-info">👁 Просмотр</a>

                                {{-- 3. Кнопка "Редактировать" --}}
                                @if(
                                    Auth::user()->isAdmin() ||
                                    (Auth::user()->isOtk() && $device->status == \App\Models\Device::STATUS_OTK) ||
                                    (Auth::user()->isMaster() && $device->employee_id == Auth::user()->employee_id)
                                )
                                    <a href="{{ route('devices.edit', $device) }}" class="btn btn-sm btn-warning">✏️ Изменить</a>
                                @endif

                                {{-- 4. Кнопка "Удалить" --}}
                                @if(Auth::user()->isAdmin())
                                    <form action="{{ route('devices.destroy', $device) }}" method="POST" class="d-inline" onsubmit="return confirm('Удалить устройство #{{ $device->device_number }}? Это действие необратимо!')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">🗑 Удалить</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            <div class="text-muted">Устройства не найдены</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection