<?php

namespace App\StructuralCalculation\Beams;

use DomainException;

final class BeamServiceabilityCombinationException extends DomainException
{
    public function __construct(public readonly BeamServiceabilityCombinationRejectionReason $reason)
    {
        parent::__construct($reason->value);
    }
}
