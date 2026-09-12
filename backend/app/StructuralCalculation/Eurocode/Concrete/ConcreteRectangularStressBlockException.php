<?php

namespace App\StructuralCalculation\Eurocode\Concrete;

use DomainException;

final class ConcreteRectangularStressBlockException extends DomainException
{
    public function __construct(public readonly ConcreteRectangularStressBlockRejectionReason $reason)
    {
        parent::__construct($reason->value);
    }
}
