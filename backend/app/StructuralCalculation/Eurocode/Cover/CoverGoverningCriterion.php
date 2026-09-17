<?php

namespace App\StructuralCalculation\Eurocode\Cover;

enum CoverGoverningCriterion: string
{
    case BOND = 'BOND';
    case DURABILITY = 'DURABILITY';
    case MINIMUM_10_MM = 'MINIMUM_10_MM';
}
