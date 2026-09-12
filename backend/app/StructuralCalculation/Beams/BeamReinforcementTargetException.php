<?php

namespace App\StructuralCalculation\Beams;

use DomainException;

final class BeamReinforcementTargetException extends DomainException
{
    public function __construct(public readonly BeamReinforcementTargetRejectionReason $reason)
    {
        parent::__construct($reason->value);
    }
}
