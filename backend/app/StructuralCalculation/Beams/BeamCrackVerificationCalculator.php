<?php

namespace App\StructuralCalculation\Beams;

use App\StructuralCalculation\Eurocode\Beams\BeamCrackWidthRequirements;
use App\StructuralCalculation\Eurocode\Profiles\DesignCodeProfile;
use App\StructuralCalculation\Materials\Concrete\ConcreteProperties;
use App\StructuralCalculation\Materials\Exposure\ExposureClassCode;
use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementSteelProperties;

/** Vérifie wk à la combinaison quasi-permanente, selon EC2 §7.3.4. */
final class BeamCrackVerificationCalculator
{
    public function calculate(
        BeamGeometry $geometry,
        BeamEffectiveDepthResult $effectiveDepth,
        BeamReinforcementProposalCandidate $longitudinalReinforcement,
        BeamStirrupProposalResult $stirrupProposals,
        BeamServiceStressVerificationResult $serviceStresses,
        ConcreteProperties $concrete,
        ReinforcementSteelProperties $steel,
        ExposureClassCode $exposureClass,
        DesignCodeProfile $profile,
    ): BeamCrackVerificationResult {
        $this->ensurePositiveFinite($geometry->width, BeamCrackVerificationRejectionReason::INVALID_SECTION_WIDTH);
        $this->ensurePositiveFinite($geometry->height, BeamCrackVerificationRejectionReason::INVALID_SECTION_HEIGHT);
        $this->ensurePositiveFinite($effectiveDepth->effectiveDepth, BeamCrackVerificationRejectionReason::INVALID_EFFECTIVE_DEPTH);
        if ($effectiveDepth->effectiveDepth >= $geometry->height) {
            throw new BeamCrackVerificationException(BeamCrackVerificationRejectionReason::INVALID_EFFECTIVE_DEPTH);
        }
        $this->ensurePositiveFinite($effectiveDepth->nominalCover, BeamCrackVerificationRejectionReason::INVALID_NOMINAL_COVER);
        $this->ensurePositiveFinite($longitudinalReinforcement->providedArea, BeamCrackVerificationRejectionReason::INVALID_TENSION_REINFORCEMENT);
        $this->ensurePositiveFinite($longitudinalReinforcement->barDiameter, BeamCrackVerificationRejectionReason::INVALID_TENSION_REINFORCEMENT);
        if ($longitudinalReinforcement->barCount < 2) {
            throw new BeamCrackVerificationException(BeamCrackVerificationRejectionReason::INVALID_BAR_LAYOUT);
        }
        $this->ensurePositiveFinite($steel->es, BeamCrackVerificationRejectionReason::INVALID_STEEL_MODULUS);
        $this->ensureNonNegativeFinite($concrete->fctm, BeamCrackVerificationRejectionReason::INVALID_CONCRETE_TENSILE_STRENGTH);

        $x = $serviceStresses->crackedNeutralAxisDepth;
        $alpha = $serviceStresses->modularRatio;
        $steelStress = $serviceStresses->steelQuasiPermanent->stress;
        if ($x === null || ! is_finite($x) || $x <= 0 || $x >= $geometry->height) {
            throw new BeamCrackVerificationException(BeamCrackVerificationRejectionReason::INVALID_CRACKED_NEUTRAL_AXIS);
        }
        $this->ensurePositiveFinite($alpha ?? 0, BeamCrackVerificationRejectionReason::INVALID_MODULAR_RATIO);
        $this->ensureNonNegativeFinite($steelStress ?? -1, BeamCrackVerificationRejectionReason::INVALID_STEEL_STRESS);

        $stirrup = $stirrupProposals->recommendedCandidate;
        if ($stirrup === null) {
            throw new BeamCrackVerificationException(BeamCrackVerificationRejectionReason::MISSING_RECOMMENDED_STIRRUP);
        }
        $this->ensurePositiveFinite($stirrup->barDiameter, BeamCrackVerificationRejectionReason::INVALID_TRANSVERSE_BAR_DIAMETER);

        $requirements = $profile->beamCrackWidthRequirements;
        $this->ensureRequirements($requirements);
        $crackWidthLimit = $requirements->crackWidthLimitFor($exposureClass);
        if ($crackWidthLimit === null) {
            throw new BeamCrackVerificationException(BeamCrackVerificationRejectionReason::UNSUPPORTED_CRACK_WIDTH_EXPOSURE_CLASS);
        }
        $this->ensurePositiveFinite($crackWidthLimit, BeamCrackVerificationRejectionReason::INVALID_CRACK_WIDTH_LIMIT);

        $coverToLongitudinalBar = $effectiveDepth->nominalCover + $stirrup->barDiameter;
        $barDiameter = $longitudinalReinforcement->barDiameter;
        $barSpacing = ($geometry->width - 2 * ($coverToLongitudinalBar + $barDiameter / 2)) / ($longitudinalReinforcement->barCount - 1);
        $this->ensurePositiveFinite($barSpacing, BeamCrackVerificationRejectionReason::INVALID_BAR_LAYOUT);
        $clearBarSpacing = $barSpacing - $barDiameter;

        $effectiveHeightFromDepth = 2.5 * ($geometry->height - $effectiveDepth->effectiveDepth);
        $effectiveHeightFromNeutralAxis = ($geometry->height - $x) / 3;
        $effectiveHeightFromHalfDepth = $geometry->height / 2;
        $effectiveTensionHeight = min($effectiveHeightFromDepth, $effectiveHeightFromNeutralAxis, $effectiveHeightFromHalfDepth);
        $this->ensurePositiveFinite($effectiveTensionHeight, BeamCrackVerificationRejectionReason::INVALID_EFFECTIVE_DEPTH);
        $effectiveTensionArea = $geometry->width * $effectiveTensionHeight;
        $this->ensurePositiveFinite($effectiveTensionArea, BeamCrackVerificationRejectionReason::INVALID_TENSION_REINFORCEMENT);
        $effectiveReinforcementRatio = $longitudinalReinforcement->providedArea / $effectiveTensionArea;
        $this->ensurePositiveFinite($effectiveReinforcementRatio, BeamCrackVerificationRejectionReason::INVALID_TENSION_REINFORCEMENT);

        $loadDuration = BeamCrackLoadDuration::LONG_TERM;
        $kt = $requirements->ktFor($loadDuration);
        $closeSpacingLimit = 5 * ($coverToLongitudinalBar + $barDiameter / 2);
        $spacingFormulaCriterion = $barSpacing <= $closeSpacingLimit
            ? BeamCrackSpacingFormulaCriterion::CLOSELY_SPACED_BARS
            : BeamCrackSpacingFormulaCriterion::WIDELY_SPACED_BARS;
        $maximumCrackSpacing = $spacingFormulaCriterion === BeamCrackSpacingFormulaCriterion::CLOSELY_SPACED_BARS
            ? $requirements->crackSpacingCoefficient3 * $coverToLongitudinalBar
                + $requirements->crackBondCoefficient * $requirements->crackStrainDistributionCoefficient
                * $requirements->crackSpacingCoefficient4 * $barDiameter / $effectiveReinforcementRatio
            : 1.3 * ($geometry->height - $x);
        $this->ensurePositiveFinite($maximumCrackSpacing, BeamCrackVerificationRejectionReason::INVALID_CRACK_WIDTH_REQUIREMENTS);

        $strainDifferenceMain = ($steelStress - $kt * $concrete->fctm / $effectiveReinforcementRatio * (1 + $alpha * $effectiveReinforcementRatio)) / $steel->es;
        $strainDifferenceMinimum = 0.6 * $steelStress / $steel->es;
        $strainDifference = max($strainDifferenceMain, $strainDifferenceMinimum);
        if (! is_finite($strainDifference) || $strainDifference < 0) {
            throw new BeamCrackVerificationException(BeamCrackVerificationRejectionReason::INVALID_STEEL_STRESS);
        }
        $strainDifferenceCriterion = $strainDifferenceMain >= $strainDifferenceMinimum
            ? BeamCrackStrainDifferenceCriterion::MAIN_STRAIN_EXPRESSION
            : BeamCrackStrainDifferenceCriterion::MINIMUM_STRAIN_DIFFERENCE;
        $crackWidth = $maximumCrackSpacing * $strainDifference;
        $utilization = $crackWidth / $crackWidthLimit;

        return new BeamCrackVerificationResult(
            'QUASI_PERMANENT', $loadDuration, $serviceStresses->sectionModel,
            $coverToLongitudinalBar, $barDiameter, $longitudinalReinforcement->barCount, $barSpacing, $clearBarSpacing,
            $effectiveHeightFromDepth, $effectiveHeightFromNeutralAxis, $effectiveHeightFromHalfDepth, $effectiveTensionHeight,
            $effectiveTensionArea, $effectiveReinforcementRatio, $concrete->fctm, $alpha, $steelStress, $kt,
            $requirements->crackBondCoefficient, $requirements->crackStrainDistributionCoefficient,
            $requirements->crackSpacingCoefficient3, $requirements->crackSpacingCoefficient4, $maximumCrackSpacing,
            $spacingFormulaCriterion, $strainDifferenceMain, $strainDifferenceMinimum, $strainDifference,
            $strainDifferenceCriterion, $crackWidth, $crackWidthLimit, $utilization,
            $crackWidth <= $crackWidthLimit ? BeamCrackVerificationStatus::COMPLIANT : BeamCrackVerificationStatus::NOT_COMPLIANT,
        );
    }

    private function ensureRequirements(BeamCrackWidthRequirements $requirements): void
    {
        foreach ([$requirements->crackBondCoefficient, $requirements->crackStrainDistributionCoefficient, $requirements->crackSpacingCoefficient3, $requirements->crackSpacingCoefficient4, $requirements->shortTermKt, $requirements->longTermKt] as $value) {
            $this->ensurePositiveFinite($value, BeamCrackVerificationRejectionReason::INVALID_CRACK_WIDTH_REQUIREMENTS);
        }
    }

    private function ensureNonNegativeFinite(float $value, BeamCrackVerificationRejectionReason $reason): void
    {
        if (! is_finite($value) || $value < 0) {
            throw new BeamCrackVerificationException($reason);
        }
    }

    private function ensurePositiveFinite(float $value, BeamCrackVerificationRejectionReason $reason): void
    {
        if (! is_finite($value) || $value <= 0) {
            throw new BeamCrackVerificationException($reason);
        }
    }
}
