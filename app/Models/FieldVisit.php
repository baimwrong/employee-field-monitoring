<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FieldVisit extends Model
{
    use HasFactory;

    protected $fillable = [
        'visit_code',
        'user_id',
        'location_id',
        'geographical_area_id',
        'scheduled_at',
        'status',
        'purpose',
        'check_in_at',
        'check_in_lat',
        'check_in_lng',
        'check_in_distance_meters',
        'is_within_geofence',
        'check_in_photo',
        'check_out_at',
        'check_out_lat',
        'check_out_lng',
        'duration_minutes',
        'visit_summary',
        'client_representative_name',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'check_in_at' => 'datetime',
            'check_out_at' => 'datetime',
            'check_in_lat' => 'float',
            'check_in_lng' => 'float',
            'check_out_lat' => 'float',
            'check_out_lng' => 'float',
            'is_within_geofence' => 'boolean',
            'check_in_distance_meters' => 'integer',
            'duration_minutes' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function geographicalArea(): BelongsTo
    {
        return $this->belongsTo(GeographicalArea::class);
    }

    public function inspectionReports(): HasMany
    {
        return $this->hasMany(InspectionReport::class);
    }
}
