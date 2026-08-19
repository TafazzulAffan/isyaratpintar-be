<?php

namespace App\Events\Learning;

use App\Events\DomainEvent;
use App\Models\User;

class TaskSubmitted extends DomainEvent
{
    public function __construct(
        public readonly User $user,
        public readonly ?int $taskId = null,
        public readonly ?string $taskType = null,
    ) {
        parent::__construct();
    }
}
