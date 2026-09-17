<?php

namespace App\StructuralCalculation\Beams;

use DomainException;

final class SelfWeightCalculationException extends DomainException
{
    public function __construct(public readonly SelfWeightCalculationRejectionReason $reason)
    {
        parent::__construct($reason->value);
    }
}
