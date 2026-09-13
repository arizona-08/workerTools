<?php

namespace App\StructuralCalculation\Beams;

use DomainException;

final class BeamDeflectionVerificationException extends DomainException
{
    public function __construct(public readonly BeamDeflectionVerificationRejectionReason $reason)
    {
        parent::__construct($reason->value);
    }
}
