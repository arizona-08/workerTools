<?php

namespace App\StructuralCalculation\Beams;

use DomainException;

final class BeamLongitudinalReinforcementException extends DomainException
{
    public function __construct(public readonly BeamLongitudinalReinforcementRejectionReason $reason)
    {
        parent::__construct($reason->value);
    }
}
