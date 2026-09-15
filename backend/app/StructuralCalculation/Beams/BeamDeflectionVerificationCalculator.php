<?php

namespace App\StructuralCalculation\Beams;

use App\StructuralCalculation\Eurocode\Beams\BeamDeflectionRequirements;
use App\StructuralCalculation\Eurocode\Profiles\DesignCodeProfile;
use App\StructuralCalculation\Eurocode\Serviceability\SimplifiedSpanDepthCalculator;
use App\StructuralCalculation\Materials\Concrete\ConcreteProperties;
use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementSteelProperties;
use App\StructuralCalculation\MaterialType;

/** Applique EC2 §7.4.2 à un candidat longitudinal recalculé, sans flèche en mm. */
final class BeamDeflectionVerificationCalculator
{
    public function __construct(private SimplifiedSpanDepthCalculator $simplifiedSpanDepth) {}

    public function calculate(
        BeamCalculationConfiguration $configuration,
        BeamGeometry $geometry,
        BeamReinforcementCandidateRecalculationResult $candidate,
        ConcreteProperties $concrete,
        ReinforcementSteelProperties $steel,
        DesignCodeProfile $profile,
    ): BeamDeflectionVerificationResult {
        $this->ensureSupportedConfiguration($configuration, $candidate);
        $this->ensurePositiveFinite($geometry->effectiveSpan, BeamDeflectionVerificationRejectionReason::INVALID_EFFECTIVE_SPAN);
        $this->ensurePositiveFinite($geometry->width, BeamDeflectionVerificationRejectionReason::INVALID_TENSION_WIDTH);
        $this->ensurePositiveFinite($candidate->effectiveDepth->effectiveDepth, BeamDeflectionVerificationRejectionReason::INVALID_EFFECTIVE_DEPTH);
        if ($candidate->effectiveDepth->effectiveDepth >= $geometry->height) {
            throw new BeamDeflectionVerificationException(BeamDeflectionVerificationRejectionReason::INVALID_EFFECTIVE_DEPTH);
        }
        $this->ensurePositiveFinite($concrete->fck, BeamDeflectionVerificationRejectionReason::INVALID_CONCRETE_STRENGTH);
        $this->ensurePositiveFinite($steel->fyk, BeamDeflectionVerificationRejectionReason::INVALID_STEEL_STRENGTH);
        $requiredArea = $candidate->requiredArea->requiredReinforcementArea;
        $providedArea = $candidate->providedArea;
        $this->ensurePositiveFinite($requiredArea, BeamDeflectionVerificationRejectionReason::INVALID_REQUIRED_REINFORCEMENT);
        $this->ensurePositiveFinite($providedArea, BeamDeflectionVerificationRejectionReason::INVALID_PROVIDED_REINFORCEMENT);
        if ($providedArea < $requiredArea) {
            throw new BeamDeflectionVerificationException(BeamDeflectionVerificationRejectionReason::INSUFFICIENT_LONGITUDINAL_REINFORCEMENT);
        }

        $requirements = $profile->beamDeflectionRequirements;
        $this->ensureRequirements($requirements);
        $structuralFactor = $requirements->structuralFactorFor($configuration->supportSystem);
        if ($structuralFactor === null) {
            throw new BeamDeflectionVerificationException(BeamDeflectionVerificationRejectionReason::CALCULATION_METHOD_NOT_SUPPORTED);
        }
        $this->ensurePositiveFinite($structuralFactor, BeamDeflectionVerificationRejectionReason::INVALID_DEFLECTION_REQUIREMENTS);

        $effectiveDepth = $candidate->effectiveDepth->effectiveDepth;
        $compressionRatio = 0.0;
        try {
            $spanDepth = $this->simplifiedSpanDepth->calculate($geometry->effectiveSpan, $geometry->width, $effectiveDepth,
                $concrete->fck, $steel->fyk, $requiredArea, $providedArea, $structuralFactor, $requirements);
        } catch (\InvalidArgumentException) {
            throw new BeamDeflectionVerificationException(BeamDeflectionVerificationRejectionReason::INVALID_DEFLECTION_REQUIREMENTS);
        }

        return new BeamDeflectionVerificationResult(
            BeamDeflectionMethod::SIMPLIFIED_SPAN_DEPTH,
            BeamDeflectionVerificationStatus::COMPLIANT,
            $geometry->effectiveSpan,
            $effectiveDepth,
            $spanDepth->actualSpanDepthRatio,
            $concrete->fck,
            $requiredArea,
            $providedArea,
            $spanDepth->reinforcementRatio,
            $spanDepth->referenceReinforcementRatio,
            $compressionRatio,
            $configuration->supportSystem,
            $structuralFactor,
            BeamDeflectionFormulaBranch::from($spanDepth->formulaBranch),
            $spanDepth->baseAllowableSpanDepthRatio,
            $spanDepth->steelStressCorrectionFactor,
            $spanDepth->allowableSpanDepthRatio,
            $spanDepth->utilization,
            $spanDepth->actualSpanDepthRatio <= $spanDepth->allowableSpanDepthRatio ? BeamDeflectionVerificationStatus::COMPLIANT : BeamDeflectionVerificationStatus::NOT_COMPLIANT,
            ['SIMPLIFIED_METHOD_ONLY', 'NO_EXPLICIT_DEFLECTION_CALCULATED', 'LONG_TERM_EFFECTS_NOT_EXPLICITLY_MODELLED', 'PARTITION_DAMAGE_CHECK_NOT_MODELLED'],
        );
    }

    private function ensureSupportedConfiguration(BeamCalculationConfiguration $configuration, BeamReinforcementCandidateRecalculationResult $candidate): void
    {
        if ($configuration->materialType !== MaterialType::REINFORCED_CONCRETE
            || $configuration->sectionType !== BeamSectionType::RECTANGULAR
            || $configuration->supportSystem !== BeamSupportSystem::SIMPLY_SUPPORTED
            || $candidate->status === BeamReinforcementCandidateRecalculationStatus::INVALID_SINGLY_REINFORCED_DOMAIN) {
            throw new BeamDeflectionVerificationException(BeamDeflectionVerificationRejectionReason::CALCULATION_METHOD_NOT_SUPPORTED);
        }
    }

    private function ensureRequirements(BeamDeflectionRequirements $requirements): void
    {
        foreach ([$requirements->baseRatioConstant, $requirements->lowReinforcementCoefficient, $requirements->lowReinforcementAdditionalCoefficient, $requirements->highReinforcementCompressionCoefficient, $requirements->referenceReinforcementRatioFactor, $requirements->referenceSteelStrength] as $value) {
            $this->ensurePositiveFinite($value, BeamDeflectionVerificationRejectionReason::INVALID_DEFLECTION_REQUIREMENTS);
        }
    }

    private function ensurePositiveFinite(float $value, BeamDeflectionVerificationRejectionReason $reason): void
    {
        if (! is_finite($value) || $value <= 0) {
            throw new BeamDeflectionVerificationException($reason);
        }
    }
}
