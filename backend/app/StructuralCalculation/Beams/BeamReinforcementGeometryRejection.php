<?php

namespace App\StructuralCalculation\Beams;

use DomainException;

final class BeamReinforcementGeometryRejection extends DomainException
{
    public function __construct(public readonly string $reason)
    {
        parent::__construct($reason);
    }
}
