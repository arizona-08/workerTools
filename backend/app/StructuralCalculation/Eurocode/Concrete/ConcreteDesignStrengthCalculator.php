<?php

namespace App\StructuralCalculation\Eurocode\Concrete;

use App\StructuralCalculation\Eurocode\Profiles\DesignCodeProfile;
use App\StructuralCalculation\Materials\Concrete\ConcreteProperties;

/** Dérive fcd (MPa) du matériau et du profil sélectionnés. */
final class ConcreteDesignStrengthCalculator
{
    public function calculate(ConcreteProperties $concrete, DesignCodeProfile $profile): float
    {
        $factors = $profile->materialSafetyFactors;

        return $factors->alphaCc * $concrete->fck / $factors->gammaC;
    }
}
