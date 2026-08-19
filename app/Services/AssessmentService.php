<?php

namespace App\Services;

use App\Enums\AssessmentAttemptStatus;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class AssessmentService
{
    public function __construct(
        private KelasAccessService $kelasAccessService
    ) {}

    /**
     * Get all assessments with count of questions
     */
    public function getAllAssessments(?User $user = null): LengthAwarePaginator
    {
        return $this->kelasAccessService
            ->scopeContentQuery(Assessment::withCount('questions'), $user)
            ->latest()
            ->paginate(15);
    }

    /**
     * Get assessments filtered by mata_pelajaran id
     */
    public function getAssessmentsByMataPelajaranId(int $id, ?User $user = null): LengthAwarePaginator
    {
        return $this->kelasAccessService
            ->scopeContentQuery(
                Assessment::withCount('questions')->where('mata_pelajaran_id', $id),
                $user
            )
            ->latest()
            ->paginate(15);
    }

    /**
     * Get assessment by slug with questions and options
     */
    public function getAssessmentBySlug(string $slug, ?User $user = null): ?Assessment
    {
        return $this->kelasAccessService
            ->scopeContentQuery(
                Assessment::where('slug', $slug)->with(['questions.options']),
                $user
            )
            ->first();
    }

    /**
     * Get assessment by ID
     */
    public function getAssessmentById(int $id, ?User $user = null): ?Assessment
    {
        return $this->kelasAccessService
            ->scopeContentQuery(
                Assessment::with(['questions.options'])->where('id', $id),
                $user
            )
            ->first();
    }

    /**
     * Create new assessment
     */
    public function createAssessment(array $data): Assessment
    {
        return Assessment::create($data);
    }

    /**
     * Update assessment
     */
    public function updateAssessment(Assessment $assessment, array $data): Assessment
    {
        $assessment->update($data);
        return $assessment;
    }

    /**
     * Delete assessment
     */
    public function deleteAssessment(Assessment $assessment): bool
    {
        return $assessment->delete();
    }

    /**
     * Check if assessment exists
     */
    public function assessmentExists(int $id): bool
    {
        return Assessment::exists() && Assessment::where('id', $id)->exists();
    }

    /**
     * Get total questions in assessment
     */
    public function getTotalQuestions(Assessment $assessment): int
    {
        return $assessment->questions()->count();
    }

    /**
     * Get all attempts for assessment
     */
    public function getAssessmentAttempts(Assessment $assessment): Collection
    {
        return $assessment->attempts()
            ->with(['user'])
            ->latest()
            ->get();
    }
}
