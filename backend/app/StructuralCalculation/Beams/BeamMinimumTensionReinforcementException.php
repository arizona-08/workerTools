<?php

namespace App\StructuralCalculation\Beams;

use DomainException;

final class BeamMinimumTensionReinforcementException extends DomainException
{
    public function __construct(public readonly BeamMinimumTensionReinforcementRejectionReason $reason)
    {
        parent::__construct($reason->value);
    }
}
