<?php

namespace App\StructuralCalculation\Eurocode\Concrete;

use DomainException;

final class ConcreteUltimateStrainException extends DomainException
{
    public function __construct(public readonly ConcreteUltimateStrainRejectionReason $reason)
    {
        parent::__construct($reason->value);
    }
}
