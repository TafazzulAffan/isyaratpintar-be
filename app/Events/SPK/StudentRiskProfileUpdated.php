<?php

namespace App\Events\SPK;

use App\Events\DomainEvent;
use App\Models\StudentRiskProfile;

class StudentRiskProfileUpdated extends DomainEvent
{
    public function __construct(
        public readonly StudentRiskProfile $riskProfile,
    ) {
        parent::__construct();
    }
}
