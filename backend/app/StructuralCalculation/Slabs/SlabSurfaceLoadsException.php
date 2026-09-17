<?php

namespace App\StructuralCalculation\Slabs;

use DomainException;

final class SlabSurfaceLoadsException extends DomainException
{
    public function __construct(public readonly SlabSurfaceLoadsRejectionReason $reason)
    {
        parent::__construct($reason->value);
    }
}
