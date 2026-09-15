<?php

namespace App\StructuralCalculation\Slabs;

/** Ferraillage principal sélectionné pour une bande de dalle de 1 m. */
final readonly class SlabMainReinforcementProposal
{
    public const DIAMETER_UNIT = 'mm';

    public const SPACING_UNIT = 'mm';

    public const AREA_UNIT = 'mm²';

    public const AREA_PER_METRE_UNIT = 'mm²/m';

    public const PROVIDED_AREA_FORMULA = 'As_provided/m = Aφ × 1000 / s';

    public function __construct(
        public float $barDiameter,
        public float $spacing,
        public float $barArea,
        public float $providedAreaPerMeter,
        public SlabUlsFlexureResult $recalculatedFlexure,
        public float $overProvision,
    ) {}

    public function label(): string
    {
        return sprintf('HA%s / %s mm', $this->barDiameter, $this->spacing);
    }
}
