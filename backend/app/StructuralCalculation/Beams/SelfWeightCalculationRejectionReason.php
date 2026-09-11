<?php

namespace App\StructuralCalculation\Beams;

enum SelfWeightCalculationRejectionReason: string
{
    case INVALID_WIDTH = 'INVALID_WIDTH';
    case INVALID_HEIGHT = 'INVALID_HEIGHT';
    case INVALID_REINFORCED_CONCRETE_UNIT_WEIGHT = 'INVALID_REINFORCED_CONCRETE_UNIT_WEIGHT';
}
