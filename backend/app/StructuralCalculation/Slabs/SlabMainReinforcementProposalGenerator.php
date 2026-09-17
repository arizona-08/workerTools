<?php

namespace App\StructuralCalculation\Slabs;

use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementBarAreaCalculator;
use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementBarDiameterCatalog;

/** Recherche déterministe diamètre/espacement pour la nappe principale, après recalcul ELU de chaque candidat. */
final readonly class SlabMainReinforcementProposalGenerator
{
    public function __construct(
        private ReinforcementBarDiameterCatalog $diameters,
        private SlabMainReinforcementProposalConfiguration $configuration,
        private ReinforcementBarAreaCalculator $barAreaCalculator,
        private SlabUlsFlexureCalculator $flexureCalculator,
    ) {}

    public function generate(
        SlabCalculationInput $input,
        SlabStripAnalysis $analysis,
        SlabUlsFlexureResult $preliminaryFlexure,
    ): SlabMainReinforcementProposalResult {
        $diameters = $this->diameters->all();
        $spacings = $this->configuration->candidateSpacings();
        $valid = [];
        $insufficient = 0;
        $unsupported = 0;

        foreach ($diameters as $diameter) {
            foreach ($spacings as $spacing) {
                $barArea = $this->barAreaCalculator->calculate($diameter);
                $providedArea = $barArea * 1000 / $spacing;

                try {
                    $flexure = $this->flexureCalculator->calculate(
                        $input,
                        $analysis,
                        new SlabFlexuralDetailingAssumptions($diameter),
                    );
                } catch (SlabUlsFlexureException) {
                    $unsupported++;

                    continue;
                }

                if ($providedArea < $flexure->designReinforcementArea) {
                    $insufficient++;

                    continue;
                }

                $valid[] = new SlabMainReinforcementProposal(
                    barDiameter: $diameter,
                    spacing: $spacing,
                    barArea: $barArea,
                    providedAreaPerMeter: $providedArea,
                    recalculatedFlexure: $flexure,
                    overProvision: $providedArea - $flexure->designReinforcementArea,
                );
            }
        }

        usort($valid, fn (SlabMainReinforcementProposal $left, SlabMainReinforcementProposal $right): int => $left->overProvision <=> $right->overProvision
            ?: $right->spacing <=> $left->spacing
            ?: $left->barDiameter <=> $right->barDiameter);

        return new SlabMainReinforcementProposalResult(
            initialTargetArea: $preliminaryFlexure->designReinforcementArea,
            diameterCatalogue: $diameters,
            spacingCatalogue: $spacings,
            status: $valid === [] ? SlabMainReinforcementProposalStatus::NO_VALID_REINFORCEMENT_PROPOSAL : SlabMainReinforcementProposalStatus::REINFORCEMENT_PROPOSAL_FOUND,
            proposal: $valid[0] ?? null,
            generatedCandidateCount: count($diameters) * count($spacings),
            insufficientCandidateCount: $insufficient,
            unsupportedCandidateCount: $unsupported,
        );
    }
}
