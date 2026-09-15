<?php

namespace App\StructuralCalculation\Slabs;

use DomainException;

final class SlabStripLinearLoadsException extends DomainException
{
    public function __construct(public readonly SlabStripLinearLoadsRejectionReason $reason)
    {
        parent::__construct($reason->value);
    }
}
