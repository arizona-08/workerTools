<?php

namespace App\StructuralCalculation\Beams;

use DomainException;

final class CharacteristicActionsCalculationException extends DomainException
{
    public function __construct(public readonly CharacteristicActionsCalculationRejectionReason $reason)
    {
        parent::__construct($reason->value);
    }
}
