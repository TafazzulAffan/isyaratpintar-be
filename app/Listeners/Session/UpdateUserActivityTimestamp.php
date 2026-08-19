<?php

namespace App\Listeners\Session;

use App\Events\Session\SessionEnded;

class UpdateUserActivityTimestamp
{
    /**
     * Handle the event.
     * Update last_activity_at and activities_count on User model
     */
    public function handle(SessionEnded $event): void
    {
        $event->user->update([
            'last_activity_at' => now(),
            'activities_count' => $event->user->activities_count + 1,
            'total_learning_minutes' => $event->user->total_learning_minutes + intval($event->durationSeconds / 60),
        ]);
    }
}
