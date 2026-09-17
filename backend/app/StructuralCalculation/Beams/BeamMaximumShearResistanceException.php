<?php

namespace App\StructuralCalculation\Beams;

use DomainException;

final class BeamMaximumShearResistanceException extends DomainException
{
    public function __construct(public readonly BeamMaximumShearResistanceRejectionReason $reason)
    {
        parent::__construct($reason->value);
    }
}
