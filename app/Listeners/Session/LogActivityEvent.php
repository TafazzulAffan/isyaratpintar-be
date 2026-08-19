<?php

namespace App\Listeners\Session;

use App\Events\Assessment\{AssessmentStarted, AnswerSubmitted, AttemptCompleted, AttemptTimedOut};
use App\Events\Session\{SessionStarted, SessionEnded, PageViewed};
use App\Events\Learning\{LessonViewed, LessonCompleted, TaskSubmitted};
use App\Models\StudentActivityLog;

class LogActivityEvent
{
    /**
     * Handle any event and log to StudentActivityLog table
     */
    public function handle(object $event): void
    {
        match (get_class($event)) {
            AssessmentStarted::class => $this->logAssessmentStarted($event),
            AnswerSubmitted::class => $this->logAnswerSubmitted($event),
            AttemptCompleted::class => $this->logAttemptCompleted($event),
            AttemptTimedOut::class => $this->logAttemptTimedOut($event),
            SessionStarted::class => $this->logSessionStarted($event),
            SessionEnded::class => $this->logSessionEnded($event),
            PageViewed::class => $this->logPageViewed($event),
            LessonViewed::class => $this->logLessonViewed($event),
            LessonCompleted::class => $this->logLessonCompleted($event),
            TaskSubmitted::class => $this->logTaskSubmitted($event),
            default => null,
        };
    }

    private function logAssessmentStarted(AssessmentStarted $event): void
    {
        StudentActivityLog::create([
            'user_id' => $event->attempt->user_id,
            'activity_type' => 'assessment_started',
            'entity_type' => 'Assessment',
            'entity_id' => $event->attempt->assessment_id,
            'metadata' => [
                'attempt_id' => $event->attempt->id,
                'assessment_id' => $event->attempt->assessment_id,
            ],
            'logged_at' => now(),
        ]);
    }

    private function logAnswerSubmitted(AnswerSubmitted $event): void
    {
        StudentActivityLog::create([
            'user_id' => $event->attempt->user_id,
            'activity_type' => 'answer_submitted',
            'entity_type' => 'Question',
            'entity_id' => $event->answer->question_id,
            'metadata' => [
                'attempt_id' => $event->attempt->id,
                'answer_id' => $event->answer->id,
                'is_correct' => $event->answer->is_correct,
            ],
            'logged_at' => now(),
        ]);
    }

    private function logAttemptCompleted(AttemptCompleted $event): void
    {
        StudentActivityLog::create([
            'user_id' => $event->attempt->user_id,
            'activity_type' => 'attempt_completed',
            'entity_type' => 'Assessment',
            'entity_id' => $event->attempt->assessment_id,
            'metadata' => [
                'attempt_id' => $event->attempt->id,
                'score' => $event->attempt->score,
                'status' => $event->attempt->status->value,
            ],
            'logged_at' => now(),
        ]);
    }

    private function logAttemptTimedOut(AttemptTimedOut $event): void
    {
        StudentActivityLog::create([
            'user_id' => $event->attempt->user_id,
            'activity_type' => 'attempt_timed_out',
            'entity_type' => 'Assessment',
            'entity_id' => $event->attempt->assessment_id,
            'metadata' => [
                'attempt_id' => $event->attempt->id,
                'assessment_id' => $event->attempt->assessment_id,
            ],
            'logged_at' => now(),
        ]);
    }

    private function logSessionStarted(SessionStarted $event): void
    {
        StudentActivityLog::create([
            'user_id' => $event->user->id,
            'activity_type' => 'session_started',
            'entity_type' => $event->entityType,
            'entity_id' => $event->entityId,
            'metadata' => [
                'entity_type' => $event->entityType,
                'entity_id' => $event->entityId,
            ],
            'logged_at' => now(),
        ]);
    }

    private function logSessionEnded(SessionEnded $event): void
    {
        StudentActivityLog::create([
            'user_id' => $event->user->id,
            'activity_type' => 'session_ended',
            'metadata' => [
                'duration_seconds' => $event->durationSeconds,
                'duration_minutes' => round($event->durationSeconds / 60, 2),
            ],
            'logged_at' => now(),
        ]);
    }

    private function logPageViewed(PageViewed $event): void
    {
        StudentActivityLog::create([
            'user_id' => $event->user->id,
            'activity_type' => 'page_viewed',
            'entity_type' => $event->entityType,
            'entity_id' => $event->entityId,
            'metadata' => [
                'page_url' => $event->pageUrl,
                'entity_type' => $event->entityType,
                'entity_id' => $event->entityId,
            ],
            'logged_at' => now(),
        ]);
    }

    private function logLessonViewed(LessonViewed $event): void
    {
        StudentActivityLog::create([
            'user_id' => $event->user->id,
            'activity_type' => 'lesson_viewed',
            'entity_type' => 'Lesson',
            'entity_id' => $event->lesson->id,
            'metadata' => [
                'lesson_id' => $event->lesson->id,
                'lesson_title' => $event->lesson->title,
            ],
            'logged_at' => now(),
        ]);
    }

    private function logLessonCompleted(LessonCompleted $event): void
    {
        StudentActivityLog::create([
            'user_id' => $event->user->id,
            'activity_type' => 'lesson_completed',
            'entity_type' => 'Lesson',
            'entity_id' => $event->lesson->id,
            'metadata' => [
                'lesson_id' => $event->lesson->id,
                'lesson_title' => $event->lesson->title,
            ],
            'logged_at' => now(),
        ]);
    }

    private function logTaskSubmitted(TaskSubmitted $event): void
    {
        StudentActivityLog::create([
            'user_id' => $event->user->id,
            'activity_type' => 'task_submitted',
            'entity_type' => $event->taskType ?? 'Task',
            'entity_id' => $event->taskId,
            'metadata' => [
                'task_id' => $event->taskId,
                'task_type' => $event->taskType,
            ],
            'logged_at' => now(),
        ]);
    }
}
