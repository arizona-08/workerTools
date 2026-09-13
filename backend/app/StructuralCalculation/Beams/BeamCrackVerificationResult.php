<?php

namespace App\StructuralCalculation\Beams;

/** Résultat traçable de fissuration directe EC2 §7.3.4, sans flèche. */
final readonly class BeamCrackVerificationResult
{
    public const LENGTH_UNIT = 'mm';

    public const AREA_UNIT = 'mm²';

    public const STRESS_UNIT = 'MPa';

    public function __construct(
        public string $loadCombination,
        public BeamCrackLoadDuration $loadDuration,
        public string $sectionModel,
        public float $coverToLongitudinalBar,
        public float $barDiameter,
        public int $barCount,
        public float $barSpacing,
        public float $clearBarSpacing,
        public float $effectiveTensionHeightFromDepth,
        public float $effectiveTensionHeightFromNeutralAxis,
        public float $effectiveTensionHeightFromHalfDepth,
        public float $effectiveTensionHeight,
        public float $effectiveTensionArea,
        public float $effectiveReinforcementRatio,
        public float $effectiveConcreteTensileStrength,
        public float $modularRatio,
        public float $steelStress,
        public float $kt,
        public float $crackBondCoefficient,
        public float $crackStrainDistributionCoefficient,
        public float $crackSpacingCoefficient3,
        public float $crackSpacingCoefficient4,
        public float $maximumCrackSpacing,
        public BeamCrackSpacingFormulaCriterion $spacingFormulaCriterion,
        public float $strainDifferenceMain,
        public float $strainDifferenceMinimum,
        public float $strainDifference,
        public BeamCrackStrainDifferenceCriterion $strainDifferenceCriterion,
        public float $crackWidth,
        public float $crackWidthLimit,
        public float $utilization,
        public BeamCrackVerificationStatus $status,
    ) {}
}
