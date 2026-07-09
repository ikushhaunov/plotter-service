<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Система учёта устройств')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    .status-card {
        transition: all 0.2s ease;
        cursor: pointer;
    }
    .status-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
</style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="{{ route('devices.index') }}">Сервисный центр</a>
<li class="nav-item">
    <a class="nav-link" href="{{ route('analytics.index') }}">📊 Аналитика</a>
</li>
            <div class="navbar-nav ms-auto">
                @auth
                    <a href="{{ route('devices.index') }}" class="btn btn-outline-light btn-sm me-2">Устройства</a>
                    <a href="{{ route('stats.index') }}" class="btn btn-outline-light btn-sm me-2">Статистика</a>
                    <span class="navbar-text text-light me-3">{{ Auth::user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-outline-light btn-sm">Выйти</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="btn btn-outline-light btn-sm">Войти</a>
                @endauth
            </div>
        </div>
    </nav>

    <div class="container">
        @yield('content')
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
@yield('scripts')
</body>
</html>