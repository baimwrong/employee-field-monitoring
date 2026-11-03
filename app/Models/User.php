<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'employee_code',
        'phone',
        'role',
        'status',
        'avatar',
        'current_latitude',
        'current_longitude',
        'last_active_at',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_active_at' => 'datetime',
            'password' => 'hashed',
            'current_latitude' => 'float',
            'current_longitude' => 'float',
        ];
    }

    public function geographicalAreas(): BelongsToMany
    {
        return $this->belongsToMany(GeographicalArea::class, 'area_employee')
            ->withPivot('role_in_area', 'assigned_date', 'expiry_date', 'is_primary', 'assignment_notes')
            ->withTimestamps();
    }

    public function fieldVisits(): HasMany
    {
        return $this->hasMany(FieldVisit::class);
    }

    public function inspectionReports(): HasMany
    {
        return $this->hasMany(InspectionReport::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isSupervisor(): bool
    {
        return $this->role === 'supervisor';
    }

    public function isFieldOfficer(): bool
    {
        return $this->role === 'field_officer';
    }

    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }
}
