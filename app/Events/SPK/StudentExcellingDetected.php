<?php

namespace App\Events\SPK;

use App\Events\DomainEvent;
use App\Models\User;

class StudentExcellingDetected extends DomainEvent
{
    public function __construct(
        public readonly User $student,
        public readonly array $achievementAreas,
    ) {
        parent::__construct();
    }
}
