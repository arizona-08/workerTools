<?php

namespace App\StructuralCalculation\Beams;

use DomainException;

final class BeamEffectiveDepthException extends DomainException
{
    public function __construct(public readonly BeamEffectiveDepthRejectionReason $reason)
    {
        parent::__construct($reason->value);
    }
}
