<?php

namespace App\StructuralCalculation\Beams;

use DomainException;

final class BeamCalculationInputException extends DomainException
{
    public function __construct(public readonly BeamCalculationInputRejectionReason $reason)
    {
        parent::__construct($reason->value);
    }
}
