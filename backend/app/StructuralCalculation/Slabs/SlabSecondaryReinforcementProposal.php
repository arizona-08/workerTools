<?php

namespace App\StructuralCalculation\Slabs;

/** Proposition transversale de répartition, distincte de la nappe principale porteuse. */
final readonly class SlabSecondaryReinforcementProposal
{
    public const PROVIDED_AREA_FORMULA = 'As_secondary_provided/m = Aφ × 1000 / s';

    public function __construct(
        public float $barDiameter,
        public float $spacing,
        public float $barArea,
        public float $providedAreaPerMeter,
        public float $overProvision,
    ) {}

    public function label(): string
    {
        return sprintf('HA%s / %s mm', $this->barDiameter, $this->spacing);
    }
}
