<?php

namespace App\Events\SPK;

use App\Events\DomainEvent;
use App\Models\SubjectMastery;

class SubjectMasteryCalculated extends DomainEvent
{
    public function __construct(
        public readonly SubjectMastery $mastery,
    ) {
        parent::__construct();
    }
}
