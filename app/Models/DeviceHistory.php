<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceHistory extends Model
{
    protected $fillable = [
        'device_id',
        'status',
        'status_name',
        'changed_by',
        'comment',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            Device::STATUS_RECEIVED => 'secondary',
            Device::STATUS_DIAGNOSTICS => 'info',
            Device::STATUS_REPAIR => 'warning',
            Device::STATUS_DISASSEMBLED => 'danger',
            Device::STATUS_REPAIRED => 'success',
            default => 'secondary',
        };
    }
}