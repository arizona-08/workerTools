<?php

namespace App\StructuralCalculation\Beams;

use App\StructuralCalculation\Units\MomentConverter;

/** Détermine As_req par l'équilibre MEd = As_req × fyd × z. */
final readonly class BeamRequiredTensionReinforcementCalculator
{
    public function __construct(private MomentConverter $momentConverter) {}

    public function calculate(
        BeamBendingMoment $ultimateMoment,
        BeamFlexuralSteelDesignStrength $steelDesignStrength,
        BeamLeverArmResult $leverArm,
    ): BeamRequiredTensionReinforcementResult {
        $designMomentMagnitude = $ultimateMoment->magnitude();
        $this->ensureNonNegativeFinite($designMomentMagnitude, BeamRequiredTensionReinforcementRejectionReason::INVALID_DESIGN_MOMENT);
        $this->ensurePositiveFinite($steelDesignStrength->fyd, BeamRequiredTensionReinforcementRejectionReason::INVALID_STEEL_DESIGN_STRENGTH);
        $this->ensurePositiveFinite($leverArm->leverArm, BeamRequiredTensionReinforcementRejectionReason::INVALID_LEVER_ARM);

        $designMomentInNewtonMillimetres = $this->momentConverter->kilonewtonMetresToNewtonMillimetres($designMomentMagnitude);
        $steelLeverArmProduct = $steelDesignStrength->fyd * $leverArm->leverArm;
        $this->ensurePositiveFinite($steelLeverArmProduct, BeamRequiredTensionReinforcementRejectionReason::INVALID_STEEL_LEVER_ARM_PRODUCT);

        $requiredReinforcementArea = $designMomentInNewtonMillimetres / $steelLeverArmProduct;
        $this->ensureNonNegativeFinite($requiredReinforcementArea, BeamRequiredTensionReinforcementRejectionReason::INVALID_REQUIRED_REINFORCEMENT_AREA);

        return new BeamRequiredTensionReinforcementResult(
            designMoment: $designMomentMagnitude,
            designMomentInNewtonMillimetres: $designMomentInNewtonMillimetres,
            steelDesignStrength: $steelDesignStrength->fyd,
            leverArm: $leverArm->leverArm,
            steelLeverArmProduct: $steelLeverArmProduct,
            requiredReinforcementArea: $requiredReinforcementArea,
            signedDesignMoment: $ultimateMoment->maximumMoment,
        );
    }

    private function ensureNonNegativeFinite(float $value, BeamRequiredTensionReinforcementRejectionReason $reason): void
    {
        if (! is_finite($value) || $value < 0) {
            throw new BeamRequiredTensionReinforcementException($reason);
        }
    }

    private function ensurePositiveFinite(float $value, BeamRequiredTensionReinforcementRejectionReason $reason): void
    {
        if (! is_finite($value) || $value <= 0) {
            throw new BeamRequiredTensionReinforcementException($reason);
        }
    }
}
