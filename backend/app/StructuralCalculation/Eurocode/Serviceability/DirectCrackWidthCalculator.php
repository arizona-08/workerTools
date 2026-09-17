<?php

namespace App\StructuralCalculation\Eurocode\Serviceability;

use App\StructuralCalculation\Eurocode\Beams\BeamCrackWidthRequirements;

/** Noyau EC2 §7.3.4 commun aux sections rectangulaires avec barres tendues régulièrement espacées. */
final class DirectCrackWidthCalculator
{
    public function calculate(
        float $width,
        float $height,
        float $effectiveDepth,
        float $tensionArea,
        float $barDiameter,
        float $barSpacing,
        float $coverToBar,
        float $neutralAxisDepth,
        float $modularRatio,
        float $steelStress,
        float $steelModulus,
        float $concreteTensileStrength,
        BeamCrackWidthRequirements $requirements,
    ): DirectCrackWidthResult {
        foreach ([$width, $height, $effectiveDepth, $tensionArea, $barDiameter, $barSpacing, $coverToBar, $neutralAxisDepth, $modularRatio, $steelModulus, $concreteTensileStrength] as $value) {
            if (! is_finite($value) || $value <= 0) {
                throw new \InvalidArgumentException('The direct crack-width input is invalid.');
            }
        }
        if (! is_finite($steelStress) || $steelStress < 0 || $effectiveDepth >= $height || $neutralAxisDepth >= $height) {
            throw new \InvalidArgumentException('The direct crack-width input is invalid.');
        }

        $effectiveHeightFromDepth = 2.5 * ($height - $effectiveDepth);
        $effectiveHeightFromNeutralAxis = ($height - $neutralAxisDepth) / 3;
        $effectiveHeightFromHalfDepth = $height / 2;
        $effectiveTensionHeight = min($effectiveHeightFromDepth, $effectiveHeightFromNeutralAxis, $effectiveHeightFromHalfDepth);
        $effectiveTensionArea = $width * $effectiveTensionHeight;
        $effectiveReinforcementRatio = $tensionArea / $effectiveTensionArea;
        $closeSpacingLimit = 5 * ($coverToBar + $barDiameter / 2);
        $spacingFormulaCriterion = $barSpacing <= $closeSpacingLimit ? 'CLOSELY_SPACED_BARS' : 'WIDELY_SPACED_BARS';
        $maximumCrackSpacing = $spacingFormulaCriterion === 'CLOSELY_SPACED_BARS'
            ? $requirements->crackSpacingCoefficient3 * $coverToBar
                + $requirements->crackBondCoefficient * $requirements->crackStrainDistributionCoefficient
                * $requirements->crackSpacingCoefficient4 * $barDiameter / $effectiveReinforcementRatio
            : 1.3 * ($height - $neutralAxisDepth);
        $kt = $requirements->longTermKt;
        $strainDifferenceMain = ($steelStress - $kt * $concreteTensileStrength / $effectiveReinforcementRatio * (1 + $modularRatio * $effectiveReinforcementRatio)) / $steelModulus;
        $strainDifferenceMinimum = 0.6 * $steelStress / $steelModulus;
        $strainDifference = max($strainDifferenceMain, $strainDifferenceMinimum);

        foreach ([$effectiveTensionHeight, $effectiveTensionArea, $effectiveReinforcementRatio, $maximumCrackSpacing] as $value) {
            if (! is_finite($value) || $value <= 0) {
                throw new \InvalidArgumentException('The direct crack-width result is invalid.');
            }
        }
        if (! is_finite($strainDifference) || $strainDifference < 0) {
            throw new \InvalidArgumentException('The direct crack-width result is invalid.');
        }

        return new DirectCrackWidthResult(
            $effectiveHeightFromDepth, $effectiveHeightFromNeutralAxis, $effectiveHeightFromHalfDepth,
            $effectiveTensionHeight, $effectiveTensionArea, $effectiveReinforcementRatio,
            $spacingFormulaCriterion, $maximumCrackSpacing, $strainDifferenceMain,
            $strainDifferenceMinimum, $strainDifference,
            $strainDifferenceMain >= $strainDifferenceMinimum ? 'MAIN_STRAIN_EXPRESSION' : 'MINIMUM_STRAIN_DIFFERENCE',
            $maximumCrackSpacing * $strainDifference,
        );
    }
}
