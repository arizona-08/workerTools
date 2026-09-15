<?php

namespace App\StructuralCalculation\Eurocode\Serviceability;

/** Valeurs intermédiaires communes du calcul direct de largeur de fissure EC2 §7.3.4. */
final readonly class DirectCrackWidthResult
{
    public function __construct(
        public float $effectiveTensionHeightFromDepth,
        public float $effectiveTensionHeightFromNeutralAxis,
        public float $effectiveTensionHeightFromHalfDepth,
        public float $effectiveTensionHeight,
        public float $effectiveTensionArea,
        public float $effectiveReinforcementRatio,
        public string $spacingFormulaCriterion,
        public float $maximumCrackSpacing,
        public float $strainDifferenceMain,
        public float $strainDifferenceMinimum,
        public float $strainDifference,
        public string $strainDifferenceCriterion,
        public float $crackWidth,
    ) {}
}
