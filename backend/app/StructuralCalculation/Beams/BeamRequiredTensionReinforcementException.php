<?php

namespace App\StructuralCalculation\Beams;

use DomainException;

final class BeamRequiredTensionReinforcementException extends DomainException
{
    public function __construct(public readonly BeamRequiredTensionReinforcementRejectionReason $reason)
    {
        parent::__construct($reason->value);
    }
}
