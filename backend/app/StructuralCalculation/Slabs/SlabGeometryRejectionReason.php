<?php

namespace App\StructuralCalculation\Slabs;

enum SlabGeometryRejectionReason: string
{
    case MISSING_EFFECTIVE_SPAN = 'MISSING_EFFECTIVE_SPAN';
    case INVALID_EFFECTIVE_SPAN = 'INVALID_EFFECTIVE_SPAN';
    case MISSING_THICKNESS = 'MISSING_THICKNESS';
    case INVALID_THICKNESS = 'INVALID_THICKNESS';
}
