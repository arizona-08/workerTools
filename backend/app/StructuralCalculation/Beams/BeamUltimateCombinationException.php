<?php

namespace App\StructuralCalculation\Beams;

use DomainException;

final class BeamUltimateCombinationException extends DomainException
{
    public function __construct(public readonly BeamUltimateCombinationRejectionReason $reason)
    {
        parent::__construct($reason->value);
    }
}
