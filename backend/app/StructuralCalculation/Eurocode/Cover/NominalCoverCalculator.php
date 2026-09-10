<?php

namespace App\StructuralCalculation\Eurocode\Cover;

use App\StructuralCalculation\Eurocode\Profiles\DesignCodeProfile;

final class NominalCoverCalculator
{
    public function __construct(
        private readonly StructuralClassCalculator $structuralClassCalculator,
        private readonly DurabilityMinimumCoverCalculator $durabilityMinimumCoverCalculator,
        private readonly BondMinimumCoverCalculator $bondMinimumCoverCalculator,
    ) {}

    public function calculate(CoverCalculationInput $input, DesignCodeProfile $profile): CoverCalculationResult
    {
        if ($input->coverMode === CoverMode::MANUAL) {
            return $this->manualResult($input);
        }

        $this->validateAutomaticInput($input, $profile);
        $requirements = $profile->coverRequirements;
        $exposureResults = array_map(function ($exposureClass) use ($input, $profile): DurabilityExposureResult {
            $structuralClass = $this->structuralClassCalculator->calculate(
                $profile,
                $exposureClass,
                $input->concreteClass,
                $input->designWorkingLifeYears,
                $input->compactCover,
            );

            return $this->durabilityMinimumCoverCalculator->calculate($profile, $exposureClass, $structuralClass);
        }, $input->exposureClasses);

        usort($exposureResults, fn (DurabilityExposureResult $left, DurabilityExposureResult $right): int => $right->minimumDurabilityCover <=> $left->minimumDurabilityCover);
        $governingExposure = $exposureResults[0];
        $cMinBond = $this->bondMinimumCoverCalculator->calculate($input->reinforcementDiameter);
        $correctedDurability = $governingExposure->minimumDurabilityCover
            + $requirements->deltaCDurGamma
            - $requirements->deltaCDurSt
            - $requirements->deltaCDurAdd;
        [$cMin, $criterion] = $this->minimumAndGoverningCriterion(
            $cMinBond,
            $correctedDurability,
            $requirements->minimumAbsoluteCover,
        );

        return new CoverCalculationResult(
            coverMode: CoverMode::AUTO,
            initialStructuralClass: $governingExposure->structuralClass->initialStructuralClass,
            structuralClassModifiers: $governingExposure->structuralClass->modifiers,
            finalStructuralClass: $governingExposure->structuralClass->finalStructuralClass,
            exposureResults: $exposureResults,
            governingExposureClass: $governingExposure->exposureClass,
            cMinBond: $cMinBond,
            cMinDurability: $governingExposure->minimumDurabilityCover,
            deltaCDurGamma: $requirements->deltaCDurGamma,
            deltaCDurSt: $requirements->deltaCDurSt,
            deltaCDurAdd: $requirements->deltaCDurAdd,
            correctedCMinDurability: $correctedDurability,
            minimumAbsoluteCover: $requirements->minimumAbsoluteCover,
            cMin: $cMin,
            governingCriterion: $criterion,
            deltaCDev: $requirements->defaultDeltaCDev,
            cNom: $cMin + $requirements->defaultDeltaCDev,
            unit: 'mm',
            warnings: [],
        );
    }

    private function manualResult(CoverCalculationInput $input): CoverCalculationResult
    {
        if ($input->manualNominalCover === null || $input->manualNominalCover <= 0) {
            throw new CoverCalculationException(CoverCalculationRejectionReason::INVALID_MANUAL_NOMINAL_COVER);
        }

        return new CoverCalculationResult(
            coverMode: CoverMode::MANUAL,
            initialStructuralClass: null,
            structuralClassModifiers: [],
            finalStructuralClass: null,
            exposureResults: [],
            governingExposureClass: null,
            cMinBond: null,
            cMinDurability: null,
            deltaCDurGamma: null,
            deltaCDurSt: null,
            deltaCDurAdd: null,
            correctedCMinDurability: null,
            minimumAbsoluteCover: null,
            cMin: $input->manualNominalCover,
            governingCriterion: null,
            deltaCDev: null,
            cNom: $input->manualNominalCover,
            unit: 'mm',
            warnings: ['L’enrobage nominal est imposé manuellement et sa conformité normative n’est pas vérifiée par WorkerTools.'],
        );
    }

    private function validateAutomaticInput(CoverCalculationInput $input, DesignCodeProfile $profile): void
    {
        if (! $input->scope->isSupported()) {
            throw new CoverCalculationException(CoverCalculationRejectionReason::UNSUPPORTED_CONFIGURATION);
        }
        if ($input->exposureClasses === []) {
            throw new CoverCalculationException(CoverCalculationRejectionReason::NO_EXPOSURE_CLASS);
        }
        if ($input->reinforcementDiameter === null || $input->reinforcementDiameter <= 0) {
            throw new CoverCalculationException(CoverCalculationRejectionReason::INVALID_REINFORCEMENT_DIAMETER);
        }
        if ($input->concreteClass === null) {
            throw new CoverCalculationException(CoverCalculationRejectionReason::STRUCTURAL_CLASS_RULE_NOT_SUPPORTED);
        }
        if ($input->designWorkingLifeYears === null || $input->designWorkingLifeYears <= 0) {
            throw new CoverCalculationException(CoverCalculationRejectionReason::INVALID_DESIGN_WORKING_LIFE);
        }
        if (! $profile->coverRequirements->supportsDesignWorkingLife($input->designWorkingLifeYears)) {
            throw new CoverCalculationException(CoverCalculationRejectionReason::STRUCTURAL_CLASS_RULE_NOT_SUPPORTED);
        }
    }

    /** @return array{float, CoverGoverningCriterion} */
    private function minimumAndGoverningCriterion(float $bond, float $durability, float $absoluteMinimum): array
    {
        if ($bond >= $durability && $bond >= $absoluteMinimum) {
            return [$bond, CoverGoverningCriterion::BOND];
        }
        if ($durability > $absoluteMinimum) {
            return [$durability, CoverGoverningCriterion::DURABILITY];
        }

        return [$absoluteMinimum, CoverGoverningCriterion::MINIMUM_10_MM];
    }
}
