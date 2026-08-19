<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherInsight extends Model
{
    protected $fillable = [
        'teacher_id',
        'student_id',
        'insight_type',
        'message',
        'supporting_data',
        'teacher_acknowledged',
        'acknowledged_at',
    ];

    protected $casts = [
        'supporting_data' => 'json',
        'teacher_acknowledged' => 'boolean',
        'acknowledged_at' => 'datetime',
    ];

    /**
     * Get the teacher
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Get the student
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Scope: Get unacknowledged insights
     */
    public function scopeUnacknowledged($query)
    {
        return $query->where('teacher_acknowledged', false);
    }

    /**
     * Scope: Get insights of specific type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('insight_type', $type);
    }

    /**
     * Scope: Get insights for specific student
     */
    public function scopeForStudent($query, int $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    /**
     * Get human-readable insight type
     */
    public function getTypeLabel(): string
    {
        return match ($this->insight_type) {
            'needs_attention' => 'Memerlukan Perhatian',
            'excelling' => 'Menunjukkan Prestasi',
            'disengaged' => 'Kurang Terlibat',
            'at_risk' => 'Dalam Risiko',
            default => $this->insight_type,
        };
    }

    /**
     * Mark insight as acknowledged by teacher
     */
    public function acknowledge(): void
    {
        $this->update([
            'teacher_acknowledged' => true,
            'acknowledged_at' => now(),
        ]);
    }
}
