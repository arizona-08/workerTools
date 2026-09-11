<?php

namespace App\StructuralCalculation\Beams;

/** Construit Gk et Qk sans appliquer de coefficient ou de combinaison. */
final class CharacteristicActionsCalculator
{
    public function calculate(
        SelfWeightResult $selfWeight,
        BeamPermanentLoads $permanentLoads,
        BeamVariableLoad $variableLoad,
    ): BeamCharacteristicActionsResult {
        $this->ensureNonNegativeFinite($selfWeight->characteristicLineLoad, CharacteristicActionsCalculationRejectionReason::INVALID_SELF_WEIGHT);
        $this->ensureNonNegativeFinite($permanentLoads->additionalPermanentLoad, CharacteristicActionsCalculationRejectionReason::INVALID_ADDITIONAL_PERMANENT_LOAD);
        $this->ensureNonNegativeFinite($variableLoad->characteristicLoad, CharacteristicActionsCalculationRejectionReason::INVALID_CHARACTERISTIC_VARIABLE_LOAD);

        return new BeamCharacteristicActionsResult(
            permanent: new CharacteristicPermanentActions(
                selfWeight: $selfWeight,
                additionalPermanentLoad: $permanentLoads->additionalPermanentLoad,
                totalPermanentLoad: $selfWeight->characteristicLineLoad + $permanentLoads->additionalPermanentLoad,
            ),
            variable: new CharacteristicVariableAction(
                category: $variableLoad->category,
                characteristicLoad: $variableLoad->characteristicLoad,
            ),
        );
    }

    private function ensureNonNegativeFinite(float $value, CharacteristicActionsCalculationRejectionReason $reason): void
    {
        if (! is_finite($value) || $value < 0) {
            throw new CharacteristicActionsCalculationException($reason);
        }
    }
}
