<?php

namespace App\StructuralCalculation\Beams;

use DomainException;

/** Échec contrôlé lorsqu'un sous-module connu n'a pas encore son analyse. */
final class BeamAnalysisException extends DomainException
{
    public function __construct(public readonly BeamAnalysisRejectionReason $reason)
    {
        parent::__construct($reason->value);
    }
}
