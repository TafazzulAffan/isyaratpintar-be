<?php

namespace App\Providers;

use App\Events\Assessment\{
    AssessmentStarted,
    AnswerSubmitted,
    AttemptCompleted,
    AttemptTimedOut,
};
use App\Events\Session\{
    SessionStarted,
    SessionEnded,
    PageViewed,
};
use App\Events\Learning\{
    LessonViewed,
    LessonCompleted,
    TaskSubmitted,
};
use App\Events\SPK\{
    SubjectMasteryCalculated,
    StudentRiskProfileUpdated,
    StudentNeedsAttention,
    StudentExcellingDetected,
};
use App\Listeners\Session\{
    LogActivityEvent,
    TrackSessionDuration,
    UpdateUserActivityTimestamp,
};
use App\Listeners\SPK\{
    CalculateSubjectMasteryListener,
    CalculateRiskProfileListener,
    GenerateInsightsListener,
};
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        // ===== AUTH EVENTS =====
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        // ===== ASSESSMENT EVENTS =====
        AssessmentStarted::class => [
            LogActivityEvent::class,
            TrackSessionDuration::class,
        ],

        AnswerSubmitted::class => [
            LogActivityEvent::class,
        ],

        AttemptCompleted::class => [
            LogActivityEvent::class,
            CalculateSubjectMasteryListener::class,
        ],

        AttemptTimedOut::class => [
            LogActivityEvent::class,
        ],

        // ===== SESSION EVENTS =====
        SessionStarted::class => [
            LogActivityEvent::class,
            TrackSessionDuration::class,
        ],

        SessionEnded::class => [
            LogActivityEvent::class,
            TrackSessionDuration::class,
            UpdateUserActivityTimestamp::class,
        ],

        PageViewed::class => [
            LogActivityEvent::class,
        ],

        // ===== LEARNING EVENTS =====
        LessonViewed::class => [
            LogActivityEvent::class,
        ],

        LessonCompleted::class => [
            LogActivityEvent::class,
        ],

        TaskSubmitted::class => [
            LogActivityEvent::class,
        ],

        // ===== SPK EVENTS =====
        SubjectMasteryCalculated::class => [
            CalculateRiskProfileListener::class,
        ],

        StudentRiskProfileUpdated::class => [
            GenerateInsightsListener::class,
        ],

        StudentNeedsAttention::class => [],

        StudentExcellingDetected::class => [],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
