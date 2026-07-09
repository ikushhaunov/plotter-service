@extends('layouts.app')

@section('title', 'Добавить устройство')

@section('content')
    <h1>Добавить устройство</h1>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('devices.store') }}" method="POST">
        @csrf
        
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Номер устройства *</label>
                    <input type="text" name="device_number" class="form-control" value="{{ old('device_number') }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Номер заявки (Okdesk)</label>
                    <input type="text" name="issue_number" class="form-control" value="{{ old('issue_number') }}" placeholder="Например: 12345">
                    <small class="text-muted">Уникальный номер заявки. Если оставить пустым — будет сгенерирован автоматически</small>
                </div>

                <div class="mb-3">
                    <label class="form-label">Модель плоттера</label>
                    <select name="plotter_model_id" class="form-select">
                        <option value="">Не указана</option>
                        @foreach($plotterModels as $model)
                            <option value="{{ $model->id }}" {{ old('plotter_model_id') == $model->id ? 'selected' : '' }}>
                                {{ $model->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Дата приема *</label>
                    <input type="date" name="received_date" class="form-control" value="{{ old('received_date', date('Y-m-d')) }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Исполнитель</label>
                    <select name="employee_id" class="form-select">
                        <option value="">Не назначен</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" {{ old('employee_id') == $employee->id ? 'selected' : '' }}>
                                {{ $employee->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Описание неисправности *</label>
                    <textarea name="fault_description" class="form-control" rows="4" required>{{ old('fault_description') }}</textarea>
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
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="parts_list[]" value="{{ $part->id }}" id="part_{{ $part->id }}" {{ in_array($part->id, old('parts_list', [])) ? 'checked' : '' }}>
                                <label class="form-check-label" for="part_{{ $part->id }}">
                                    {{ $part->name }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Или введите вручную (если нужной запчасти нет в списке)</label>
                    <textarea name="replaced_parts_custom" class="form-control" rows="2" placeholder="Например: Конденсатор 100мкФ, Резистор 1кОм">{{ old('replaced_parts_custom') }}</textarea>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Добавить</button>
        <a href="{{ route('devices.index') }}" class="btn btn-secondary">Отмена</a>
    </form>
@endsection