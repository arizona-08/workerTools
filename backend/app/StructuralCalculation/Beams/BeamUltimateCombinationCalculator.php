<?php

namespace App\StructuralCalculation\Beams;

use App\StructuralCalculation\Eurocode\Profiles\DesignCodeProfile;

/** Applique EN 1990 6.10 au cas MVP d'une action variable principale. */
final class BeamUltimateCombinationCalculator
{
    public function calculate(BeamCharacteristicActionsResult $actions, DesignCodeProfile $profile): BeamUltimateCombinationResult
    {
        $permanentLoad = $actions->permanent->totalPermanentLoad;
        $variableLoad = $actions->variable->characteristicLoad;
        $permanentFactor = $profile->actionSafetyFactors->gammaGUnfavourable;
        $variableFactor = $profile->actionSafetyFactors->gammaQ;

        $this->ensureNonNegativeFinite($permanentLoad, BeamUltimateCombinationRejectionReason::INVALID_TOTAL_PERMANENT_LOAD);
        $this->ensureNonNegativeFinite($variableLoad, BeamUltimateCombinationRejectionReason::INVALID_VARIABLE_CHARACTERISTIC_LOAD);
        $this->ensurePositiveFinite($permanentFactor, BeamUltimateCombinationRejectionReason::INVALID_UNFAVOURABLE_PERMANENT_PARTIAL_FACTOR);
        $this->ensurePositiveFinite($variableFactor, BeamUltimateCombinationRejectionReason::INVALID_VARIABLE_PARTIAL_FACTOR);

        $permanentContribution = $permanentFactor * $permanentLoad;
        $variableContribution = $variableFactor * $variableLoad;

        return new BeamUltimateCombinationResult(
            permanentCharacteristicLoad: $permanentLoad,
            permanentPartialFactor: $permanentFactor,
            permanentDesignContribution: $permanentContribution,
            variableCharacteristicLoad: $variableLoad,
            variablePartialFactor: $variableFactor,
            variableDesignContribution: $variableContribution,
            designLineLoad: $permanentContribution + $variableContribution,
            expressionReference: $profile->fundamentalUltimateCombinationExpression,
        );
    }

    private function ensureNonNegativeFinite(float $value, BeamUltimateCombinationRejectionReason $reason): void
    {
        if (! is_finite($value) || $value < 0) {
            throw new BeamUltimateCombinationException($reason);
        }
    }

    private function ensurePositiveFinite(float $value, BeamUltimateCombinationRejectionReason $reason): void
    {
        if (! is_finite($value) || $value <= 0) {
            throw new BeamUltimateCombinationException($reason);
        }
    }
}
