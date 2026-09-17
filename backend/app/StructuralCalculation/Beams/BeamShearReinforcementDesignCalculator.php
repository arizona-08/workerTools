<?php

namespace App\StructuralCalculation\Beams;

use App\StructuralCalculation\Eurocode\Beams\BeamConcreteShearResistanceRequirements;
use App\StructuralCalculation\Eurocode\Profiles\DesignCodeProfile;
use App\StructuralCalculation\Eurocode\ReinforcementSteel\ReinforcementSteelDesignStrengthCalculator;
use App\StructuralCalculation\Materials\Concrete\ConcreteProperties;
use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementSteelProperties;
use App\StructuralCalculation\Units\ForceConverter;

/** Dimensionne une densité théorique d'étriers verticaux, hors vérification VRd,max. */
final readonly class BeamShearReinforcementDesignCalculator
{
    public function __construct(
        private ReinforcementSteelDesignStrengthCalculator $steelDesignStrengthCalculator,
        private ForceConverter $forceConverter,
        private BeamShearReinforcementResistanceCalculator $reinforcementResistanceCalculator,
    ) {}

    public function calculate(
        BeamConcreteShearResistanceResult $concreteShear,
        BeamLeverArmResult $leverArm,
        ConcreteProperties $concrete,
        ReinforcementSteelProperties $stirrupSteel,
        DesignCodeProfile $profile,
        BeamShearDesignAssumptions $assumptions,
    ): BeamShearReinforcementDesignResult {
        $this->ensureNonNegativeFinite($concreteShear->designShearForce, BeamShearReinforcementDesignRejectionReason::INVALID_DESIGN_SHEAR_FORCE);
        $this->ensureNonNegativeFinite($concreteShear->concreteShearResistance, BeamShearReinforcementDesignRejectionReason::INVALID_CONCRETE_SHEAR_RESISTANCE);
        $this->ensurePositiveFinite($concreteShear->webWidth, BeamShearReinforcementDesignRejectionReason::INVALID_WEB_WIDTH);
        $this->ensurePositiveFinite($leverArm->leverArm, BeamShearReinforcementDesignRejectionReason::INVALID_LEVER_ARM);
        $this->ensurePositiveFinite($concrete->fck, BeamShearReinforcementDesignRejectionReason::INVALID_CONCRETE_STRENGTH);
        $this->ensurePositiveFinite($stirrupSteel->fyk, BeamShearReinforcementDesignRejectionReason::INVALID_STEEL_CHARACTERISTIC_STRENGTH);

        $requirements = $profile->beamConcreteShearResistanceRequirements;
        $this->ensureRequirements($requirements, $assumptions);
        $fywd = $this->steelDesignStrengthCalculator->calculate($stirrupSteel, $profile);
        $this->ensurePositiveFinite($fywd, BeamShearReinforcementDesignRejectionReason::INVALID_STIRRUP_STEEL_DESIGN_STRENGTH);

        $requiredByDemand = $concreteShear->status === BeamConcreteShearResistanceStatus::SHEAR_REINFORCEMENT_REQUIRED;
        $required = $requiredByDemand
            ? $this->forceConverter->kilonewtonsToNewtons($concreteShear->designShearForce) / ($leverArm->leverArm * $fywd * $assumptions->designCotTheta)
            : 0.0;
        $minimumRatio = $requirements->minimumShearReinforcementCoefficient * sqrt($concrete->fck) / $stirrupSteel->fyk;
        $minimum = $minimumRatio * $concreteShear->webWidth;
        $target = max($required, $minimum);
        $governing = match (true) {
            $required > $minimum => BeamShearReinforcementGoverningRequirement::SHEAR_DEMAND,
            $minimum > $required => BeamShearReinforcementGoverningRequirement::MINIMUM_TRANSVERSE_REINFORCEMENT,
            default => BeamShearReinforcementGoverningRequirement::EQUAL_REQUIREMENTS,
        };
        $targetResistance = $this->reinforcementResistanceCalculator->calculate($target, $leverArm->leverArm, $fywd, $assumptions->designCotTheta);

        return new BeamShearReinforcementDesignResult(
            $concreteShear->designShearForce, $concreteShear->concreteShearResistance, $requiredByDemand,
            $concreteShear->webWidth, $leverArm->leverArm, $stirrupSteel->fyk, $fywd,
            $assumptions->designCotTheta, $minimumRatio, $required, $minimum, $target, $governing, $targetResistance,
        );
    }

    private function ensureRequirements(BeamConcreteShearResistanceRequirements $requirements, BeamShearDesignAssumptions $assumptions): void
    {
        $this->ensurePositiveFinite($requirements->minimumCotTheta, BeamShearReinforcementDesignRejectionReason::INVALID_MINIMUM_COT_THETA);
        $this->ensurePositiveFinite($requirements->maximumCotTheta, BeamShearReinforcementDesignRejectionReason::INVALID_MAXIMUM_COT_THETA);
        if ($requirements->minimumCotTheta > $requirements->maximumCotTheta) {
            throw new BeamShearReinforcementDesignException(BeamShearReinforcementDesignRejectionReason::INVALID_MAXIMUM_COT_THETA);
        }
        $this->ensurePositiveFinite($requirements->minimumShearReinforcementCoefficient, BeamShearReinforcementDesignRejectionReason::INVALID_MINIMUM_SHEAR_REINFORCEMENT_COEFFICIENT);
        $this->ensurePositiveFinite($assumptions->designCotTheta, BeamShearReinforcementDesignRejectionReason::INVALID_DESIGN_COT_THETA);
        if ($assumptions->designCotTheta < $requirements->minimumCotTheta || $assumptions->designCotTheta > $requirements->maximumCotTheta) {
            throw new BeamShearReinforcementDesignException(BeamShearReinforcementDesignRejectionReason::DESIGN_COT_THETA_OUTSIDE_PROFILE_RANGE);
        }
    }

    private function ensureNonNegativeFinite(float $value, BeamShearReinforcementDesignRejectionReason $reason): void
    {
        if (! is_finite($value) || $value < 0) {
            throw new BeamShearReinforcementDesignException($reason);
        }
    }

    private function ensurePositiveFinite(float $value, BeamShearReinforcementDesignRejectionReason $reason): void
    {
        if (! is_finite($value) || $value <= 0) {
            throw new BeamShearReinforcementDesignException($reason);
        }
    }
}
