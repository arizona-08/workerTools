<?php

namespace App\StructuralCalculation\Beams;

use DomainException;

final class BeamShearForceException extends DomainException
{
    public function __construct(public readonly BeamShearForceRejectionReason $reason)
    {
        parent::__construct($reason->value);
    }
}
