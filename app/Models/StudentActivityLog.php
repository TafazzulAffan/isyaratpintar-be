<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'activity_type',
        'entity_type',
        'entity_id',
        'metadata',
        'logged_at',
    ];

    protected $casts = [
        'metadata' => 'json',
        'logged_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that performed the activity
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: Get activities for a specific user
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Get activities of a specific type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('activity_type', $type);
    }

    /**
     * Scope: Get activities within a date range
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('logged_at', [$startDate, $endDate]);
    }

    /**
     * Scope: Get recent activities
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('logged_at', '>=', now()->subDays($days));
    }

    /**
     * Get human-readable activity type name
     */
    public function getActivityTypeLabel(): string
    {
        return match ($this->activity_type) {
            'lesson_viewed' => 'Pelajaran Dilihat',
            'lesson_completed' => 'Pelajaran Selesai',
            'assessment_started' => 'Ujian Dimulai',
            'answer_submitted' => 'Jawaban Dikirim',
            'attempt_completed' => 'Ujian Selesai',
            'attempt_timed_out' => 'Ujian Habis Waktu',
            'session_started' => 'Session Dimulai',
            'session_ended' => 'Session Berakhir',
            'page_viewed' => 'Halaman Dilihat',
            'task_submitted' => 'Tugas Dikirim',
            default => $this->activity_type,
        };
    }
}
