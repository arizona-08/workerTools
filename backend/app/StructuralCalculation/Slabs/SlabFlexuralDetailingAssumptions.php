<?php

namespace App\StructuralCalculation\Slabs;

use App\StructuralCalculation\Detailing\PreliminaryLongitudinalBarDiameter;

/** Hypothèse explicite de Ø principal avant la proposition SLAB-08. */
final readonly class SlabFlexuralDetailingAssumptions
{
    public function __construct(public float $preliminaryMainBarDiameter) {}

    public static function mvp(): self
    {
        return new self(PreliminaryLongitudinalBarDiameter::MVP_MILLIMETRES);
    }
}
