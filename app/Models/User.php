<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'employee_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Константы ролей
    const ROLE_ADMIN = 'admin';
    const ROLE_MASTER = 'master';
    const ROLE_OTK = 'otk';

    // Проверки ролей
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isMaster(): bool
    {
        return $this->role === self::ROLE_MASTER;
    }

    public function isOtk(): bool
    {
        return $this->role === self::ROLE_OTK;
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}