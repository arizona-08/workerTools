<?php

namespace App\StructuralCalculation\Beams;

use App\StructuralCalculation\Eurocode\Beams\BeamCrackWidthRequirements;
use App\StructuralCalculation\Eurocode\Profiles\DesignCodeProfile;
use App\StructuralCalculation\Eurocode\Serviceability\DirectCrackWidthCalculator;
use App\StructuralCalculation\Materials\Concrete\ConcreteProperties;
use App\StructuralCalculation\Materials\Exposure\ExposureClassCode;
use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementSteelProperties;

/** Vérifie wk à la combinaison quasi-permanente, selon EC2 §7.3.4. */
final class BeamCrackVerificationCalculator
{
    public function __construct(private DirectCrackWidthCalculator $directCrackWidth) {}

    public function calculate(
        BeamGeometry $geometry,
        BeamEffectiveDepthResult $effectiveDepth,
        BeamReinforcementProposalCandidate $longitudinalReinforcement,
        ?BeamStirrupProposalResult $stirrupProposals,
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

        // Le diamètre transversal sert seulement à localiser le lit longitudinal.
        // En son absence, la géométrie déjà utilisée pour d reste la source de vérité.
        $transverseBarDiameter = $stirrupProposals?->recommendedCandidate?->barDiameter ?? $effectiveDepth->transverseBarDiameter;
        $this->ensurePositiveFinite($transverseBarDiameter, BeamCrackVerificationRejectionReason::INVALID_TRANSVERSE_BAR_DIAMETER);

        $requirements = $profile->beamCrackWidthRequirements;
        $this->ensureRequirements($requirements);
        $crackWidthLimit = $requirements->crackWidthLimitFor($exposureClass);
        if ($crackWidthLimit === null) {
            throw new BeamCrackVerificationException(BeamCrackVerificationRejectionReason::UNSUPPORTED_CRACK_WIDTH_EXPOSURE_CLASS);
        }
        $this->ensurePositiveFinite($crackWidthLimit, BeamCrackVerificationRejectionReason::INVALID_CRACK_WIDTH_LIMIT);

        $coverToLongitudinalBar = $effectiveDepth->nominalCover + $transverseBarDiameter;
        $barDiameter = $longitudinalReinforcement->barDiameter;
        $barSpacing = ($geometry->width - 2 * ($coverToLongitudinalBar + $barDiameter / 2)) / ($longitudinalReinforcement->barCount - 1);
        $this->ensurePositiveFinite($barSpacing, BeamCrackVerificationRejectionReason::INVALID_BAR_LAYOUT);
        $clearBarSpacing = $barSpacing - $barDiameter;

        $loadDuration = BeamCrackLoadDuration::LONG_TERM;
        $kt = $requirements->ktFor($loadDuration);
        try {
            $direct = $this->directCrackWidth->calculate($geometry->width, $geometry->height, $effectiveDepth->effectiveDepth,
                $longitudinalReinforcement->providedArea, $barDiameter, $barSpacing, $coverToLongitudinalBar,
                $x, $alpha, $steelStress, $steel->es, $concrete->fctm, $requirements);
        } catch (\InvalidArgumentException) {
            throw new BeamCrackVerificationException(BeamCrackVerificationRejectionReason::INVALID_STEEL_STRESS);
        }
        $crackWidth = $direct->crackWidth;
        $utilization = $crackWidth / $crackWidthLimit;

        return new BeamCrackVerificationResult(
            'QUASI_PERMANENT', $loadDuration, $serviceStresses->sectionModel, $serviceStresses->steelQuasiPermanent->moment,
            $serviceStresses->tensionFace, $longitudinalReinforcement->position, $longitudinalReinforcement->providedArea, $transverseBarDiameter,
            $coverToLongitudinalBar, $barDiameter, $longitudinalReinforcement->barCount, $barSpacing, $clearBarSpacing,
            $direct->effectiveTensionHeightFromDepth, $direct->effectiveTensionHeightFromNeutralAxis, $direct->effectiveTensionHeightFromHalfDepth, $direct->effectiveTensionHeight,
            $direct->effectiveTensionArea, $direct->effectiveReinforcementRatio, $concrete->fctm, $alpha, $steelStress, $kt,
            $requirements->crackBondCoefficient, $requirements->crackStrainDistributionCoefficient,
            $requirements->crackSpacingCoefficient3, $requirements->crackSpacingCoefficient4, $direct->maximumCrackSpacing,
            BeamCrackSpacingFormulaCriterion::from($direct->spacingFormulaCriterion), $direct->strainDifferenceMain, $direct->strainDifferenceMinimum, $direct->strainDifference,
            BeamCrackStrainDifferenceCriterion::from($direct->strainDifferenceCriterion), $crackWidth, $crackWidthLimit, $utilization,
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
