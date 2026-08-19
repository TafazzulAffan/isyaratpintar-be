<?php

namespace App\Events\SPK;

use App\Events\DomainEvent;
use App\Models\User;

class StudentNeedsAttention extends DomainEvent
{
    public function __construct(
        public readonly User $student,
        public readonly array $reasons,
        public readonly string $severity = 'high', // low, medium, high, critical
    ) {
        parent::__construct();
    }
}
