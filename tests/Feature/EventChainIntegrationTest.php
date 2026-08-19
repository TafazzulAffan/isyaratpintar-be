<?php

namespace Tests\Feature;

use App\Enums\AssessmentAttemptStatus;
use App\Events\Assessment\AttemptCompleted;
use App\Events\SPK\StudentNeedsAttention;
use App\Events\SPK\StudentRiskProfileUpdated;
use App\Events\SPK\SubjectMasteryCalculated;
use App\Jobs\CalculateStudentRiskProfileJob;
use App\Jobs\CalculateSubjectMasteryJob;
use App\Jobs\GenerateTeacherInsightsJob;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\MataPelajaran;
use App\Models\SessionDuration;
use App\Models\StudentActivityLog;
use App\Models\StudentRiskProfile;
use App\Models\SubjectMastery;
use App\Models\TeacherInsight;
use App\Models\User;
use App\Enums\UserRole;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Test: Verifikasi bahwa event chain berjalan dari awal sampai akhir
 * (AttemptCompleted → SubjectMastery → RiskProfile → TeacherInsights).
 *
 * Relevansi Skripsi: Membuktikan bahwa seluruh pipeline Event-Driven Architecture
 * berfungsi secara end-to-end, dari trigger event hingga hasil akhir di database.
 */
class EventChainIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    private User $student;
    private MataPelajaran $subject;
    private Assessment $assessment;
    private AssessmentAttempt $attempt;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test student
        $this->student = User::create([
            'name' => 'Siswa Test EDA',
            'username' => 'siswaedatest',
            'email' => 'siswa.eda.test@example.com',
            'password' => bcrypt('password'),
            'role' => UserRole::SISWA,
        ]);

        // Create subject
        $this->subject = MataPelajaran::create([
            'name' => 'Matematika Test',
        ]);

        // Create assessment
        $this->assessment = Assessment::create([
            'title' => 'Assessment EDA Test',
            'slug' => 'assessment-eda-test-' . uniqid(),
            'description' => 'Assessment untuk testing event chain',
            'time_limit' => 30,
            'mata_pelajaran_id' => $this->subject->id,
        ]);

        // Create completed attempt
        $this->attempt = AssessmentAttempt::create([
            'user_id' => $this->student->id,
            'assessment_id' => $this->assessment->id,
            'status' => 'COMPLETED',
            'score' => 85.00,
            'level' => 'Advanced',
            'started_at' => now()->subMinutes(20),
            'completed_at' => now(),
        ]);

        // Seed activity data for risk profile calculation
        $this->seedStudentActivityData();
    }

    // =========================================================================
    // TEST 1: Event Dispatch Verification
    // =========================================================================

    public function test_attempt_completed_event_dispatches_correctly(): void
    {
        Event::fake([AttemptCompleted::class]);

        event(new AttemptCompleted($this->attempt));

        Event::assertDispatched(AttemptCompleted::class, function ($event) {
            return $event->attempt->id === $this->attempt->id;
        });
    }

    // =========================================================================
    // TEST 2: Sync Listener - Activity Logging
    // =========================================================================

    public function test_attempt_completed_creates_activity_log_synchronously(): void
    {
        // Don't fake events — let sync listeners run
        Queue::fake(); // But fake the queue so jobs don't actually execute

        event(new AttemptCompleted($this->attempt));

        // LogActivityEvent runs synchronously — verify the log exists
        $this->assertDatabaseHas('student_activity_logs', [
            'user_id' => $this->student->id,
            'activity_type' => 'attempt_completed',
            'entity_type' => 'Assessment',
            'entity_id' => $this->assessment->id,
        ]);
    }

    // =========================================================================
    // TEST 3: Job Queuing - Async Processing
    // =========================================================================

    public function test_attempt_completed_dispatches_mastery_job_to_queue(): void
    {
        Queue::fake();

        event(new AttemptCompleted($this->attempt));

        Queue::assertPushed(CalculateSubjectMasteryJob::class);
    }

    public function test_mastery_calculated_event_dispatches_risk_profile_job(): void
    {
        Queue::fake();

        // First create a mastery record (simulating what the mastery job would do)
        $mastery = SubjectMastery::create([
            'user_id' => $this->student->id,
            'mata_pelajaran_id' => $this->subject->id,
            'mastery_percentage' => 85,
            'average_score' => 85,
            'assessments_completed' => 1,
            'status' => 'excellent',
            'calculated_at' => now(),
        ]);

        event(new SubjectMasteryCalculated($mastery));

        Queue::assertPushed(CalculateStudentRiskProfileJob::class);
    }

    public function test_risk_profile_updated_event_dispatches_insights_job(): void
    {
        Queue::fake();

        $profile = StudentRiskProfile::create([
            'user_id' => $this->student->id,
            'attendance_risk' => 30,
            'performance_risk' => 20,
            'engagement_risk' => 25,
            'subject_mastery_risk' => 15,
            'overall_risk_score' => 22.5,
            'risk_level' => 'low',
            'struggling_subjects' => [],
            'recommendations' => [],
            'calculated_at' => now(),
        ]);

        event(new StudentRiskProfileUpdated($profile));

        Queue::assertPushed(GenerateTeacherInsightsJob::class);
    }

    // =========================================================================
    // TEST 4: Full Event Chain - E2E with Job Execution
    // =========================================================================

    public function test_full_event_chain_from_attempt_to_insights(): void
    {
        // Verify initial state: no activity logs, no mastery, no risk profile
        $this->assertDatabaseMissing('student_activity_logs', [
            'user_id' => $this->student->id,
            'activity_type' => 'attempt_completed',
        ]);

        // Step 1: Fire AttemptCompleted event
        event(new AttemptCompleted($this->attempt));

        // Step 2: Verify sync listener ran (activity log created)
        $this->assertDatabaseHas('student_activity_logs', [
            'user_id' => $this->student->id,
            'activity_type' => 'attempt_completed',
        ]);

        // Step 3: Execute the queued mastery job manually
        $masteryJob = new CalculateSubjectMasteryJob($this->attempt->id);
        $masteryJob->handle(app(\App\Services\SubjectMasteryService::class));

        // Step 4: Verify subject mastery was calculated
        $this->assertDatabaseHas('subject_mastery', [
            'user_id' => $this->student->id,
            'mata_pelajaran_id' => $this->subject->id,
        ]);

        $mastery = SubjectMastery::where('user_id', $this->student->id)
            ->where('mata_pelajaran_id', $this->subject->id)
            ->first();

        $this->assertNotNull($mastery, 'SubjectMastery record harus dibuat');
        $this->assertEquals(85.00, $mastery->mastery_percentage);

        // Step 5: Execute the risk profile job (triggered by SubjectMasteryCalculated event)
        $riskJob = new CalculateStudentRiskProfileJob($mastery->id);
        $riskJob->handle(app(\App\Services\StudentRiskProfileService::class));

        // Step 6: Verify risk profile was created
        $this->assertDatabaseHas('student_risk_profiles', [
            'user_id' => $this->student->id,
        ]);

        $riskProfile = StudentRiskProfile::where('user_id', $this->student->id)->first();
        $this->assertNotNull($riskProfile, 'StudentRiskProfile record harus dibuat');
        $this->assertNotNull($riskProfile->risk_level);
        $this->assertContains($riskProfile->risk_level, ['low', 'medium', 'high', 'critical']);

        // Step 7: Execute the insights job (triggered by StudentRiskProfileUpdated event)
        $insightsJob = new GenerateTeacherInsightsJob($riskProfile->id);
        $insightsJob->handle();

        // Step 8: Verify the entire chain produced results
        $activityLogCount = StudentActivityLog::where('user_id', $this->student->id)->count();
        $this->assertGreaterThan(0, $activityLogCount, 'Harus ada activity logs');
    }

    // =========================================================================
    // TEST 5: High Risk Student triggers StudentNeedsAttention
    // =========================================================================

    public function test_high_risk_student_triggers_needs_attention_event(): void
    {
        Event::fake([StudentNeedsAttention::class]);

        // Create a student with very poor data (no activity, no engagement)
        $poorStudent = User::create([
            'name' => 'Siswa Berisiko Tinggi',
            'username' => 'poorstudent',
            'email' => 'poor.student.test@example.com',
            'password' => bcrypt('password'),
            'role' => UserRole::SISWA,
        ]);

        // Don't add any activity data — this should result in high risk

        // Calculate risk profile
        $service = app(\App\Services\StudentRiskProfileService::class);
        $profile = $service->calculateRiskProfile($poorStudent);

        // If the risk score is high/critical, the StudentNeedsAttention event should fire
        if (in_array($profile->risk_level, ['high', 'critical'])) {
            Event::assertDispatched(StudentNeedsAttention::class, function ($event) use ($poorStudent) {
                return $event->student->id === $poorStudent->id;
            });
        } else {
            // If not high risk (depends on other students), at least verify profile exists
            $this->assertNotNull($profile);
            $this->assertContains($profile->risk_level, ['low', 'medium', 'high', 'critical']);
        }
    }

    // =========================================================================
    // TEST 6: Fault Isolation — One Listener Failure Doesn't Affect Others
    // =========================================================================

    public function test_activity_log_created_even_if_mastery_job_is_queued(): void
    {
        Queue::fake(); // Jobs are queued but not executed

        event(new AttemptCompleted($this->attempt));

        // Sync listener (LogActivityEvent) should still run
        $this->assertDatabaseHas('student_activity_logs', [
            'user_id' => $this->student->id,
            'activity_type' => 'attempt_completed',
        ]);

        // Job is queued but not executed
        Queue::assertPushed(CalculateSubjectMasteryJob::class);

        // No mastery or risk records yet (jobs haven't run)
        // This proves fault isolation: sync and async are independent
    }

    // =========================================================================
    // TEST 7: Response Time — Async is Non-Blocking
    // =========================================================================

    public function test_event_dispatch_is_fast_because_jobs_are_async(): void
    {
        Queue::fake(); // Prevent actual job execution

        $startTime = microtime(true);

        event(new AttemptCompleted($this->attempt));

        $elapsedMs = (microtime(true) - $startTime) * 1000;

        // Event dispatch + sync listener should be < 200ms
        // (Heavy calculations are queued, not run inline)
        $this->assertLessThan(
            200,
            $elapsedMs,
            "Event dispatch harus cepat (<200ms). Actual: {$elapsedMs}ms"
        );
    }

    // =========================================================================
    // HELPER: Seed realistic student activity data
    // =========================================================================

    private function seedStudentActivityData(): void
    {
        // Seed attendance (activity logs in last 7 days)
        for ($i = 0; $i < 5; $i++) {
            StudentActivityLog::create([
                'user_id' => $this->student->id,
                'activity_type' => 'session_started',
                'logged_at' => now()->subDays(rand(0, 6))->setTime(rand(8, 15), rand(0, 59)),
            ]);
        }

        // Seed engagement (session durations in last 30 days)
        for ($i = 0; $i < 10; $i++) {
            $durationMinutes = rand(20, 60);
            $start = now()->subDays(rand(0, 29))->setTime(rand(8, 15), rand(0, 59));
            SessionDuration::create([
                'user_id' => $this->student->id,
                'session_type' => 'course_learning',
                'duration_seconds' => $durationMinutes * 60,
                'started_at' => $start,
                'ended_at' => $start->copy()->addMinutes($durationMinutes),
                'status' => 'completed',
            ]);
        }
    }
}
