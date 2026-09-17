<?php

namespace App\StructuralCalculation\Slabs;

use App\StructuralCalculation\Eurocode\Cover\CoverCalculationResult;

/** Résultat de flexion ELU de la bande de 1 m, avant tout choix de ferraillage discret. */
final readonly class SlabUlsFlexureResult
{
    public const REINFORCEMENT_AREA_UNIT = 'mm²/m';

    public const DESIGN_MOMENT_SOURCE = 'SLAB-06';

    public const DESIGN_REINFORCEMENT_FORMULA = 'As_design = max(As_req, As_min)';

    public function __construct(
        public float $designMoment,
        public float $designMomentInNewtonMillimetres,
        public float $sectionWidth,
        public SlabEffectiveDepthResult $effectiveDepth,
        public CoverCalculationResult $cover,
        public float $concreteDesignStrength,
        public float $steelDesignStrength,
        public float $meanTensileConcreteStrength,
        public float $characteristicSteelStrength,
        public float $reducedMoment,
        public float $neutralAxisRatio,
        public float $neutralAxisDepth,
        public float $leverArm,
        public float $requiredReinforcementArea,
        public float $minimumStrengthBasedReinforcementArea,
        public float $minimumAbsoluteReinforcementArea,
        public float $minimumReinforcementArea,
        public float $designReinforcementArea,
        public SlabReinforcementTargetGoverningRequirement $governingRequirement,
    ) {}
}
