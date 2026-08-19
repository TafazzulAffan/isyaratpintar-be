<?php

namespace App\Events\Assessment;

use App\Events\DomainEvent;
use App\Models\AssessmentAttempt;

class AssessmentStarted extends DomainEvent
{
    public function __construct(
        public readonly AssessmentAttempt $attempt,
    ) {
        parent::__construct();
    }
}
