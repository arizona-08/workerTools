<?php

namespace App\StructuralCalculation\Slabs;

/** Point d'entrée métier SLAB-11 : compose les étapes existantes sans formule propre. */
final readonly class SlabCalculationOrchestrator
{
    public function __construct(private SlabCharacteristicActionsCalculator $actions, private SlabActionCombinationsCalculator $combinations, private SlabStripAnalysisCalculator $analysis, private SlabUlsFlexureCalculator $flexure, private SlabMainReinforcementProposalGenerator $main, private SlabSecondaryReinforcementProposalGenerator $secondary, private SlabServiceabilityCalculator $serviceability, private SlabCalculationResultAssembler $results) {}

    public function calculate(SlabCalculationInput $input): SlabCalculationResult
    {
        $actions = $this->actions->calculate($input->geometry, $input->loads);
        $combinations = $this->combinations->calculate($input->configuration, $actions);
        $analysis = $this->analysis->calculate($input->configuration, $input->geometry, $combinations);
        $flexure = $this->flexure->calculate($input, $analysis);
        $main = $this->main->generate($input, $analysis, $flexure);
        $secondary = $this->secondary->generate($input->configuration, $input->geometry, $main);
        $serviceability = $this->serviceability->calculate($input, $analysis, $main);

        return $this->results->assemble($input, $actions, $combinations, $analysis, $flexure, $main, $secondary, $serviceability);
    }
}
