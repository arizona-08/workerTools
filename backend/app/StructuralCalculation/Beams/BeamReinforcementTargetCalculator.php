<?php

namespace App\StructuralCalculation\Beams;

/** Construit As_target sans choisir ni quantifier les barres longitudinales. */
final class BeamReinforcementTargetCalculator
{
    public function calculate(
        BeamRequiredTensionReinforcementResult $flexuralRequirement,
        BeamMinimumTensionReinforcementResult $minimumRequirement,
        BeamFlexuralDomainCheckResult $domainCheck,
    ): BeamRequiredReinforcementAreaResult {
        $this->ensureNonNegativeFinite($flexuralRequirement->requiredReinforcementArea, BeamReinforcementTargetRejectionReason::INVALID_FLEXURAL_REQUIRED_AREA);
        $this->ensureNonNegativeFinite($minimumRequirement->requiredMinimum, BeamReinforcementTargetRejectionReason::INVALID_MINIMUM_REQUIRED_AREA);
        if (! $domainCheck->singlyReinforcedModelValid) {
            throw new BeamReinforcementTargetException(BeamReinforcementTargetRejectionReason::INVALID_SINGLY_REINFORCED_DOMAIN);
        }

        $governingRequirement = match (true) {
            $flexuralRequirement->requiredReinforcementArea > $minimumRequirement->requiredMinimum => BeamReinforcementTargetGoverningRequirement::FLEXURAL_DEMAND,
            $flexuralRequirement->requiredReinforcementArea < $minimumRequirement->requiredMinimum => BeamReinforcementTargetGoverningRequirement::MINIMUM_REINFORCEMENT,
            default => BeamReinforcementTargetGoverningRequirement::EQUAL_REQUIREMENTS,
        };

        return new BeamRequiredReinforcementAreaResult(
            flexuralRequiredArea: $flexuralRequirement->requiredReinforcementArea,
            minimumRequiredArea: $minimumRequirement->requiredMinimum,
            targetArea: max($flexuralRequirement->requiredReinforcementArea, $minimumRequirement->requiredMinimum),
            governingRequirement: $governingRequirement,
        );
    }

    private function ensureNonNegativeFinite(float $value, BeamReinforcementTargetRejectionReason $reason): void
    {
        if (! is_finite($value) || $value < 0) {
            throw new BeamReinforcementTargetException($reason);
        }
    }
}
