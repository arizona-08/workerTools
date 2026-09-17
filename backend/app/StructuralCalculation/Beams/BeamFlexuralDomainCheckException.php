<?php

namespace App\StructuralCalculation\Beams;

use DomainException;

final class BeamFlexuralDomainCheckException extends DomainException
{
    public function __construct(public readonly BeamFlexuralDomainCheckRejectionReason $reason)
    {
        parent::__construct($reason->value);
    }
}
