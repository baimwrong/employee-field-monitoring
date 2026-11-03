<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action_type',
        'title',
        'details',
        'subject_type',
        'subject_id',
        'latitude',
        'longitude',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function log(string $actionType, string $title, ?string $details = null, ?Model $subject = null, ?User $user = null, ?float $lat = null, ?float $lng = null): self
    {
        return self::create([
            'user_id' => $user?->id ?? auth()->id(),
            'action_type' => $actionType,
            'title' => $title,
            'details' => $details,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->id,
            'latitude' => $lat,
            'longitude' => $lng,
            'ip_address' => request()->ip(),
        ]);
    }
}
