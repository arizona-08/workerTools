<?php

namespace App\StructuralCalculation\Slabs;

use DomainException;

final class SlabStripAnalysisException extends DomainException
{
    public function __construct(public readonly SlabStripAnalysisRejectionReason $reason)
    {
        parent::__construct($reason->value);
    }
}
