<?php

namespace App\StructuralCalculation\Beams;

use DomainException;

final class BeamShearReinforcementDesignException extends DomainException
{
    public function __construct(public readonly BeamShearReinforcementDesignRejectionReason $reason)
    {
        parent::__construct($reason->value);
    }
}
