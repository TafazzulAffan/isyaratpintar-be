<?php

namespace App\Events\Session;

use App\Events\DomainEvent;
use App\Models\User;

class SessionEnded extends DomainEvent
{
    public function __construct(
        public readonly User $user,
        public readonly int $durationSeconds,
    ) {
        parent::__construct();
    }
}
