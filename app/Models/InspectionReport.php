<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InspectionReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_number',
        'field_visit_id',
        'location_id',
        'user_id',
        'title',
        'category',
        'inspection_date',
        'compliance_score',
        'result_grade',
        'status',
        'executive_summary',
        'major_findings',
        'corrective_recommendations',
        'reviewed_by',
        'reviewed_at',
        'reviewer_remarks',
    ];

    protected function casts(): array
    {
        return [
            'inspection_date' => 'date',
            'reviewed_at' => 'datetime',
            'compliance_score' => 'float',
        ];
    }

    public function fieldVisit(): BelongsTo
    {
        return $this->belongsTo(FieldVisit::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InspectionItem::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(InspectionPhoto::class);
    }
}
