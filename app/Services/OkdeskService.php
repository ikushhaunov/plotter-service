<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OkdeskService
{
    private $apiToken;
    private $account;
    private $baseUrl;

    public function __construct()
    {
        $this->apiToken = config('services.okdesk.api_token');
        $this->account = config('services.okdesk.account');
        $this->baseUrl = "https://{$this->account}.okdesk.ru/api/v1/";
    }

    /**
     * Получить список всех статусов заявок
     */
    public function getStatuses(): array
    {
        $response = Http::get($this->baseUrl . 'statuses', [
            'api_token' => $this->apiToken,
        ]);

        Log::info('Okdesk getStatuses', [
            'url' => $this->baseUrl . 'statuses',
            'status' => $response->status(),
        ]);

        if ($response->successful()) {
            return $response->json() ?? [];
        }

        Log::error('Okdesk API Error (getStatuses): ' . $response->body());
        return [];
    }

    /**
     * Получить список заявок с фильтрацией
     */
    public function getIssues(array $filters = []): array
    {
        $params = [
            'api_token' => $this->apiToken,
            'limit' => 100,
        ];

        if (!empty($filters['status_codes'])) {
            $params['status_codes'] = (array) $filters['status_codes'];
        }

        if (!empty($filters['assignee_ids'])) {
            $params['assignee_ids'] = (array) $filters['assignee_ids'];
        }

        $response = Http::get($this->baseUrl . 'issues', $params);

        Log::info('Okdesk getIssues', [
            'status' => $response->status(),
        ]);

        if ($response->successful()) {
            return $response->json() ?? [];
        }

        Log::error('Okdesk API Error (getIssues): ' . $response->body());
        return [];
    }

    /**
     * Получить детали конкретной заявки
     */
    public function getIssue(int $issueId): array
    {
        $response = Http::get($this->baseUrl . "issues/{$issueId}", [
            'api_token' => $this->apiToken,
        ]);

        if ($response->successful()) {
            return $response->json() ?? [];
        }

        Log::error("Okdesk API Error (getIssue #{$issueId}): " . $response->body());
        return [];
    }

    /**
     * Получить список исполнителей
     */
    public function getEmployees(): array
    {
        $response = Http::get($this->baseUrl . 'employees', [
