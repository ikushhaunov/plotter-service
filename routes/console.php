<?php

use Illuminate\Support\Facades\Schedule;

// Автоматическая синхронизация с Okdesk каждые 4 часа
Schedule::command('sync:okdesk')->everyFourHours();