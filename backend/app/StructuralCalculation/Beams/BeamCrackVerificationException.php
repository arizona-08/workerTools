<?php

namespace App\StructuralCalculation\Beams;

use DomainException;

final class BeamCrackVerificationException extends DomainException
{
    public function __construct(public readonly BeamCrackVerificationRejectionReason $reason)
    {
        parent::__construct($reason->value);
    }
}
