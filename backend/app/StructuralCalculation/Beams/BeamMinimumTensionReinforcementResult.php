<?php

namespace App\StructuralCalculation\Beams;

use App\StructuralCalculation\Materials\Concrete\ConcreteStrengthClass;
use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementSteelGrade;

/** Armature minimale EC2 traçable, distincte de l'armature d'équilibre As_req. */
final readonly class BeamMinimumTensionReinforcementResult
{
    public const STRENGTH_UNIT = 'MPa';

    public const LENGTH_UNIT = 'mm';

    public const AREA_UNIT = 'mm²';

    public const FORMULA = 'As_min = max(0.26 × fctm / fyk × bt × d, 0.0013 × bt × d)';

    public function __construct(
        public ConcreteStrengthClass $concreteClass,
        public float $meanTensileConcreteStrength,
        public ReinforcementSteelGrade $steelGrade,
        public float $characteristicSteelStrength,
        public float $tensionZoneMeanWidth,
        public float $effectiveDepth,
        public float $strengthBasedMinimum,
        public float $absoluteMinimum,
        public float $requiredMinimum,
        public MinimumTensionReinforcementGoverningCriterion $governingCriterion,
    ) {}
}
