<?php

namespace App\StructuralCalculation\Eurocode\Combinations;

use App\StructuralCalculation\Eurocode\Profiles\DesignCodeProfile;
use App\StructuralCalculation\Eurocode\Profiles\FundamentalUltimateCombinationExpression;
use App\StructuralCalculation\Eurocode\Profiles\ServiceabilityCombinationExpression;

/** Moteur commun EN 1990 : Gk/Qk vers combinaisons, sans géométrie ni unité imposée. */
final class ActionCombinationCalculator
{
    /** Facteur de l'unique action variable principale, EN 1990 6.14. */
    private const LEADING_VARIABLE_FACTOR = 1.0;

    public function calculateUltimate(CharacteristicActions $actions, DesignCodeProfile $profile): CombinedAction
    {
        $this->ensureNonNegativeFinite($actions->permanentTotal, ActionCombinationRejectionReason::INVALID_TOTAL_PERMANENT_LOAD);
        $this->ensureNonNegativeFinite($actions->variableCharacteristic, ActionCombinationRejectionReason::INVALID_VARIABLE_CHARACTERISTIC_LOAD);
        $this->ensurePositiveFinite($profile->actionSafetyFactors->gammaGUnfavourable, ActionCombinationRejectionReason::INVALID_UNFAVOURABLE_PERMANENT_PARTIAL_FACTOR);
        $this->ensurePositiveFinite($profile->actionSafetyFactors->gammaQ, ActionCombinationRejectionReason::INVALID_VARIABLE_PARTIAL_FACTOR);

        return $this->combine(
            $actions,
            $profile->actionSafetyFactors->gammaGUnfavourable,
            $profile->actionSafetyFactors->gammaQ,
            $profile->fundamentalUltimateCombinationExpression,
        );
    }

    public function calculateServiceability(CharacteristicActions $actions, DesignCodeProfile $profile): ServiceabilityCombinations
    {
        $this->ensureNonNegativeFinite($actions->permanentTotal, ActionCombinationRejectionReason::INVALID_TOTAL_PERMANENT_LOAD);
        $this->ensureNonNegativeFinite($actions->variableCharacteristic, ActionCombinationRejectionReason::INVALID_VARIABLE_CHARACTERISTIC_LOAD);

        $factors = $profile->combinationFactorsFor($actions->variableActionCategory);
        if ($factors === null) {
            throw new ActionCombinationException(ActionCombinationRejectionReason::MISSING_VARIABLE_ACTION_FACTORS);
        }
        $this->ensureNonNegativeFinite($factors->psi1, ActionCombinationRejectionReason::INVALID_FREQUENT_VARIABLE_FACTOR);
        $this->ensureNonNegativeFinite($factors->psi2, ActionCombinationRejectionReason::INVALID_QUASI_PERMANENT_VARIABLE_FACTOR);

        return new ServiceabilityCombinations(
            characteristic: $this->combine($actions, 1.0, self::LEADING_VARIABLE_FACTOR, ServiceabilityCombinationExpression::EN1990_6_14),
            frequent: $this->combine($actions, 1.0, $factors->psi1, ServiceabilityCombinationExpression::EN1990_6_15),
            quasiPermanent: $this->combine($actions, 1.0, $factors->psi2, ServiceabilityCombinationExpression::EN1990_6_16),
        );
    }

    private function combine(
        CharacteristicActions $actions,
        float $permanentFactor,
        float $variableFactor,
        FundamentalUltimateCombinationExpression|ServiceabilityCombinationExpression $expressionReference,
    ): CombinedAction {
        $permanentContribution = $permanentFactor * $actions->permanentTotal;
        $variableContribution = $variableFactor * $actions->variableCharacteristic;

        return new CombinedAction(
            permanentCharacteristic: $actions->permanentTotal,
            variableCharacteristic: $actions->variableCharacteristic,
            permanentFactor: $permanentFactor,
            variableFactor: $variableFactor,
            permanentContribution: $permanentContribution,
            variableContribution: $variableContribution,
            result: $permanentContribution + $variableContribution,
            expressionReference: $expressionReference,
        );
    }

    private function ensureNonNegativeFinite(float $value, ActionCombinationRejectionReason $reason): void
    {
        if (! is_finite($value) || $value < 0) {
            throw new ActionCombinationException($reason);
        }
    }

    private function ensurePositiveFinite(float $value, ActionCombinationRejectionReason $reason): void
    {
        if (! is_finite($value) || $value <= 0) {
            throw new ActionCombinationException($reason);
        }
    }
}
