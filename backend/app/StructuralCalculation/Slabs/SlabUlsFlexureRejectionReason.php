<?php

namespace App\StructuralCalculation\Slabs;

enum SlabUlsFlexureRejectionReason: string
{
    case INVALID_INPUT = 'INVALID_INPUT';
    case NON_POSITIVE_EFFECTIVE_DEPTH = 'NON_POSITIVE_EFFECTIVE_DEPTH';
    case CALCULATION_METHOD_NOT_SUPPORTED = 'CALCULATION_METHOD_NOT_SUPPORTED';
}
