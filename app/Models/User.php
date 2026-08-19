<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'username',
        'password',
        'role',
        'is_active',
        'profile_photo_path',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'role' => UserRole::class,
        'is_active' => 'boolean',
    ];

    public function lessons(): BelongsToMany
    {
        return $this->belongsToMany(Lesson::class, 'user_lessons')
            ->withPivot(['completed', 'completed_at'])
            ->withTimestamps();
    }

    public function completedLessons(): BelongsToMany
    {
        return $this->belongsToMany(Lesson::class, 'user_lessons')
            ->wherePivot('completed', true)
            ->withPivot(['completed', 'completed_at'])
            ->withTimestamps();
    }

    public function caseProgress(): HasMany
    {
        return $this->hasMany(UserCaseProgress::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(CaseSubmission::class);
    }

    public function assessmentAttempts(): HasMany
    {
        return $this->hasMany(AssessmentAttempt::class);
    }

    public function resumes(): HasMany
    {
        return $this->hasMany(UserResume::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(StudentActivityLog::class);
    }

    public function sessionDurations(): HasMany
    {
        return $this->hasMany(SessionDuration::class);
    }

    public function subjectMastery(): HasMany
    {
        return $this->hasMany(SubjectMastery::class);
    }

    public function riskProfile()
    {
        return $this->hasOne(StudentRiskProfile::class);
    }

    public function teacherInsights(): HasMany
    {
        return $this->hasMany(TeacherInsight::class, 'student_id');
    }

    public function kelasDibuat(): HasMany
    {
        return $this->hasMany(Kelas::class, 'guru_id');
    }

    public function kelasDiikuti(): BelongsToMany
    {
        return $this->belongsToMany(Kelas::class, 'kelas_siswa')
            ->withPivot('enrolled_at')
            ->withTimestamps();
    }

    // Helper methods for role checking
    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    public function isGuru(): bool
    {
        return $this->role === UserRole::GURU;
    }

    public function isSiswa(): bool
    {
        return $this->role === UserRole::SISWA;
    }

    public function isAdminOrGuru(): bool
    {
        return $this->isAdmin() || $this->isGuru();
    }

    /**
     * Check if user has any of the given roles
     * @param string ...$roles
     * @return bool
     */
    public function hasRole(...$roles): bool
    {
        return in_array($this->role->value, $roles);
    }
}
