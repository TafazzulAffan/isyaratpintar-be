<?php

namespace App\Events\Assessment;

use App\Events\DomainEvent;
use App\Models\AttemptAnswer;
use App\Models\AssessmentAttempt;

class AnswerSubmitted extends DomainEvent
{
    public function __construct(
        public readonly AssessmentAttempt $attempt,
        public readonly AttemptAnswer $answer,
    ) {
        parent::__construct();
    }
}
