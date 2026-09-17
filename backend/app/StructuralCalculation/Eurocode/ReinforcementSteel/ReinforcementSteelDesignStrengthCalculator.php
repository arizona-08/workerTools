<?php

namespace App\StructuralCalculation\Eurocode\ReinforcementSteel;

use App\StructuralCalculation\Eurocode\Profiles\DesignCodeProfile;
use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementSteelProperties;

/** Dérive fyd (MPa) du matériau et du profil sélectionnés. */
final class ReinforcementSteelDesignStrengthCalculator
{
    public function calculate(ReinforcementSteelProperties $steel, DesignCodeProfile $profile): float
    {
        return $steel->fyk / $profile->materialSafetyFactors->gammaS;
    }
}
