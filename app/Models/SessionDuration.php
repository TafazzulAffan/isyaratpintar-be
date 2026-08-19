<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionDuration extends Model
{
    protected $fillable = [
        'user_id',
        'started_at',
        'ended_at',
        'duration_seconds',
        'session_type',
        'entity_id',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user for this session
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Calculate duration in seconds when session ends
     */
    public static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            if ($model->started_at && $model->ended_at) {
                $model->duration_seconds = $model->ended_at->diffInSeconds($model->started_at);
            }
        });
    }

    /**
     * Scope: Get sessions for a specific user
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Get completed sessions
     */
    public function scopeCompleted($query)
    {
        return $query->whereNotNull('ended_at');
    }

    /**
     * Scope: Get active sessions
     */
    public function scopeActive($query)
    {
        return $query->whereNull('ended_at');
    }

    /**
     * Scope: Get sessions by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('session_type', $type);
    }

    /**
     * Scope: Get sessions within a date range
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('started_at', [$startDate, $endDate]);
    }

    /**
     * Get duration formatted as HH:MM:SS
     */
    public function getDurationFormatted(): string
    {
        $seconds = $this->duration_seconds;
        $hours = intval($seconds / 3600);
        $minutes = intval(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
    }

    /**
     * Get duration in minutes
     */
    public function getDurationMinutes(): float
    {
        return $this->duration_seconds / 60;
    }
}
