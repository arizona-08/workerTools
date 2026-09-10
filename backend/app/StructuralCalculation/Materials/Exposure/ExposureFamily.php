<?php

namespace App\StructuralCalculation\Materials\Exposure;

enum ExposureFamily: string
{
    case NO_RISK = 'NO_RISK';
    case CARBONATION = 'CARBONATION';
    case CHLORIDES_NOT_SEAWATER = 'CHLORIDES_NOT_SEAWATER';
    case CHLORIDES_SEAWATER = 'CHLORIDES_SEAWATER';
    case FREEZE_THAW = 'FREEZE_THAW';
    case CHEMICAL_ATTACK = 'CHEMICAL_ATTACK';
}
