<?php

namespace App\StructuralCalculation\Beams;

use DomainException;

final class BeamVariableLoadException extends DomainException
{
    public function __construct(public readonly BeamVariableLoadRejectionReason $reason)
    {
        parent::__construct($reason->value);
    }
}
