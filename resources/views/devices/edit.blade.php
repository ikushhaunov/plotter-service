@extends('layouts.app')

@section('title', 'Редактировать устройство #' . $device->id)

@section('content')
    <h1>Редактировать устройство #{{ $device->id }}</h1>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('devices.update', $device) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Номер устройства *</label>
                    <input type="text" name="device_number" class="form-control" value="{{ old('device_number', $device->device_number) }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Номер заявки (Okdesk)</label>
                    <input type="text" name="issue_number" class="form-control" value="{{ old('issue_number', $device->issue_number) }}" placeholder="Например: 12345">
                    <small class="text-muted">Уникальный номер заявки из Okdesk</small>
                </div>

                <div class="mb-3">
                    <label class="form-label">Модель плоттера</label>
                    <select name="plotter_model_id" class="form-select">
                        <option value="">Не указана</option>
                        @foreach($plotterModels as $model)
                            <option value="{{ $model->id }}" {{ old('plotter_model_id', $device->plotter_model_id) == $model->id ? 'selected' : '' }}>
                                {{ $model->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Статус *</label>
                    <select name="status" class="form-select" required>
                        <option value="1" {{ $device->status == 1 ? 'selected' : '' }}>Принято в ремонт</option>
                        <option value="2" {{ $device->status == 2 ? 'selected' : '' }}>На диагностике</option>
                        <option value="3" {{ $device->status == 3 ? 'selected' : '' }}>В ремонте</option>
                        <option value="6" {{ $device->status == 6 ? 'selected' : '' }}>На проверке ОТК</option>
                        <option value="4" {{ $device->status == 4 ? 'selected' : '' }}>Списано/разукомплектовано</option>
                        <option value="5" {{ $device->status == 5 ? 'selected' : '' }}>Отремонтировано (на складе)</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Исполнитель</label>
                    <select name="employee_id" class="form-select">
                        <option value="">Не назначен</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" {{ $device->employee_id == $employee->id ? 'selected' : '' }}>
                                {{ $employee->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Дата приема *</label>
                    <input type="date" name="received_date" class="form-control" value="{{ old('received_date', $device->received_date->format('Y-m-d')) }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Дата ремонта</label>
                    <input type="date" name="repair_date" class="form-control" value="{{ old('repair_date', $device->repair_date?->format('Y-m-d')) }}">
                </div>

                <div class="mb-3">
                    <label class="form-label">Дата передачи на склад</label>
                    <input type="date" name="warehouse_date" class="form-control" value="{{ old('warehouse_date', $device->warehouse_date?->format('Y-m-d')) }}">
                </div>
            </div>

            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Описание неисправности *</label>
                    <textarea name="fault_description" class="form-control" rows="4" required>{{ old('fault_description', $device->fault_description) }}</textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Комментарий к изменению</label>
                    <textarea name="comment" class="form-control" rows="2" placeholder="Опишите, что было сделано при изменении статуса">{{ old('comment') }}</textarea>
                    <small class="text-muted">Этот комментарий будет сохранён в истории изменений</small>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header bg-light">
                <h6 class="mb-0">Заменённые запчасти</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Выберите запчасти из списка:</label>
                    <div class="border rounded p-3" style="max-height: 400px; overflow-y: auto;">
                        @foreach($parts as $part)
                            @php
                                $isSelected = $device->replaced_parts && str_contains($device->replaced_parts, $part->name);
                            @endphp
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="parts_list[]" value="{{ $part->id }}" id="part_{{ $part->id }}" {{ $isSelected ? 'checked' : '' }}>
                                <label class="form-check-label" for="part_{{ $part->id }}">
                                    {{ $part->name }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Дополнительные запчасти (вручную)</label>
                    <textarea name="replaced_parts_custom" class="form-control" rows="2" placeholder="Если нужны запчасти, которых нет в списке">{{ old('replaced_parts_custom') }}</textarea>
                </div>

                @if($device->replaced_parts)
                    <div class="alert alert-info">
                        <strong>Текущие запчасти:</strong><br>
                        <pre class="mb-0 mt-2">{{ $device->replaced_parts }}</pre>
                    </div>
                @endif
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Сохранить изменения</button>
        <a href="{{ route('devices.show', $device) }}" class="btn btn-secondary">Отмена</a>
    </form>
@endsection