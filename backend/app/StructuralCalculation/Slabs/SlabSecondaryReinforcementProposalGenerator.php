<?php

namespace App\StructuralCalculation\Slabs;

use App\StructuralCalculation\Eurocode\Profiles\FrenchEurocodeProfileRepository;
use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementBarAreaCalculator;
use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementBarDiameterCatalog;

/** Génère la nappe secondaire de répartition à partir de l'armature principale réellement fournie. */
final readonly class SlabSecondaryReinforcementProposalGenerator
{
    public function __construct(
        private FrenchEurocodeProfileRepository $profiles,
        private ReinforcementBarDiameterCatalog $diameters,
        private SlabMainReinforcementProposalConfiguration $spacingConfiguration,
        private ReinforcementBarAreaCalculator $barAreaCalculator,
    ) {}

    public function generate(SlabCalculationConfiguration $configuration, SlabGeometry $geometry, SlabMainReinforcementProposalResult $main): SlabSecondaryReinforcementResult
    {
        if ($main->proposal === null) {
            return new SlabSecondaryReinforcementResult(0, 0, 0, 0, SlabSecondaryReinforcementProposalStatus::NO_VALID_SECONDARY_REINFORCEMENT_PROPOSAL, null, 0, 0);
        }

        $profile = $this->profiles->find($configuration->designCodeProfile->value);
        if ($profile === null || ! is_finite($geometry->thickness) || $geometry->thickness <= 0) {
            throw new \InvalidArgumentException('The slab secondary reinforcement input is invalid.');
        }

        $requirements = $profile->slabReinforcementRequirements;
        $mainProvided = $main->proposal->providedAreaPerMeter;
        $minimum = $requirements->secondaryReinforcementRatio * $mainProvided;
        $maximumSpacing = min($requirements->secondarySpacingHeightFactor * $geometry->thickness, $requirements->maximumSecondarySpacing);
        $valid = [];
        $insufficient = 0;
        $excessiveSpacing = 0;

        foreach ($this->diameters->all() as $diameter) {
            $barArea = $this->barAreaCalculator->calculate($diameter);
            foreach ($this->spacingConfiguration->candidateSpacings() as $spacing) {
                if ($spacing > $maximumSpacing) {
                    $excessiveSpacing++;

                    continue;
                }
                $provided = $barArea * 1000 / $spacing;
                if ($provided < $minimum) {
                    $insufficient++;

                    continue;
                }
                $valid[] = new SlabSecondaryReinforcementProposal($diameter, $spacing, $barArea, $provided, $provided - $minimum);
            }
        }

        usort($valid, fn (SlabSecondaryReinforcementProposal $left, SlabSecondaryReinforcementProposal $right): int => $left->overProvision <=> $right->overProvision
            ?: $right->spacing <=> $left->spacing
            ?: $left->barDiameter <=> $right->barDiameter);

        return new SlabSecondaryReinforcementResult(
            $mainProvided,
            $requirements->secondaryReinforcementRatio,
            $minimum,
            $maximumSpacing,
            $valid === [] ? SlabSecondaryReinforcementProposalStatus::NO_VALID_SECONDARY_REINFORCEMENT_PROPOSAL : SlabSecondaryReinforcementProposalStatus::SECONDARY_REINFORCEMENT_PROPOSAL_FOUND,
            $valid[0] ?? null,
            $insufficient,
            $excessiveSpacing,
        );
    }
}
