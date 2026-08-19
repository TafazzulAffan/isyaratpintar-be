<?php

namespace App\Services;

use App\Enums\AssessmentAttemptStatus;
use App\Events\Assessment\AssessmentStarted;
use App\Events\Assessment\AttemptCompleted;
use App\Events\Assessment\AttemptTimedOut;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class AttemptService
{
    /**
     * Get user's active assessment attempt
     */
    public function getActiveAttempt(User $user, Assessment $assessment): ?AssessmentAttempt
    {
        return AssessmentAttempt::where('user_id', $user->id)
            ->where('assessment_id', $assessment->id)
            ->where('status', AssessmentAttemptStatus::IN_PROGRESS)
            ->first();
    }

    /**
     * Start a new assessment attempt
     *
     * @throws \Exception
     */
    public function startAttempt(User $user, Assessment $assessment): AssessmentAttempt
    {
        // Check if user already has active attempt
        $activeAttempt = $this->getActiveAttempt($user, $assessment);

        if ($activeAttempt) {
            throw new \Exception('User already has an active attempt for this assessment');
        }

        // Create new attempt
        $attempt = AssessmentAttempt::create([
            'user_id' => $user->id,
            'assessment_id' => $assessment->id,
            'status' => AssessmentAttemptStatus::IN_PROGRESS,
            'started_at' => now(),
        ]);

        // 🔥 DISPATCH EVENT
        event(new AssessmentStarted($attempt));

        return $attempt;
    }

    /**
     * Get user's attempt with validation
     */
    public function getAttempt(int $attemptId, User $user): ?AssessmentAttempt
    {
        return AssessmentAttempt::where('id', $attemptId)
            ->where('user_id', $user->id)
            ->with(['assessment', 'answers.question', 'answers.selectedOption'])
            ->first();
    }

    /**
     * Check if attempt has timed out
     */
    public function hasTimedOut(AssessmentAttempt $attempt): bool
    {
        if (!$attempt->started_at || !$attempt->assessment) {
            return false;
        }

        $endTime = $attempt->started_at->addMinutes($attempt->assessment->time_limit);
        return now()->greaterThan($endTime);
    }

    /**
     * Complete attempt and calculate score
     *
     * @throws \Exception
     */
    public function completeAttempt(AssessmentAttempt $attempt): AssessmentAttempt
    {
        // Check if already completed or timeout
        if (!$attempt->isInProgress()) {
            throw new \Exception('Attempt is not in progress');
        }

        // Check timeout
        if ($this->hasTimedOut($attempt)) {
            $attempt->update([
                'status' => AssessmentAttemptStatus::TIMEOUT,
                'completed_at' => now(),
            ]);

            // 🔥 DISPATCH TIMEOUT EVENT
            event(new AttemptTimedOut($attempt));

            throw new \Exception('Attempt has timed out');
        }

        // Calculate score
        $correctAnswers = $attempt->getCorrectAnswersCount();
        $totalQuestions = $attempt->assessment->questions()->count();
        $score = ($correctAnswers / $totalQuestions) * 100;

        // Update attempt (include computed level)
        $attempt->update([
            'score' => $score,
            'level' => AssessmentAttempt::determineLevel($score),
            'status' => AssessmentAttemptStatus::COMPLETED,
            'completed_at' => now(),
        ]);

        // 🔥 DISPATCH COMPLETION EVENT
        event(new AttemptCompleted($attempt));

        return $attempt;
    }

    /**
     * Get user's results
     */
    public function getUserResults(User $user): Collection
    {
        return $user->assessmentAttempts()
            ->with(['assessment'])
            ->latest('updated_at')
            ->get();
    }

    /**
     * Get user's specific result
     */
    public function getUserResult(int $attemptId, User $user): ?AssessmentAttempt
    {
        return $user->assessmentAttempts()
            ->where('id', $attemptId)
            ->with(['assessment', 'answers.question', 'answers.selectedOption'])
            ->first();
    }

    /**
     * Get all attempts (admin only)
     */
    public function getAllAttempts()
    {
        return AssessmentAttempt::with(['user', 'assessment'])
            ->latest()
            ->paginate(20);
    }

    /**
     * Get attempt detail (admin only)
     */
    public function getAttemptDetail(int $attemptId): ?AssessmentAttempt
    {
        return AssessmentAttempt::where('id', $attemptId)
            ->with(['user', 'assessment', 'answers.question', 'answers.selectedOption'])
            ->first();
    }
}
