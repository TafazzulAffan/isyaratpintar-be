<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentRiskProfile extends Model
{
    protected $fillable = [
        'user_id',
        'attendance_risk',
        'performance_risk',
        'engagement_risk',
        'subject_mastery_risk',
        'overall_risk_score',
        'waspas_score',
        'wsm_score',
        'wpm_score',
        'waspas_rank',
        'risk_level',
        'struggling_subjects',
        'weak_topics',
        'recommendations',
        'calculated_at',
    ];

    protected $casts = [
        'attendance_risk' => 'decimal:2',
        'performance_risk' => 'decimal:2',
        'engagement_risk' => 'decimal:2',
        'subject_mastery_risk' => 'decimal:2',
        'overall_risk_score' => 'decimal:2',
        'waspas_score' => 'decimal:6',
        'wsm_score' => 'decimal:6',
        'wpm_score' => 'decimal:6',
        'waspas_rank' => 'integer',
        'struggling_subjects' => 'json',
        'weak_topics' => 'json',
        'recommendations' => 'json',
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
     * Get teacher insights for this student
     */
    public function teacherInsights(): HasMany
    {
        return $this->hasMany(TeacherInsight::class, 'student_id', 'user_id');
    }

    /**
     * Scope: Get students at critical risk
     */
    public function scopeCritical($query)
    {
        return $query->where('risk_level', 'critical');
    }

    /**
     * Scope: Get students at high risk
     */
    public function scopeHighRisk($query)
    {
        return $query->whereIn('risk_level', ['high', 'critical']);
    }

    /**
     * Scope: Get students with low risk
     */
    public function scopeLowRisk($query)
    {
        return $query->where('risk_level', 'low');
    }

    /**
     * Get human-readable risk level
     */
    public function getRiskLevelLabel(): string
    {
        return match ($this->risk_level) {
            'critical' => 'Sangat Kritis - Memerlukan Intervensi Segera',
            'high' => 'Tinggi - Perlu Perhatian',
            'medium' => 'Sedang - Pantau',
            'low' => 'Rendah - Sedang Berkembang Baik',
            default => $this->risk_level,
        };
    }

    /**
     * Get risk percentage representation
     */
    public function getRiskPercentage(): float
    {
        return min(100, round($this->overall_risk_score, 2));
    }

    /**
     * Check if student needs immediate attention
     */
    public function needsImmediateAttention(): bool
    {
        return $this->risk_level === 'critical' || $this->overall_risk_score >= 75;
    }
}
