@extends('layouts.app')

@section('title', 'Статистика')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Статистика по сотрудникам</h1>
        <a href="{{ route('devices.index') }}" class="btn btn-secondary">Назад к устройствам</a>
    </div>

    <!-- Общая статистика -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Общая статистика</h5>
        </div>
        <div class="card-body">
            <div class="row text-center">
                <div class="col">
                    <div class="h3 mb-1">{{ $totalStats['total'] }}</div>
                    <div class="text-muted">Всего</div>
                </div>
                <div class="col">
                    <div class="h3 mb-1 text-secondary">{{ $totalStats['received'] }}</div>
                    <div class="text-muted">Принято</div>
                </div>
                <div class="col">
                    <div class="h3 mb-1 text-info">{{ $totalStats['diagnostics'] }}</div>
                    <div class="text-muted">Диагностика</div>
                </div>
                <div class="col">
                    <div class="h3 mb-1 text-warning">{{ $totalStats['in_repair'] }}</div>
                    <div class="text-muted">В ремонте</div>
                </div>
                <div class="col">
                    <div class="h3 mb-1 text-primary">{{ $totalStats['otk'] }}</div>
                    <div class="text-muted">На проверке ОТК</div>
                </div>
                <div class="col">
                    <div class="h3 mb-1 text-danger">{{ $totalStats['disassembled'] }}</div>
                    <div class="text-muted">Списано</div>
                </div>
                <div class="col">
                    <div class="h3 mb-1 text-success">{{ $totalStats['repaired'] }}</div>
                    <div class="text-muted">На складе</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Статистика по сотрудникам -->
    <div class="card">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">Статистика по сотрудникам</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Сотрудник</th>
                            <th class="text-center">Принято</th>
                            <th class="text-center">Диагностика</th>
                            <th class="text-center">В ремонте</th>
                            <th class="text-center">ОТК</th>
                            <th class="text-center">Списано</th>
                            <th class="text-center">На складе</th>
                            <th class="text-center">Всего</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($stats as $stat)
                            <tr>
                                <td>
                                    <a href="{{ route('employees.show', $stat['employee']) }}" class="text-decoration-none fw-bold">
                                        {{ $stat['employee']->name }}
                                    </a>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('employees.show', [$stat['employee'], 'status' => 1]) }}" class="text-decoration-none">
                                        <span class="badge bg-secondary fs-6">{{ $stat['received'] }}</span>
                                    </a>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('employees.show', [$stat['employee'], 'status' => 2]) }}" class="text-decoration-none">
                                        <span class="badge bg-info fs-6">{{ $stat['diagnostics'] }}</span>
                                    </a>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('employees.show', [$stat['employee'], 'status' => 3]) }}" class="text-decoration-none">
                                        <span class="badge bg-warning fs-6">{{ $stat['in_repair'] }}</span>
                                    </a>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('employees.show', [$stat['employee'], 'status' => 6]) }}" class="text-decoration-none">
                                        <span class="badge bg-primary fs-6">{{ $stat['otk'] }}</span>
                                    </a>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('employees.show', [$stat['employee'], 'status' => 4]) }}" class="text-decoration-none">
                                        <span class="badge bg-danger fs-6">{{ $stat['disassembled'] }}</span>
                                    </a>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('employees.show', [$stat['employee'], 'status' => 5]) }}" class="text-decoration-none">
                                        <span class="badge bg-success fs-6">{{ $stat['repaired'] }}</span>
                                    </a>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('employees.show', $stat['employee']) }}" class="text-decoration-none">
                                        <span class="badge bg-dark fs-6">{{ $stat['total'] }}</span>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection