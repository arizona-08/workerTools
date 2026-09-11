<?php

namespace App\StructuralCalculation\Beams;

use DomainException;

final class BeamMaterialsException extends DomainException
{
    public function __construct(public readonly BeamMaterialsRejectionReason $reason)
    {
        parent::__construct($reason->value);
    }
}
