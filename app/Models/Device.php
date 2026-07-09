<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    protected $fillable = [
        'device_number',
        'issue_number',
        'plotter_model_id',
        'status',
        'employee_id',
        'received_date',
        'repair_date',
        'fault_description',
        'replaced_parts',
        'warehouse_date',
    ];

    protected $casts = [
        'received_date' => 'date',
        'repair_date' => 'date',
        'warehouse_date' => 'date',
    ];

    const STATUS_RECEIVED = 1;
    const STATUS_DIAGNOSTICS = 2;
    const STATUS_REPAIR = 3;
    const STATUS_OTK = 6;
    const STATUS_DISASSEMBLED = 4;
    const STATUS_REPAIRED = 5;

    public static function getStatuses(): array
    {
        return [
            self::STATUS_RECEIVED => 'Принято в ремонт',
            self::STATUS_DIAGNOSTICS => 'На диагностике',
            self::STATUS_REPAIR => 'В ремонте',
            self::STATUS_OTK => 'На проверке ОТК',
            self::STATUS_DISASSEMBLED => 'Списано/разукомплектовано',
            self::STATUS_REPAIRED => 'Отремонтировано (на складе)',
        ];
    }

    public function getStatusNameAttribute(): string
    {
        return self::getStatuses()[$this->status] ?? 'Неизвестно';
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_RECEIVED => 'secondary',
            self::STATUS_DIAGNOSTICS => 'info',
            self::STATUS_REPAIR => 'warning',
            self::STATUS_OTK => 'primary',
            self::STATUS_DISASSEMBLED => 'danger',
            self::STATUS_REPAIRED => 'success',
            default => 'secondary',
        };
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function plotterModel()
    {
        return $this->belongsTo(PlotterModel::class);
    }

    public function histories()
    {
        return $this->hasMany(DeviceHistory::class)->orderBy('created_at', 'desc');
    }

    public function latestHistory()
    {
        return $this->hasOne(DeviceHistory::class)->latestOfMany();
    }

    public function addHistory(int $status, ?int $userId = null, ?string $comment = null): void
    {
        DeviceHistory::create([
            'device_id' => $this->id,
            'status' => $status,
            'status_name' => self::getStatuses()[$status] ?? 'Неизвестно',
            'changed_by' => $userId,
            'comment' => $comment,
        ]);
    }
}