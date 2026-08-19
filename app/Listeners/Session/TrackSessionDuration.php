<?php

namespace App\Listeners\Session;

use App\Events\Session\SessionStarted;
use App\Events\Session\SessionEnded;
use App\Events\Assessment\AssessmentStarted;
use App\Models\SessionDuration;
use Illuminate\Support\Facades\Cache;

class TrackSessionDuration
{
    /**
     * Handle the event.
     * Track session start/end and calculate duration
     */
    public function handle(object $event): void
    {
        match (get_class($event)) {
            SessionStarted::class => $this->startSession($event),
            SessionEnded::class => $this->endSession($event),
            AssessmentStarted::class => $this->startAssessmentSession($event),
            default => null,
        };
    }

    /**
     * Start a new session
     */
    private function startSession(SessionStarted $event): void
    {
        $cacheKey = "session_start_{$event->user->id}";
        
        SessionDuration::create([
            'user_id' => $event->user->id,
            'started_at' => now(),
            'session_type' => 'learning',
            'entity_id' => $event->entityId,
        ]);

        // Cache session start for reference
        Cache::put($cacheKey, now(), now()->addHours(24));
    }

    /**
     * End a session
     */
    private function endSession(SessionEnded $event): void
    {
        // Find the latest active session for this user
        $activeSession = SessionDuration::forUser($event->user->id)
            ->active()
            ->latest('started_at')
            ->first();

        if ($activeSession) {
            $activeSession->update([
                'ended_at' => now(),
                'duration_seconds' => now()->diffInSeconds($activeSession->started_at),
            ]);
        }
    }

    /**
     * Track assessment session start
     */
    private function startAssessmentSession(AssessmentStarted $event): void
    {
        SessionDuration::create([
            'user_id' => $event->attempt->user_id,
            'started_at' => now(),
            'session_type' => 'assessment',
            'entity_id' => $event->attempt->id,
        ]);
    }
}
