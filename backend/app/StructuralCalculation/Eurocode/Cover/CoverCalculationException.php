<?php

namespace App\StructuralCalculation\Eurocode\Cover;

use DomainException;

final class CoverCalculationException extends DomainException
{
    public function __construct(public readonly CoverCalculationRejectionReason $reason)
    {
        parent::__construct($reason->value);
    }
}
