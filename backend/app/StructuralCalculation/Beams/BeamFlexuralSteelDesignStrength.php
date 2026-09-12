<?php

namespace App\StructuralCalculation\Beams;

use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementSteelGrade;

/** Résistance de calcul acier, avec les données matériau et profil qui la composent. */
final readonly class BeamFlexuralSteelDesignStrength
{
    public const UNIT = 'MPa';

    public function __construct(
        public ReinforcementSteelGrade $steelGrade,
        public float $fyk,
        public float $gammaS,
        public float $fyd,
    ) {}
}
