<?php

namespace App\StructuralCalculation\Beams;

enum BeamGeometryRejectionReason: string
{
    case MISSING_EFFECTIVE_SPAN = 'MISSING_EFFECTIVE_SPAN';
    case INVALID_EFFECTIVE_SPAN = 'INVALID_EFFECTIVE_SPAN';
    case MISSING_WIDTH = 'MISSING_WIDTH';
    case INVALID_WIDTH = 'INVALID_WIDTH';
    case MISSING_HEIGHT = 'MISSING_HEIGHT';
    case INVALID_HEIGHT = 'INVALID_HEIGHT';
}
