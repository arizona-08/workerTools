<?php

namespace App\StructuralCalculation\Beams;

/** Aire théorique d'acier tendu imposée par l'équilibre ELU, sans minimum réglementaire. */
final readonly class BeamRequiredTensionReinforcementResult
{
    public const DESIGN_MOMENT_UNIT = BeamBendingMoment::UNIT;

    public const DESIGN_MOMENT_IN_NEWTON_MILLIMETRES_UNIT = 'N·mm';

    public const STEEL_DESIGN_STRENGTH_UNIT = BeamFlexuralSteelDesignStrength::UNIT;

    public const LEVER_ARM_UNIT = BeamLeverArmResult::UNIT;

    public const AREA_UNIT = 'mm²';

    public const FORMULA = 'As_req = MEd_Nmm / (fyd × z)';

    public function __construct(
        public float $designMoment,
        public float $designMomentInNewtonMillimetres,
        public float $steelDesignStrength,
        public float $leverArm,
        public float $steelLeverArmProduct,
        public float $requiredReinforcementArea,
    ) {}
}
