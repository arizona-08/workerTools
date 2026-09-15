<?php

namespace App\StructuralCalculation\Slabs;

use App\StructuralCalculation\DesignSituation;
use App\StructuralCalculation\Eurocode\Combinations\ActionCombinationCalculator;
use App\StructuralCalculation\Eurocode\Combinations\CharacteristicActions;
use App\StructuralCalculation\Eurocode\Combinations\CombinedAction;
use App\StructuralCalculation\Eurocode\Profiles\FrenchEurocodeProfileRepository;
use App\StructuralCalculation\Eurocode\Profiles\VariableActionCategory;
use LogicException;

/** Adaptateur Dalle du moteur commun EN 1990 ; il ne contient aucune formule. */
final readonly class SlabActionCombinationsCalculator
{
    public function __construct(
        private ActionCombinationCalculator $calculator,
        private FrenchEurocodeProfileRepository $profiles,
    ) {}

    public function calculate(SlabCalculationConfiguration $configuration, SlabCharacteristicActions $actions): SlabActionCombinations
    {
        if ($configuration->designSituation !== DesignSituation::PERSISTENT_TRANSIENT) {
            throw new LogicException('UNSUPPORTED_SLAB_DESIGN_SITUATION');
        }
        $profile = $this->profiles->find($configuration->designCodeProfile->value)
            ?? throw new LogicException('UNSUPPORTED_SLAB_DESIGN_CODE_PROFILE');
        $characteristicActions = new CharacteristicActions(
            $actions->permanentTotal,
            $actions->imposedLoad,
            VariableActionCategory::A_DOMESTIC_RESIDENTIAL_AREAS,
        );
        $ultimate = $this->calculator->calculateUltimate($characteristicActions, $profile);
        $serviceability = $this->calculator->calculateServiceability($characteristicActions, $profile);

        return new SlabActionCombinations(
            uls: $this->surfaceCombination($ultimate, SlabSurfaceLoadCombinationType::ULTIMATE, 'qEd = γG,sup × Gk_total + γQ × Qk'),
            slsCharacteristic: $this->surfaceCombination($serviceability->characteristic, SlabSurfaceLoadCombinationType::SERVICEABILITY_CHARACTERISTIC, 'qSlsCharacteristic = Gk_total + Qk'),
            slsFrequent: $this->surfaceCombination($serviceability->frequent, SlabSurfaceLoadCombinationType::SERVICEABILITY_FREQUENT, 'qSlsFrequent = Gk_total + ψ1 × Qk'),
            slsQuasiPermanent: $this->surfaceCombination($serviceability->quasiPermanent, SlabSurfaceLoadCombinationType::SERVICEABILITY_QUASI_PERMANENT, 'qSlsQuasiPermanent = Gk_total + ψ2 × Qk'),
        );
    }

    private function surfaceCombination(CombinedAction $combination, SlabSurfaceLoadCombinationType $type, string $formula): SlabSurfaceLoadCombination
    {
        return new SlabSurfaceLoadCombination(
            type: $type,
            permanentCharacteristicLoad: $combination->permanentCharacteristic,
            variableCharacteristicLoad: $combination->variableCharacteristic,
            permanentFactor: $combination->permanentFactor,
            variableFactor: $combination->variableFactor,
            permanentContribution: $combination->permanentContribution,
            variableContribution: $combination->variableContribution,
            value: $combination->result,
            expressionReference: $combination->expressionReference,
            formula: $formula,
        );
    }
}
