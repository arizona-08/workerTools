<?php

namespace App\StructuralCalculation\Beams;

use DomainException;

final class BeamServiceStressVerificationException extends DomainException
{
    public function __construct(public readonly BeamServiceStressVerificationRejectionReason $reason)
    {
        parent::__construct($reason->value);
    }
}
