@extends('layouts.app')

@section('title', 'Аналитика')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>📊 Аналитика и статистика</h1>
        <a href="{{ route('devices.index') }}" class="btn btn-secondary">← К устройствам</a>
    </div>

    <!-- Фильтр по году и месяцу -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('analytics.index') }}" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Год</label>
                    <select name="year" class="form-select">
                        @foreach($availableYears as $y)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Месяц</label>
                    <select name="month" class="form-select">
                        @foreach($monthNames as $num => $name)
                            <option value="{{ $num }}" {{ $month == $num ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100">Показать</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Статистика за выбранный месяц -->
    <div class="row g-3 mb-4">
        <div class="col-md-2">
            <div class="card border-dark h-100">
                <div class="card-body text-center">
                    <div class="h3 mb-1 fw-bold">{{ $monthStats['total'] }}</div>
                    <div class="text-muted small">Всего за месяц</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-secondary h-100">
                <div class="card-body text-center">
                    <div class="h3 mb-1 text-secondary fw-bold">{{ $monthStats['received'] }}</div>
                    <div class="text-muted small">Принято</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-info h-100">
                <div class="card-body text-center">
                    <div class="h3 mb-1 text-info fw-bold">{{ $monthStats['diagnostics'] }}</div>
                    <div class="text-muted small">Диагностика</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-warning h-100">
                <div class="card-body text-center">
                    <div class="h3 mb-1 text-warning fw-bold">{{ $monthStats['repair'] }}</div>
                    <div class="text-muted small">В ремонте</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-primary h-100">
                <div class="card-body text-center">
                    <div class="h3 mb-1 text-primary fw-bold">{{ $monthStats['otk'] }}</div>
                    <div class="text-muted small">ОТК</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-success h-100">
                <div class="card-body text-center">
                    <div class="h3 mb-1 text-success fw-bold">{{ $monthStats['repaired'] }}</div>
                    <div class="text-muted small">Отремонтировано</div>
                </div>
            </div>
        </div>
    </div>

    <!-- График по месяцам -->
    <div class="card mb-4">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0"> Динамика по месяцам ({{ $year }} год)</h5>
        </div>
        <div class="card-body">
            <canvas id="monthlyChart" height="100"></canvas>
        </div>
    </div>

    <!-- График распределения статусов -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"> Распределение статусов за {{ $monthNames[$month] }}</h5>
                </div>
                <div class="card-body">
                    <canvas id="statusPieChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">👷 Загрузка сотрудников за {{ $monthNames[$month] }}</h5>
                </div>
                <div class="card-body">
                    <canvas id="employeeChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Таблица по сотрудникам -->
    <div class="card">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">👥 Статистика по сотрудникам за {{ $monthNames[$month] }} {{ $year }}</h5>
        </div>
        <div class="card-body">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Сотрудник</th>
                        <th class="text-center">Всего</th>
                        <th class="text-center">Отремонтировано</th>
                        <th class="text-center">В работе</th>
                        <th class="text-center">Эффективность</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($employeeStats as $stat)
                        <tr>
                            <td><strong>{{ $stat['name'] }}</strong></td>
                            <td class="text-center">{{ $stat['total'] }}</td>
                            <td class="text-center text-success">{{ $stat['repaired'] }}</td>
                            <td class="text-center text-warning">{{ $stat['in_repair'] }}</td>
                            <td class="text-center">
                                @if($stat['total'] > 0)
                                    @php $efficiency = round(($stat['repaired'] / $stat['total']) * 100) @endphp
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar bg-success" style="width: {{ $efficiency }}%">
                                            {{ $efficiency }}%
                                        </div>
                                    </div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // График по месяцам
    const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
    new Chart(monthlyCtx, {
        type: 'line',
        data: {
            labels: @json($labels),
            datasets: [
                {
                    label: 'Всего',
                    data: @json($totals),
                    borderColor: '#212529',
                    backgroundColor: 'rgba(33, 37, 41, 0.1)',
                    tension: 0.3,
                    fill: true
                },
                {
                    label: 'Принято',
                    data: @json($receivedData),
                    borderColor: '#6c757d',
                    backgroundColor: 'rgba(108, 117, 125, 0.1)',
                    tension: 0.3
                },
                {
                    label: 'В ремонте',
                    data: @json($repairData),
                    borderColor: '#ffc107',
                    backgroundColor: 'rgba(255, 193, 7, 0.1)',
                    tension: 0.3
                },
                {
                    label: 'Отремонтировано',
                    data: @json($repairedData),
                    borderColor: '#198754',
                    backgroundColor: 'rgba(25, 135, 84, 0.1)',
                    tension: 0.3
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'top' }
            },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });

    // Круговая диаграмма статусов
    const pieCtx = document.getElementById('statusPieChart').getContext('2d');
    new Chart(pieCtx, {
        type: 'doughnut',
        data: {
            labels: ['Принято', 'Диагностика', 'В ремонте', 'ОТК', 'Списано', 'Отремонтировано'],
            datasets: [{
                data: [
                    {{ $monthStats['received'] }},
                    {{ $monthStats['diagnostics'] }},
                    {{ $monthStats['repair'] }},
                    {{ $monthStats['otk'] }},
                    {{ $monthStats['disassembled'] }},
                    {{ $monthStats['repaired'] }}
                ],
                backgroundColor: ['#6c757d', '#0dcaf0', '#ffc107', '#0d6efd', '#dc3545', '#198754']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });

    // График по сотрудникам
    const empCtx = document.getElementById('employeeChart').getContext('2d');
    new Chart(empCtx, {
        type: 'bar',
        data: {
            labels: @json($employeeStats->pluck('name')),
            datasets: [
                {
                    label: 'Отремонтировано',
                    data: @json($employeeStats->pluck('repaired')),
                    backgroundColor: '#198754'
                },
                {
                    label: 'В работе',
                    data: @json($employeeStats->pluck('in_repair')),
                    backgroundColor: '#ffc107'
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                x: { stacked: true },
                y: { stacked: true, beginAtZero: true, ticks: { stepSize: 1 } }
            },
            plugins: {
                legend: { position: 'top' }
            }
        }
    });
</script>
@endsection