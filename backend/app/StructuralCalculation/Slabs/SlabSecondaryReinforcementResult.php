<?php

namespace App\StructuralCalculation\Slabs;

/** Résultat local des armatures secondaires ; aucune conformité globale ne peut en être déduite. */
final readonly class SlabSecondaryReinforcementResult
{
    public const AREA_PER_METRE_UNIT = 'mm²/m';

    public const SPACING_UNIT = 'mm';

    public const MINIMUM_FORMULA = 'As_secondary_min = ratio_secondary × As_main_provided';

    public function __construct(
        public float $mainProvidedAreaPerMeter,
        public float $secondaryReinforcementRatio,
        public float $minimumRequiredAreaPerMeter,
        public float $maximumAllowedSpacing,
        public SlabSecondaryReinforcementProposalStatus $status,
        public ?SlabSecondaryReinforcementProposal $proposal,
        public int $insufficientAreaCandidateCount,
        public int $excessiveSpacingCandidateCount,
    ) {}
}
