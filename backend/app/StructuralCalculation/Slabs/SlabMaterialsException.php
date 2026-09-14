<?php

namespace App\StructuralCalculation\Slabs;

use DomainException;

final class SlabMaterialsException extends DomainException
{
    public function __construct(public readonly SlabMaterialsRejectionReason $reason, ?\Throwable $previous = null)
    {
        parent::__construct($reason->value, previous: $previous);
    }
}
