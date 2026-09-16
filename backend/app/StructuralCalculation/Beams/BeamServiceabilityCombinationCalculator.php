<?php

namespace App\StructuralCalculation\Beams;

use App\StructuralCalculation\Eurocode\Combinations\ActionCombinationCalculator;
use App\StructuralCalculation\Eurocode\Combinations\ActionCombinationException;
use App\StructuralCalculation\Eurocode\Combinations\CharacteristicActions;
use App\StructuralCalculation\Eurocode\Combinations\CombinedAction;
use App\StructuralCalculation\Eurocode\Profiles\DesignCodeProfile;

/** Applique les expressions ELS EN 1990 au cas V1 d'une action variable principale. */
final readonly class BeamServiceabilityCombinationCalculator
{
    public function __construct(private ActionCombinationCalculator $calculator) {}

    public function calculate(BeamCharacteristicActionsResult $actions, DesignCodeProfile $profile): BeamServiceabilityCombinationsResult
    {
        try {
            $combinations = $this->calculator->calculateServiceability(new CharacteristicActions(
                $actions->permanent->totalPermanentLoad,
                $actions->variable->characteristicLoad,
                $actions->variable->category,
            ), $profile);
        } catch (ActionCombinationException $exception) {
            throw new BeamServiceabilityCombinationException(BeamServiceabilityCombinationRejectionReason::from($exception->reason->value));
        }

        return new BeamServiceabilityCombinationsResult(
            characteristic: $this->result($combinations->characteristic, 'wSlsCharacteristic = Gk_total + Qk'),
            frequent: $this->result($combinations->frequent, 'wSlsFrequent = Gk_total + ψ1 × Qk'),
            quasiPermanent: $this->result($combinations->quasiPermanent, 'wSlsQuasiPermanent = Gk_total + ψ2 × Qk'),
        );
    }

    private function result(CombinedAction $combination, string $formula): BeamServiceabilityCombination
    {
        return new BeamServiceabilityCombination(
            permanentCharacteristicLoad: $combination->permanentCharacteristic,
            variableCharacteristicLoad: $combination->variableCharacteristic,
            variableFactor: $combination->variableFactor,
            permanentContribution: $combination->permanentContribution,
            variableContribution: $combination->variableContribution,
            resultingLineLoad: $combination->result,
            expressionReference: $combination->expressionReference,
            formula: $formula,
        );
    }
}
