<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\OkdeskService;

class OkdeskListIssues extends Command
{
    protected $signature = 'okdesk:list-issues {status?}';
    protected $description = 'Показать список заявок из Okdesk (опционально по статусу)';

    public function handle()
    {
        $statusCode = $this->argument('status');
        
        $this->info('Получаю список заявок из Okdesk...');
        if ($statusCode) {
            $this->info("Фильтр по статусу: {$statusCode}");
        }

        try {
            $service = new OkdeskService();
            
            $filters = [];
            if ($statusCode) {
                $filters['status_codes'] = [$statusCode];
            }
            
            $issues = $service->getIssues($filters);

            if (empty($issues)) {
                $this->warn('Заявки не найдены.');
                return;
            }

            $this->info("\nНайдено заявок: " . count($issues));
            $this->info(str_repeat('-', 100));

            foreach ($issues as $issue) {
                $this->line(sprintf(
                    "ID: %s | Номер: <comment>%s</comment> | Статус: %s | Тема: %s",
                    $issue['id'] ?? 'N/A',
                    $issue['number'] ?? 'N/A',
                    $issue['status']['name'] ?? 'N/A',
                    mb_substr($issue['subject'] ?? 'N/A', 0, 40)
                ));

                if (!empty($issue['equipment'])) {
                    foreach ($issue['equipment'] as $equipment) {
                        $this->line(sprintf(
                            "   └─ Оборудование: %s | Модель: %s | Серийный: %s",
                            $equipment['equipment_kind']['name'] ?? 'N/A',
                            $equipment['equipment_model']['name'] ?? 'N/A',
                            $equipment['serial_number'] ?? 'N/A'
                        ));
                    }
                }
            }

            $this->info(str_repeat('-', 100));

        } catch (\Exception $e) {
            $this->error('Ошибка: ' . $e->getMessage());
        }
    }
}