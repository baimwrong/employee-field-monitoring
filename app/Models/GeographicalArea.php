<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GeographicalArea extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'city',
        'province_state',
        'center_lat',
        'center_lng',
        'radius_meters',
        'boundary_geojson',
        'color_code',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'center_lat' => 'float',
            'center_lng' => 'float',
            'radius_meters' => 'integer',
            'is_active' => 'boolean',
            'boundary_geojson' => 'array',
        ];
    }

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'area_employee')
            ->withPivot('role_in_area', 'assigned_date', 'expiry_date', 'is_primary', 'assignment_notes')
            ->withTimestamps();
    }

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    public function fieldVisits(): HasMany
    {
        return $this->hasMany(FieldVisit::class);
    }
}
