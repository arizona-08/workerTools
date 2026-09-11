<?php

namespace App\StructuralCalculation\Beams;

enum CharacteristicActionsCalculationRejectionReason: string
{
    case INVALID_SELF_WEIGHT = 'INVALID_SELF_WEIGHT';
    case INVALID_ADDITIONAL_PERMANENT_LOAD = 'INVALID_ADDITIONAL_PERMANENT_LOAD';
    case INVALID_CHARACTERISTIC_VARIABLE_LOAD = 'INVALID_CHARACTERISTIC_VARIABLE_LOAD';
}
