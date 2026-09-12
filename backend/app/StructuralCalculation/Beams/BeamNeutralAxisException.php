<?php

namespace App\StructuralCalculation\Beams;

use DomainException;

final class BeamNeutralAxisException extends DomainException
{
    public function __construct(public readonly BeamNeutralAxisRejectionReason $reason)
    {
        parent::__construct($reason->value);
    }
}
