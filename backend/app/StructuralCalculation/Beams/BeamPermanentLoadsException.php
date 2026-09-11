<?php

namespace App\StructuralCalculation\Beams;

use DomainException;

final class BeamPermanentLoadsException extends DomainException
{
    public function __construct(public readonly BeamPermanentLoadsRejectionReason $reason)
    {
        parent::__construct($reason->value);
    }
}
