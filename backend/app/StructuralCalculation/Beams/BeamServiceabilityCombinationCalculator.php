<?php

namespace App\StructuralCalculation\Beams;

use App\StructuralCalculation\Eurocode\Profiles\DesignCodeProfile;
use App\StructuralCalculation\Eurocode\Profiles\ServiceabilityCombinationExpression;

/** Applique les expressions ELS EN 1990 au cas MVP d'une action variable principale. */
final class BeamServiceabilityCombinationCalculator
{
    /** Facteur imposé par EN 1990 6.14 pour l'unique action variable principale. */
    private const LEADING_VARIABLE_FACTOR = 1.0;

    public function calculate(BeamCharacteristicActionsResult $actions, DesignCodeProfile $profile): BeamServiceabilityCombinationsResult
    {
        $permanentLoad = $actions->permanent->totalPermanentLoad;
        $variableLoad = $actions->variable->characteristicLoad;

        $this->ensureNonNegativeFinite($permanentLoad, BeamServiceabilityCombinationRejectionReason::INVALID_TOTAL_PERMANENT_LOAD);
        $this->ensureNonNegativeFinite($variableLoad, BeamServiceabilityCombinationRejectionReason::INVALID_VARIABLE_CHARACTERISTIC_LOAD);

        $factors = $profile->combinationFactorsFor($actions->variable->category);

        if ($factors === null) {
            throw new BeamServiceabilityCombinationException(BeamServiceabilityCombinationRejectionReason::MISSING_VARIABLE_ACTION_FACTORS);
        }

        $this->ensureNonNegativeFinite($factors->psi1, BeamServiceabilityCombinationRejectionReason::INVALID_FREQUENT_VARIABLE_FACTOR);
        $this->ensureNonNegativeFinite($factors->psi2, BeamServiceabilityCombinationRejectionReason::INVALID_QUASI_PERMANENT_VARIABLE_FACTOR);

        return new BeamServiceabilityCombinationsResult(
            characteristic: $this->combine(
                $permanentLoad,
                $variableLoad,
                self::LEADING_VARIABLE_FACTOR,
                ServiceabilityCombinationExpression::EN1990_6_14,
                'wSlsCharacteristic = Gk_total + Qk',
            ),
            frequent: $this->combine(
                $permanentLoad,
                $variableLoad,
                $factors->psi1,
                ServiceabilityCombinationExpression::EN1990_6_15,
                'wSlsFrequent = Gk_total + ψ1 × Qk',
            ),
            quasiPermanent: $this->combine(
                $permanentLoad,
                $variableLoad,
                $factors->psi2,
                ServiceabilityCombinationExpression::EN1990_6_16,
                'wSlsQuasiPermanent = Gk_total + ψ2 × Qk',
            ),
        );
    }

    private function combine(
        float $permanentLoad,
        float $variableLoad,
        float $variableFactor,
        ServiceabilityCombinationExpression $expressionReference,
        string $formula,
    ): BeamServiceabilityCombination {
        $variableContribution = $variableFactor * $variableLoad;

        return new BeamServiceabilityCombination(
            permanentCharacteristicLoad: $permanentLoad,
            variableCharacteristicLoad: $variableLoad,
            variableFactor: $variableFactor,
            permanentContribution: $permanentLoad,
            variableContribution: $variableContribution,
            resultingLineLoad: $permanentLoad + $variableContribution,
            expressionReference: $expressionReference,
            formula: $formula,
        );
    }

    private function ensureNonNegativeFinite(float $value, BeamServiceabilityCombinationRejectionReason $reason): void
    {
        if (! is_finite($value) || $value < 0) {
            throw new BeamServiceabilityCombinationException($reason);
        }
    }
}
