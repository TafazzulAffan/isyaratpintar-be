<?php

namespace Tests\Unit;

use App\Events\Assessment\AnswerSubmitted;
use App\Events\Assessment\AssessmentStarted;
use App\Events\Assessment\AttemptCompleted;
use App\Events\Assessment\AttemptTimedOut;
use App\Events\Learning\LessonCompleted;
use App\Events\Learning\LessonViewed;
use App\Events\Learning\TaskSubmitted;
use App\Events\Session\PageViewed;
use App\Events\Session\SessionEnded;
use App\Events\Session\SessionStarted;
use App\Events\SPK\StudentExcellingDetected;
use App\Events\SPK\StudentNeedsAttention;
use App\Events\SPK\StudentRiskProfileUpdated;
use App\Events\SPK\SubjectMasteryCalculated;
use App\Listeners\Session\LogActivityEvent;
use App\Listeners\Session\TrackSessionDuration;
use App\Listeners\Session\UpdateUserActivityTimestamp;
use App\Listeners\SPK\CalculateRiskProfileListener;
use App\Listeners\SPK\CalculateSubjectMasteryListener;
use App\Listeners\SPK\GenerateInsightsListener;
use App\Providers\EventServiceProvider;
use PHPUnit\Framework\TestCase;

/**
 * Test: Verifikasi bahwa semua Domain Events terdaftar dengan benar
 * di EventServiceProvider beserta Listener yang sesuai.
 *
 * Relevansi Skripsi: Membuktikan bahwa wiring Event-Driven Architecture
 * telah dikonfigurasi dengan lengkap dan benar.
 */
class EventRegistrationTest extends TestCase
{
    private array $registeredEvents;

    protected function setUp(): void
    {
        parent::setUp();

        // Use reflection to read the $listen property directly from EventServiceProvider
        $reflection = new \ReflectionClass(EventServiceProvider::class);
        $property = $reflection->getProperty('listen');
        $property->setAccessible(true);

        // Instantiate with a mock app parameter
        $provider = $reflection->newInstanceWithoutConstructor();
        $this->registeredEvents = $property->getValue($provider);
    }

    // =========================================================================
    // ASSESSMENT EVENTS
    // =========================================================================

    public function test_assessment_started_event_has_correct_listeners(): void
    {
        $this->assertArrayHasKey(AssessmentStarted::class, $this->registeredEvents);

        $listeners = $this->registeredEvents[AssessmentStarted::class];
        $this->assertContains(LogActivityEvent::class, $listeners);
        $this->assertContains(TrackSessionDuration::class, $listeners);
    }

    public function test_answer_submitted_event_has_log_listener(): void
    {
        $this->assertArrayHasKey(AnswerSubmitted::class, $this->registeredEvents);

        $listeners = $this->registeredEvents[AnswerSubmitted::class];
        $this->assertContains(LogActivityEvent::class, $listeners);
    }

    public function test_attempt_completed_event_triggers_mastery_calculation(): void
    {
        $this->assertArrayHasKey(AttemptCompleted::class, $this->registeredEvents);

        $listeners = $this->registeredEvents[AttemptCompleted::class];
        $this->assertContains(LogActivityEvent::class, $listeners);
        $this->assertContains(CalculateSubjectMasteryListener::class, $listeners);
    }

    public function test_attempt_timed_out_event_has_log_listener(): void
    {
        $this->assertArrayHasKey(AttemptTimedOut::class, $this->registeredEvents);

        $listeners = $this->registeredEvents[AttemptTimedOut::class];
        $this->assertContains(LogActivityEvent::class, $listeners);
    }

    // =========================================================================
    // SESSION EVENTS
    // =========================================================================

    public function test_session_started_event_has_correct_listeners(): void
    {
        $this->assertArrayHasKey(SessionStarted::class, $this->registeredEvents);

        $listeners = $this->registeredEvents[SessionStarted::class];
        $this->assertContains(LogActivityEvent::class, $listeners);
        $this->assertContains(TrackSessionDuration::class, $listeners);
    }

    public function test_session_ended_event_has_all_three_listeners(): void
    {
        $this->assertArrayHasKey(SessionEnded::class, $this->registeredEvents);

        $listeners = $this->registeredEvents[SessionEnded::class];
        $this->assertContains(LogActivityEvent::class, $listeners);
        $this->assertContains(TrackSessionDuration::class, $listeners);
        $this->assertContains(UpdateUserActivityTimestamp::class, $listeners);
    }

    public function test_page_viewed_event_has_log_listener(): void
    {
        $this->assertArrayHasKey(PageViewed::class, $this->registeredEvents);

        $listeners = $this->registeredEvents[PageViewed::class];
        $this->assertContains(LogActivityEvent::class, $listeners);
    }

    // =========================================================================
    // LEARNING EVENTS
    // =========================================================================

    public function test_lesson_viewed_event_has_log_listener(): void
    {
        $this->assertArrayHasKey(LessonViewed::class, $this->registeredEvents);
        $this->assertContains(LogActivityEvent::class, $this->registeredEvents[LessonViewed::class]);
    }

    public function test_lesson_completed_event_has_log_listener(): void
    {
        $this->assertArrayHasKey(LessonCompleted::class, $this->registeredEvents);
        $this->assertContains(LogActivityEvent::class, $this->registeredEvents[LessonCompleted::class]);
    }

    public function test_task_submitted_event_has_log_listener(): void
    {
        $this->assertArrayHasKey(TaskSubmitted::class, $this->registeredEvents);
        $this->assertContains(LogActivityEvent::class, $this->registeredEvents[TaskSubmitted::class]);
    }

    // =========================================================================
    // SPK EVENTS (Event Chain)
    // =========================================================================

    public function test_subject_mastery_calculated_triggers_risk_profile(): void
    {
        $this->assertArrayHasKey(SubjectMasteryCalculated::class, $this->registeredEvents);

        $listeners = $this->registeredEvents[SubjectMasteryCalculated::class];
        $this->assertContains(CalculateRiskProfileListener::class, $listeners);
    }

    public function test_risk_profile_updated_triggers_insights_generation(): void
    {
        $this->assertArrayHasKey(StudentRiskProfileUpdated::class, $this->registeredEvents);

        $listeners = $this->registeredEvents[StudentRiskProfileUpdated::class];
        $this->assertContains(GenerateInsightsListener::class, $listeners);
    }

    public function test_student_needs_attention_event_is_registered(): void
    {
        $this->assertArrayHasKey(StudentNeedsAttention::class, $this->registeredEvents);
    }

    public function test_student_excelling_detected_event_is_registered(): void
    {
        $this->assertArrayHasKey(StudentExcellingDetected::class, $this->registeredEvents);
    }

    // =========================================================================
    // COMPREHENSIVE VERIFICATION
    // =========================================================================

    public function test_total_registered_events_count_is_14(): void
    {
        // Exclude Laravel's built-in Registered event
        $domainEvents = collect($this->registeredEvents)->filter(function ($listeners, $event) {
            return str_starts_with($event, 'App\\Events\\');
        });

        $this->assertCount(14, $domainEvents, 'Harus ada 14 domain events terdaftar');
    }

    public function test_event_chain_is_connected_mastery_to_risk_to_insights(): void
    {
        // Step 1: AttemptCompleted → CalculateSubjectMasteryListener
        $this->assertContains(
            CalculateSubjectMasteryListener::class,
            $this->registeredEvents[AttemptCompleted::class],
            'AttemptCompleted harus trigger CalculateSubjectMasteryListener'
        );

        // Step 2: SubjectMasteryCalculated → CalculateRiskProfileListener
        $this->assertContains(
            CalculateRiskProfileListener::class,
            $this->registeredEvents[SubjectMasteryCalculated::class],
            'SubjectMasteryCalculated harus trigger CalculateRiskProfileListener'
        );

        // Step 3: StudentRiskProfileUpdated → GenerateInsightsListener
        $this->assertContains(
            GenerateInsightsListener::class,
            $this->registeredEvents[StudentRiskProfileUpdated::class],
            'StudentRiskProfileUpdated harus trigger GenerateInsightsListener'
        );
    }

    public function test_log_activity_listener_handles_all_user_facing_events(): void
    {
        $userFacingEvents = [
            AssessmentStarted::class,
            AnswerSubmitted::class,
            AttemptCompleted::class,
            AttemptTimedOut::class,
            SessionStarted::class,
            SessionEnded::class,
            PageViewed::class,
            LessonViewed::class,
            LessonCompleted::class,
            TaskSubmitted::class,
        ];

        foreach ($userFacingEvents as $event) {
            $this->assertContains(
                LogActivityEvent::class,
                $this->registeredEvents[$event],
                "LogActivityEvent harus terdaftar untuk: {$event}"
            );
        }

        $this->assertCount(10, $userFacingEvents, 'Harus ada 10 user-facing events yang di-log');
    }
}
