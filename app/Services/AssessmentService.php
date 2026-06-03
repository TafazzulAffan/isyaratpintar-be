<?php

namespace App\Services;

use App\Enums\AssessmentAttemptStatus;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class AssessmentService
{
    /**
     * Get all assessments with count of questions
     */
    public function getAllAssessments(): LengthAwarePaginator
    {
        return Assessment::withCount('questions')
            ->latest()
            ->paginate(15);
    }

    /**
     * Get assessments filtered by mata_pelajaran id
     */
    public function getAssessmentsByMataPelajaranId(int $id): LengthAwarePaginator
    {
        return Assessment::withCount('questions')
            ->where('mata_pelajaran_id', $id)
            ->latest()
            ->paginate(15);
    }

    /**
     * Get assessment by slug with questions and options
     */
    public function getAssessmentBySlug(string $slug): ?Assessment
    {
        return Assessment::where('slug', $slug)
            ->with(['questions.options'])
            ->first();
    }

    /**
     * Get assessment by ID
     */
    public function getAssessmentById(int $id): ?Assessment
    {
        return Assessment::with(['questions.options'])->find($id);
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
