<?php

namespace App\Events;

use DateTimeImmutable;

abstract class DomainEvent
{
    public function __construct(
        public readonly DateTimeImmutable $occurredAt = new DateTimeImmutable()
    ) {}
}
