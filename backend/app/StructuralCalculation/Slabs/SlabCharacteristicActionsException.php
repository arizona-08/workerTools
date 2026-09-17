<?php

namespace App\StructuralCalculation\Slabs;

use DomainException;

final class SlabCharacteristicActionsException extends DomainException
{
    public function __construct(public readonly SlabCharacteristicActionsRejectionReason $reason)
    {
        parent::__construct($reason->value);
    }
}
