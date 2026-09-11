<?php

namespace App\StructuralCalculation\Beams;

use DomainException;

final class BeamGeometryException extends DomainException
{
    public function __construct(public readonly BeamGeometryRejectionReason $reason)
    {
        parent::__construct($reason->value);
    }
}
