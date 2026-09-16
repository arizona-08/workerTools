<?php

namespace App\StructuralCalculation\Slabs;

/** Discrétisation applicative V1 des espacements de recherche, sans valeur normative. */
final class SlabMainReinforcementProposalConfiguration
{
    /** @var list<float> */
    private const CANDIDATE_SPACINGS = [100.0, 125.0, 150.0, 175.0, 200.0, 250.0, 300.0];

    /** @param list<float>|null $candidateSpacings */
    public function __construct(private ?array $candidateSpacings = null) {}

    /** @return list<float> */
    public function candidateSpacings(): array
    {
        return $this->candidateSpacings ?? self::CANDIDATE_SPACINGS;
    }
}
