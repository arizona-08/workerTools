<?php

namespace App\StructuralCalculation\Beams;

use App\StructuralCalculation\Eurocode\Beams\BeamConcreteShearResistanceRequirements;
use App\StructuralCalculation\Eurocode\Profiles\DesignCodeProfile;
use App\StructuralCalculation\Materials\Concrete\ConcreteProperties;
use App\StructuralCalculation\Units\ForceConverter;

/** Vérifie VRd,max du modèle de treillis EC2, sans adapter les étriers ou cotθ. */
final readonly class BeamMaximumShearResistanceCalculator
{
    public function __construct(private ForceConverter $forceConverter) {}

    public function calculate(
        BeamShearReinforcementDesignResult $reinforcementDesign,
        ConcreteProperties $concrete,
        BeamFlexuralConcreteDesignStrength $concreteDesignStrength,
        DesignCodeProfile $profile,
        ?float $designShearForce = null,
    ): BeamMaximumShearResistanceResult {
        $designShearForce ??= $reinforcementDesign->designShearForce;
        $this->ensureNonNegativeFinite($designShearForce, BeamMaximumShearResistanceRejectionReason::INVALID_DESIGN_SHEAR_FORCE);
        $this->ensurePositiveFinite($reinforcementDesign->webWidth, BeamMaximumShearResistanceRejectionReason::INVALID_WEB_WIDTH);
        $this->ensurePositiveFinite($reinforcementDesign->leverArm, BeamMaximumShearResistanceRejectionReason::INVALID_LEVER_ARM);
        $this->ensurePositiveFinite($concrete->fck, BeamMaximumShearResistanceRejectionReason::INVALID_CONCRETE_CHARACTERISTIC_STRENGTH);
        $this->ensurePositiveFinite($concreteDesignStrength->fcd, BeamMaximumShearResistanceRejectionReason::INVALID_CONCRETE_DESIGN_STRENGTH);

        $requirements = $profile->beamConcreteShearResistanceRequirements;
        $this->ensureRequirements($requirements, $reinforcementDesign->cotTheta);
        $tanTheta = 1 / $reinforcementDesign->cotTheta;
        $reductionFactor = $requirements->concreteShearStrengthReductionCoefficient * (1 - $concrete->fck / $requirements->concreteShearStrengthReductionReferenceStrength);
        $this->ensurePositiveFinite($reductionFactor, BeamMaximumShearResistanceRejectionReason::INVALID_REDUCTION_COEFFICIENT);
        $denominator = $reinforcementDesign->cotTheta + $tanTheta;
        $maximumResistance = $this->forceConverter->newtonsToKilonewtons(
            $requirements->nonPrestressedAlphaCw * $reinforcementDesign->webWidth * $reinforcementDesign->leverArm * $reductionFactor * $concreteDesignStrength->fcd / $denominator,
        );
        $this->ensurePositiveFinite($maximumResistance, BeamMaximumShearResistanceRejectionReason::INVALID_MAXIMUM_SHEAR_RESISTANCE);

        return new BeamMaximumShearResistanceResult(
            $designShearForce, $reinforcementDesign->webWidth, $reinforcementDesign->leverArm,
            $concrete->fck, $concreteDesignStrength->fcd, $requirements->nonPrestressedAlphaCw, $reductionFactor,
            $reinforcementDesign->cotTheta, $tanTheta, $maximumResistance,
            $designShearForce / $maximumResistance,
            $designShearForce <= $maximumResistance
                ? BeamMaximumShearResistanceStatus::MAXIMUM_SHEAR_RESISTANCE_OK
                : BeamMaximumShearResistanceStatus::MAXIMUM_SHEAR_RESISTANCE_EXCEEDED,
        );
    }

    private function ensureRequirements(BeamConcreteShearResistanceRequirements $requirements, float $cotTheta): void
    {
        $this->ensurePositiveFinite($requirements->minimumCotTheta, BeamMaximumShearResistanceRejectionReason::INVALID_COT_THETA);
        $this->ensurePositiveFinite($requirements->maximumCotTheta, BeamMaximumShearResistanceRejectionReason::INVALID_COT_THETA);
        if ($requirements->minimumCotTheta > $requirements->maximumCotTheta || $cotTheta < $requirements->minimumCotTheta || $cotTheta > $requirements->maximumCotTheta) {
            throw new BeamMaximumShearResistanceException(BeamMaximumShearResistanceRejectionReason::COT_THETA_OUTSIDE_PROFILE_RANGE);
        }
        $this->ensurePositiveFinite($requirements->concreteShearStrengthReductionCoefficient, BeamMaximumShearResistanceRejectionReason::INVALID_REDUCTION_COEFFICIENT);
        $this->ensurePositiveFinite($requirements->concreteShearStrengthReductionReferenceStrength, BeamMaximumShearResistanceRejectionReason::INVALID_REDUCTION_REFERENCE_STRENGTH);
        $this->ensurePositiveFinite($requirements->nonPrestressedAlphaCw, BeamMaximumShearResistanceRejectionReason::INVALID_ALPHA_CW);
        $this->ensurePositiveFinite($cotTheta, BeamMaximumShearResistanceRejectionReason::INVALID_COT_THETA);
    }

    private function ensureNonNegativeFinite(float $value, BeamMaximumShearResistanceRejectionReason $reason): void
    {
        if (! is_finite($value) || $value < 0) {
            throw new BeamMaximumShearResistanceException($reason);
        }
    }

    private function ensurePositiveFinite(float $value, BeamMaximumShearResistanceRejectionReason $reason): void
    {
        if (! is_finite($value) || $value <= 0) {
            throw new BeamMaximumShearResistanceException($reason);
        }
    }
}
