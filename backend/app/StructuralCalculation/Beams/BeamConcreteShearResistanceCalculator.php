<?php

namespace App\StructuralCalculation\Beams;

use App\StructuralCalculation\Eurocode\Beams\BeamConcreteShearResistanceRequirements;
use App\StructuralCalculation\Eurocode\Profiles\DesignCodeProfile;
use App\StructuralCalculation\Materials\Concrete\ConcreteProperties;
use App\StructuralCalculation\Units\ForceConverter;

/** Calcule VRd,c d'une poutre rectangulaire MVP, sans dimensionner les étriers. */
final readonly class BeamConcreteShearResistanceCalculator
{
    public function __construct(private ForceConverter $forceConverter) {}

    public function calculate(
        BeamShearForce $designShearForce,
        BeamCalculationConfiguration $configuration,
        BeamGeometry $geometry,
        BeamEffectiveDepthResult $effectiveDepth,
        ConcreteProperties $concrete,
        BeamFlexuralConcreteDesignStrength $concreteDesignStrength,
        float $longitudinalReinforcementArea,
        DesignCodeProfile $profile,
        float $normalForce = 0.0,
    ): BeamConcreteShearResistanceResult {
        $this->ensureNonNegativeFinite($designShearForce->maximumAbsoluteShear, BeamConcreteShearResistanceRejectionReason::INVALID_DESIGN_SHEAR_FORCE);
        if ($configuration->sectionType !== BeamSectionType::RECTANGULAR) {
            throw new BeamConcreteShearResistanceException(BeamConcreteShearResistanceRejectionReason::UNSUPPORTED_SECTION_TYPE);
        }
        $this->ensurePositiveFinite($geometry->width, BeamConcreteShearResistanceRejectionReason::INVALID_WEB_WIDTH);
        $this->ensurePositiveFinite($geometry->height, BeamConcreteShearResistanceRejectionReason::INVALID_CONCRETE_AREA);
        $this->ensurePositiveFinite($effectiveDepth->effectiveDepth, BeamConcreteShearResistanceRejectionReason::INVALID_EFFECTIVE_DEPTH);
        $this->ensurePositiveFinite($concrete->fck, BeamConcreteShearResistanceRejectionReason::INVALID_CONCRETE_STRENGTH);
        $this->ensurePositiveFinite($concreteDesignStrength->fcd, BeamConcreteShearResistanceRejectionReason::INVALID_CONCRETE_DESIGN_STRENGTH);
        $this->ensureNonNegativeFinite($longitudinalReinforcementArea, BeamConcreteShearResistanceRejectionReason::INVALID_LONGITUDINAL_REINFORCEMENT_AREA);
        if ($normalForce !== 0.0) {
            throw new BeamConcreteShearResistanceException(BeamConcreteShearResistanceRejectionReason::UNSUPPORTED_NORMAL_FORCE);
        }

        $requirements = $profile->beamConcreteShearResistanceRequirements;
        $this->ensureRequirements($requirements);

        $webWidth = $geometry->width;
        $depth = $effectiveDepth->effectiveDepth;
        $concreteArea = $geometry->width * $geometry->height;
        $ratioRaw = $longitudinalReinforcementArea / ($webWidth * $depth);
        $ratio = min($ratioRaw, $requirements->maximumLongitudinalReinforcementRatio);
        $sizeEffectRaw = 1 + sqrt($requirements->sizeEffectReferenceDepth / $depth);
        $sizeEffect = min($sizeEffectRaw, $requirements->maximumSizeEffectFactor);
        $meanCompressiveStress = $this->forceConverter->kilonewtonsToNewtons($normalForce) / $concreteArea;
        $mainStress = $requirements->concreteShearResistanceCoefficient * $sizeEffect * (100 * $ratio * $concrete->fck) ** (1 / 3)
            + $requirements->compressionStressCoefficient * $meanCompressiveStress;
        $minimumStress = $requirements->minimumShearStressCoefficient * $sizeEffect ** (3 / 2) * sqrt($concrete->fck)
            + $requirements->compressionStressCoefficient * $meanCompressiveStress;
        $governingStress = max($mainStress, $minimumStress);
        $criterion = match (true) {
            $mainStress > $minimumStress => BeamConcreteShearResistanceGoverningCriterion::MAIN_EXPRESSION,
            $minimumStress > $mainStress => BeamConcreteShearResistanceGoverningCriterion::MINIMUM_SHEAR_RESISTANCE,
            default => BeamConcreteShearResistanceGoverningCriterion::EQUAL_RESISTANCES,
        };
        $resistance = $this->forceConverter->newtonsToKilonewtons($governingStress * $webWidth * $depth);
        $utilization = $designShearForce->maximumAbsoluteShear / $resistance;

        return new BeamConcreteShearResistanceResult(
            $designShearForce->maximumAbsoluteShear, $webWidth, $depth, $longitudinalReinforcementArea,
            $ratioRaw, $ratio, $ratioRaw > $ratio, $sizeEffectRaw, $sizeEffect, $sizeEffectRaw > $sizeEffect,
            $normalForce, $concreteArea, $meanCompressiveStress, $concrete->fck, $concreteDesignStrength->fcd,
            $requirements->concreteShearResistanceCoefficient, $requirements->compressionStressCoefficient,
            $requirements->minimumShearStressCoefficient * $sizeEffect ** (3 / 2) * sqrt($concrete->fck),
            $mainStress, $minimumStress, $governingStress, $criterion, $resistance, $utilization,
            $designShearForce->maximumAbsoluteShear <= $resistance
                ? BeamConcreteShearResistanceStatus::SHEAR_REINFORCEMENT_NOT_REQUIRED_BY_VRDC_CHECK
                : BeamConcreteShearResistanceStatus::SHEAR_REINFORCEMENT_REQUIRED,
        );
    }

    private function ensureRequirements(BeamConcreteShearResistanceRequirements $requirements): void
    {
        $this->ensurePositiveFinite($requirements->concreteShearResistanceCoefficient, BeamConcreteShearResistanceRejectionReason::INVALID_CONCRETE_SHEAR_RESISTANCE_COEFFICIENT);
        $this->ensureNonNegativeFinite($requirements->compressionStressCoefficient, BeamConcreteShearResistanceRejectionReason::INVALID_COMPRESSION_STRESS_COEFFICIENT);
        $this->ensureNonNegativeFinite($requirements->minimumShearStressCoefficient, BeamConcreteShearResistanceRejectionReason::INVALID_MINIMUM_SHEAR_STRESS_COEFFICIENT);
        $this->ensurePositiveFinite($requirements->sizeEffectReferenceDepth, BeamConcreteShearResistanceRejectionReason::INVALID_SIZE_EFFECT_REFERENCE_DEPTH);
        $this->ensurePositiveFinite($requirements->maximumSizeEffectFactor, BeamConcreteShearResistanceRejectionReason::INVALID_MAXIMUM_SIZE_EFFECT_FACTOR);
        $this->ensurePositiveFinite($requirements->maximumLongitudinalReinforcementRatio, BeamConcreteShearResistanceRejectionReason::INVALID_MAXIMUM_LONGITUDINAL_REINFORCEMENT_RATIO);
    }

    private function ensureNonNegativeFinite(float $value, BeamConcreteShearResistanceRejectionReason $reason): void
    {
        if (! is_finite($value) || $value < 0) {
            throw new BeamConcreteShearResistanceException($reason);
        }
    }

    private function ensurePositiveFinite(float $value, BeamConcreteShearResistanceRejectionReason $reason): void
    {
        if (! is_finite($value) || $value <= 0) {
            throw new BeamConcreteShearResistanceException($reason);
        }
    }
}
