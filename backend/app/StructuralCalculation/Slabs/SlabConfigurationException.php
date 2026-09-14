<?php

namespace App\StructuralCalculation\Slabs;

use DomainException;

final class SlabConfigurationException extends DomainException
{
    public function __construct(public readonly SlabConfigurationRejectionReason $reason)
    {
        parent::__construct($reason->value);
    }
}
