<?php

namespace App\Events\Learning;

use App\Events\DomainEvent;
use App\Models\Lesson;
use App\Models\User;

class LessonCompleted extends DomainEvent
{
    public function __construct(
        public readonly User $user,
        public readonly Lesson $lesson,
    ) {
        parent::__construct();
    }
}
