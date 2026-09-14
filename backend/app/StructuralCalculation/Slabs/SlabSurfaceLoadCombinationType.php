<?php

namespace App\StructuralCalculation\Slabs;

enum SlabSurfaceLoadCombinationType: string
{
    case ULTIMATE = 'ULS_FUNDAMENTAL';
    case SERVICEABILITY_CHARACTERISTIC = 'SLS_CHARACTERISTIC';
    case SERVICEABILITY_FREQUENT = 'SLS_FREQUENT';
    case SERVICEABILITY_QUASI_PERMANENT = 'SLS_QUASI_PERMANENT';
}
