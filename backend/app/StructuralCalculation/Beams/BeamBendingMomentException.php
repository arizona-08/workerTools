<?php

namespace App\StructuralCalculation\Beams;

use DomainException;

final class BeamBendingMomentException extends DomainException
{
    public function __construct(public readonly BeamBendingMomentRejectionReason $reason)
    {
        parent::__construct($reason->value);
    }
}
