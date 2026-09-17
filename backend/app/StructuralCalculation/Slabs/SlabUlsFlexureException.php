<?php

namespace App\StructuralCalculation\Slabs;

use DomainException;

final class SlabUlsFlexureException extends DomainException
{
    public function __construct(public readonly SlabUlsFlexureRejectionReason $reason)
    {
        parent::__construct($reason->value);
    }
}
