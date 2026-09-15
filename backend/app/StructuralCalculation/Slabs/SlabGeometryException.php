<?php

namespace App\StructuralCalculation\Slabs;

use DomainException;

final class SlabGeometryException extends DomainException
{
    public function __construct(public readonly SlabGeometryRejectionReason $reason)
    {
        parent::__construct($reason->value);
    }
}
