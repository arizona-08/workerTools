<?php

namespace App\StructuralCalculation\Beams;

use DomainException;

final class BeamConcreteShearResistanceException extends DomainException
{
    public function __construct(public readonly BeamConcreteShearResistanceRejectionReason $reason)
    {
        parent::__construct($reason->value);
    }
}
