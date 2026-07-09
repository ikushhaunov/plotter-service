@extends('layouts.app')

@section('title', 'Устройство #' . $device->id)

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Устройство #{{ $device->id }}</h1>
        <div>
            <a href="{{ route('devices.edit', $device) }}" class="btn btn-warning">Редактировать</a>
            <a href="{{ route('devices.index') }}" class="btn btn-secondary">Назад к списку</a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Основная информация</h5>
                </div>
                <div class="card-body">
                    <h5 class="card-title">
                        Номер: {{ $device->device_number }}
                        <span class="badge bg-{{ $device->status_color }} ms-2">{{ $device->status_name }}</span>
                    </h5>

                    <td>
    @if($device->issue_number)
    <div class="mb-3">
        <a href="https://{{ config('services.okdesk.account') }}.okdesk.ru/issues/{{ $device->issue_number }}" 
           target="_blank" 
           class="badge bg-info fs-6 text-decoration-none">
            Заявка Okdesk: {{ $device->issue_number }}
            <span class="ms-1">↗</span>
        </a>
    </div>
@endif

                    @if($device->plotterModel)
                        <div class="mb-3">
                            <span class="badge bg-dark fs-6">Модель: {{ $device->plotterModel->name }}</span>
                        </div>
                    @endif
                    
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <h6>Информация:</h6>
                            <ul class="list-unstyled">
                                <li><strong>Исполнитель:</strong> 
                                    @if($device->employee)
                                        <a href="{{ route('employees.show', $device->employee) }}">{{ $device->employee->name }}</a>
                                    @else
                                        Не назначен
                                    @endif
                                </li>
                                <li><strong>Дата приема:</strong> {{ $device->received_date->format('d.m.Y') }}</li>
                                <li><strong>Дата ремонта:</strong> {{ $device->repair_date ? $device->repair_date->format('d.m.Y') : 'Не указано' }}</li>
                                <li><strong>Дата передачи на склад:</strong> {{ $device->warehouse_date ? $device->warehouse_date->format('d.m.Y') : 'Не указано' }}</li>
                            </ul>
                        </div>
                    </div>

                    <div class="mt-4">
                        <h6>Описание неисправности:</h6>
                        <p class="bg-light p-3 rounded">{{ $device->fault_description }}</p>
                    </div>

                    @if($device->replaced_parts)
                        <div class="mt-4">
                            <h6>Замененные запчасти:</h6>
                            <div class="bg-light p-3 rounded">
                                @foreach(explode("\n", $device->replaced_parts) as $part)
                                    @if(trim($part))
                                        <div class="mb-1">
                                            <span class="badge bg-secondary me-1">✓</span>
                                            {{ trim($part) }}
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">История изменений</h5>
                </div>
                <div class="card-body">
                    @if($device->histories->count() > 0)
                        <div class="timeline">
                            @foreach($device->histories as $history)
                                <div class="timeline-item mb-3 p-3 border rounded">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <span class="badge bg-{{ $history->status_color }} me-2">
                                                {{ $history->status_name }}
                                            </span>
                                            @if($history->comment)
                                                <span class="text-muted">{{ $history->comment }}</span>
                                            @endif
                                        </div>
                                        <small class="text-muted">
                                            {{ $history->created_at->format('d.m.Y H:i') }}
                                        </small>
                                    </div>
                                    @if($history->changedBy)
                                        <small class="text-muted d-block mt-1">
                                            Изменил: {{ $history->changedBy->name }}
                                        </small>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted text-center mb-0">История изменений пуста</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Быстрые действия</h5>
                </div>
                <div class="card-body">
                    <a href="{{ route('devices.edit', $device) }}" class="btn btn-warning w-100 mb-2">
                        Изменить статус
                    </a>
                    <form action="{{ route('devices.destroy', $device) }}" method="POST" onsubmit="return confirm('Удалить устройство?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger w-100">Удалить устройство</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection