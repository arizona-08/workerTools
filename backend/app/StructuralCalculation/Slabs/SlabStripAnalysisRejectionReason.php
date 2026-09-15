<?php

namespace App\StructuralCalculation\Slabs;

enum SlabStripAnalysisRejectionReason: string
{
    case UNSUPPORTED_STRUCTURAL_SYSTEM = 'UNSUPPORTED_STRUCTURAL_SYSTEM';
    case UNSUPPORTED_LOAD_MODEL = 'UNSUPPORTED_LOAD_MODEL';
    case INVALID_EFFECTIVE_SPAN = 'INVALID_EFFECTIVE_SPAN';
}
