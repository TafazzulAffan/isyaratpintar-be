<?php

namespace App\Events\Session;

use App\Events\DomainEvent;
use App\Models\User;

class SessionStarted extends DomainEvent
{
    public function __construct(
        public readonly User $user,
        public readonly ?string $entityType = null,
        public readonly ?int $entityId = null,
    ) {
        parent::__construct();
    }
}
