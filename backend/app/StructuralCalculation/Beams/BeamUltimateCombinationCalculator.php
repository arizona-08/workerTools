<?php

namespace App\StructuralCalculation\Beams;

use App\StructuralCalculation\Eurocode\Combinations\ActionCombinationCalculator;
use App\StructuralCalculation\Eurocode\Combinations\ActionCombinationException;
use App\StructuralCalculation\Eurocode\Combinations\CharacteristicActions;
use App\StructuralCalculation\Eurocode\Profiles\DesignCodeProfile;

/** Applique EN 1990 6.10 au cas MVP d'une action variable principale. */
final readonly class BeamUltimateCombinationCalculator
{
    public function __construct(private ActionCombinationCalculator $calculator) {}

    public function calculate(BeamCharacteristicActionsResult $actions, DesignCodeProfile $profile): BeamUltimateCombinationResult
    {
        try {
            $combination = $this->calculator->calculateUltimate(new CharacteristicActions(
                $actions->permanent->totalPermanentLoad,
                $actions->variable->characteristicLoad,
                $actions->variable->category,
            ), $profile);
        } catch (ActionCombinationException $exception) {
            throw new BeamUltimateCombinationException(BeamUltimateCombinationRejectionReason::from($exception->reason->value));
        }

        return new BeamUltimateCombinationResult(
            permanentCharacteristicLoad: $combination->permanentCharacteristic,
            permanentPartialFactor: $combination->permanentFactor,
            permanentDesignContribution: $combination->permanentContribution,
            variableCharacteristicLoad: $combination->variableCharacteristic,
            variablePartialFactor: $combination->variableFactor,
            variableDesignContribution: $combination->variableContribution,
            designLineLoad: $combination->result,
            expressionReference: $combination->expressionReference,
        );
    }
}
