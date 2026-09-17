<?php

namespace App\StructuralCalculation\Beams;

use DomainException;

final class BeamReducedMomentException extends DomainException
{
    public function __construct(public readonly BeamReducedMomentRejectionReason $reason)
    {
        parent::__construct($reason->value);
    }
}
