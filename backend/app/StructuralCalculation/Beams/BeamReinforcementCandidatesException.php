<?php

namespace App\StructuralCalculation\Beams;

use DomainException;

final class BeamReinforcementCandidatesException extends DomainException
{
    public function __construct(public readonly BeamReinforcementCandidatesRejectionReason $reason)
    {
        parent::__construct($reason->value);
    }
}
