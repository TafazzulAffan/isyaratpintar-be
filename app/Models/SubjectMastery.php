<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubjectMastery extends Model
{
    protected $table = 'subject_mastery';

    protected $fillable = [
        'user_id',
        'mata_pelajaran_id',
        'mastery_percentage',
        'average_score',
        'assessments_completed',
        'status',
        'last_assessed_at',
        'calculated_at',
    ];

    protected $casts = [
        'mastery_percentage' => 'decimal:2',
        'average_score' => 'decimal:2',
        'last_assessed_at' => 'datetime',
        'calculated_at' => 'datetime',
    ];

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the mata pelajaran
     */
    public function mataPelajaran(): BelongsTo
    {
        return $this->belongsTo(MataPelajaran::class);
    }

    /**
     * Scope: Get all subjects with good mastery
     */
    public function scopeExcellent($query)
    {
        return $query->where('status', 'excellent');
    }

    /**
     * Scope: Get all subjects needing help
     */
    public function scopeNeedsHelp($query)
    {
        return $query->whereIn('status', ['needs_help', 'poor']);
    }

    /**
     * Scope: Get for specific user
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Get human-readable status
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'excellent' => 'Sangat Baik (80-100)',
            'good' => 'Baik (60-79)',
            'needs_help' => 'Perlu Bantuan (40-59)',
            'poor' => 'Perlu Perbaikan (0-39)',
            default => $this->status,
        };
    }
}
