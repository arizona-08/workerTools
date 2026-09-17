<?php

namespace App\StructuralCalculation\Eurocode\Profiles;

/** Expressions ELS EN 1990 couvertes par le V1 Poutre. */
enum ServiceabilityCombinationExpression: string
{
    case EN1990_6_14 = 'EN1990_6_14';
    case EN1990_6_15 = 'EN1990_6_15';
    case EN1990_6_16 = 'EN1990_6_16';
}
