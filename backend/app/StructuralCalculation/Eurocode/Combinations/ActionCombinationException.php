<?php

namespace App\StructuralCalculation\Eurocode\Combinations;

use DomainException;

final class ActionCombinationException extends DomainException
{
    public function __construct(public readonly ActionCombinationRejectionReason $reason)
    {
        parent::__construct($reason->value);
    }
}
