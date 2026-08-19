<?php

namespace App\Services;

use App\Events\Assessment\AnswerSubmitted;
use App\Models\AssessmentAttempt;
use App\Models\AttemptAnswer;
use App\Models\Option;
use App\Models\Question;
use Illuminate\Database\Eloquent\Collection;

class AnswerService
{
    /**
     * Submit answer for a question
     *
     * @throws \Exception
     */
    public function submitAnswer(
        AssessmentAttempt $attempt,
        int $questionId,
        string $selectedOptionId     
    ): AttemptAnswer {
        // Validate attempt is in progress
        if (!$attempt->isInProgress()) {
            throw new \Exception('Assessment attempt is not in progress');
        }

        // Check if attempt has timed out
        if ($attempt->hasTimedOut()) {
            $attempt->update(['status' => 'TIMEOUT']);
            throw new \Exception('Assessment attempt has timed out');
        }

        // Check if question already answered
        $existingAnswer = AttemptAnswer::where('attempt_id', $attempt->id)
            ->where('question_id', $questionId)
            ->first();

        if ($existingAnswer) {
            throw new \Exception('Question already answered');
        }

        // Validate question belongs to assessment
        $question = Question::where('id', $questionId)
            ->where('assessment_id', $attempt->assessment_id)
            ->first();

        if (!$question) {
            throw new \Exception('Question does not belong to this assessment');
        }

        // Validate option belongs to question
        $option = Option::where('id', $selectedOptionId)
            ->where('question_id', $questionId)
            ->first();

        if (!$option) {
            throw new \Exception('Option does not belong to this question');
        }

        // Create answer record
        $answer = AttemptAnswer::create([
            'attempt_id' => $attempt->id,
            'question_id' => $questionId,
            'selected_option_id' => $selectedOptionId,
            'is_correct' => $option->is_correct,
        ]);

        // 🔥 DISPATCH EVENT
        event(new AnswerSubmitted($attempt, $answer));

        return $answer;
    }

    /**
     * Submit multiple answers for bulk submission
     *
     * @param AssessmentAttempt $attempt
     * @param array $answers Array of answers with keys: question_id, selected_option_id
     * @return array Array with total, success_count, failed_count, and results array
     * @throws \Exception
     */
    public function submitAnswersBulk(
        AssessmentAttempt $attempt,
        array $answers
    ): array {
        // Validate attempt is in progress
        if (!$attempt->isInProgress()) {
            throw new \Exception('Assessment attempt is not in progress');
        }

        // Check if attempt has timed out
        if ($attempt->hasTimedOut()) {
            $attempt->update(['status' => 'TIMEOUT']);
            throw new \Exception('Assessment attempt has timed out');
        }

        $results = [];
        $failedCount = 0;

        foreach ($answers as $answerData) {
            $questionId = $answerData['question_id'];
            $selectedOptionId = $answerData['selected_option_id'];

            try {
                // Check if question already answered
                $existingAnswer = AttemptAnswer::where('attempt_id', $attempt->id)
                    ->where('question_id', $questionId)
                    ->first();

                if ($existingAnswer) {
                    $results[] = [
                        'question_id' => $questionId,
                        'success' => false,
                        'message' => 'Question already answered',
                        'answer_id' => null,
                        'is_correct' => null,
                    ];
                    $failedCount++;
                    continue;
                }

                // Validate question belongs to assessment
                $question = Question::where('id', $questionId)
                    ->where('assessment_id', $attempt->assessment_id)
                    ->first();

                if (!$question) {
                    $results[] = [
                        'question_id' => $questionId,
                        'success' => false,
                        'message' => 'Question does not belong to this assessment',
                        'answer_id' => null,
                        'is_correct' => null,
                    ];
                    $failedCount++;
                    continue;
                }

                // Validate option belongs to question
                $option = Option::where('id', $selectedOptionId)
                    ->where('question_id', $questionId)
                    ->first();

                if (!$option) {
                    $results[] = [
                        'question_id' => $questionId,
                        'success' => false,
                        'message' => 'Option does not belong to this question',
                        'answer_id' => null,
                        'is_correct' => null,
                    ];
                    $failedCount++;
                    continue;
                }

                // Create answer record
                $answer = AttemptAnswer::create([
                    'attempt_id' => $attempt->id,
                    'question_id' => $questionId,
                    'selected_option_id' => $selectedOptionId,
                    'is_correct' => $option->is_correct,
                ]);

                // 🔥 DISPATCH EVENT
                event(new AnswerSubmitted($attempt, $answer));

                $results[] = [
                    'question_id' => $questionId,
                    'success' => true,
                    'message' => 'Answer submitted',
                    'answer_id' => $answer->id,
                    'is_correct' => $answer->is_correct,
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'question_id' => $questionId,
                    'success' => false,
                    'message' => $e->getMessage(),
                    'answer_id' => null,
                    'is_correct' => null,
                ];
                $failedCount++;
            }
        }

        return [
            'total' => count($answers),
            'success_count' => count($answers) - $failedCount,
            'failed_count' => $failedCount,
            'results' => $results,
        ];
    }

    /**
     * Get answers for an attempt
     */
    public function getAttemptAnswers(AssessmentAttempt $attempt): Collection
    {
        return $attempt->answers()
            ->with(['question', 'selectedOption'])
            ->get();
    }

    /**
     * Check if question is already answered
     */
    public function isQuestionAnswered(AssessmentAttempt $attempt, int $questionId): bool
    {
        return AttemptAnswer::where('attempt_id', $attempt->id)
            ->where('question_id', $questionId)
            ->exists();
    }

    /**
     * Get number of answered questions
     */
    public function getAnsweredCount(AssessmentAttempt $attempt): int
    {
        return $attempt->answers()->count();
    }

    /**
     * Get correct answers count
     */
    public function getCorrectAnswersCount(AssessmentAttempt $attempt): int
    {
        return $attempt->answers()
            ->where('is_correct', true)
            ->count();
    }

    /**
     * Calculate score
     */
    public function calculateScore(AssessmentAttempt $attempt): float
    {
        $correctAnswers = $this->getCorrectAnswersCount($attempt);
        $totalQuestions = $attempt->assessment->questions()->count();

        if ($totalQuestions === 0) {
            return 0;
        }

        return ($correctAnswers / $totalQuestions) * 100;
    }
}
