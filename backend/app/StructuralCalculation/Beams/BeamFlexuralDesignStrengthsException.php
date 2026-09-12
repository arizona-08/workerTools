<?php

namespace App\StructuralCalculation\Beams;

use DomainException;

final class BeamFlexuralDesignStrengthsException extends DomainException
{
    public function __construct(public readonly BeamFlexuralDesignStrengthsRejectionReason $reason)
    {
        parent::__construct($reason->value);
    }
}
