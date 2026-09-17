<?php

namespace App\StructuralCalculation\Slabs;

/** Résultat local de recherche de ferraillage ; ce n'est pas une conformité globale Dalle. */
final readonly class SlabMainReinforcementProposalResult
{
    /** @param list<float> $diameterCatalogue @param list<float> $spacingCatalogue */
    public function __construct(
        public float $initialTargetArea,
        public array $diameterCatalogue,
        public array $spacingCatalogue,
        public SlabMainReinforcementProposalStatus $status,
        public ?SlabMainReinforcementProposal $proposal,
        public int $generatedCandidateCount,
        public int $insufficientCandidateCount,
        public int $unsupportedCandidateCount,
    ) {}
}
