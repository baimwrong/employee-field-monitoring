<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'geographical_area_id',
        'name',
        'code',
        'client_name',
        'contact_person',
        'contact_phone',
        'address',
        'latitude',
        'longitude',
        'geofence_radius_meters',
        'location_type',
        'priority',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'geofence_radius_meters' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function geographicalArea(): BelongsTo
    {
        return $this->belongsTo(GeographicalArea::class);
    }

    public function fieldVisits(): HasMany
    {
        return $this->hasMany(FieldVisit::class);
    }

    public function inspectionReports(): HasMany
    {
        return $this->hasMany(InspectionReport::class);
    }
}
