<?php

namespace App\StructuralCalculation\Slabs;

use App\StructuralCalculation\Units\LengthConverter;

/** Convertit les combinaisons q de SLAB-05 en charges de bande w, sans analyse statique. */
final readonly class SlabStripLinearLoadsCalculator
{
    public function __construct(private LengthConverter $lengthConverter) {}

    public function calculate(SlabGeometry $geometry, SlabActionCombinations $combinations): SlabStripLinearLoads
    {
        $this->ensurePositiveFinite($geometry->effectiveSpan, SlabStripLinearLoadsRejectionReason::INVALID_EFFECTIVE_SPAN);
        $this->ensurePositiveFinite($geometry->calculationStripWidth, SlabStripLinearLoadsRejectionReason::INVALID_CALCULATION_STRIP_WIDTH);

        $stripWidthMetres = $this->lengthConverter->millimetresToMetres($geometry->calculationStripWidth);

        return new SlabStripLinearLoads(
            uls: $this->linearLoad($combinations->uls, $geometry->calculationStripWidth, $stripWidthMetres, SlabStripLinearLoadsRejectionReason::INVALID_ULTIMATE_SURFACE_LOAD),
            slsCharacteristic: $this->linearLoad($combinations->slsCharacteristic, $geometry->calculationStripWidth, $stripWidthMetres, SlabStripLinearLoadsRejectionReason::INVALID_CHARACTERISTIC_SURFACE_LOAD),
            slsFrequent: $this->linearLoad($combinations->slsFrequent, $geometry->calculationStripWidth, $stripWidthMetres, SlabStripLinearLoadsRejectionReason::INVALID_FREQUENT_SURFACE_LOAD),
            slsQuasiPermanent: $this->linearLoad($combinations->slsQuasiPermanent, $geometry->calculationStripWidth, $stripWidthMetres, SlabStripLinearLoadsRejectionReason::INVALID_QUASI_PERMANENT_SURFACE_LOAD),
        );
    }

    private function linearLoad(
        SlabSurfaceLoadCombination $surfaceLoadCombination,
        float $stripWidthMillimetres,
        float $stripWidthMetres,
        SlabStripLinearLoadsRejectionReason $reason,
    ): SlabStripLinearLoad {
        $this->ensureNonNegativeFinite($surfaceLoadCombination->value, $reason);

        return new SlabStripLinearLoad(
            surfaceLoadCombination: $surfaceLoadCombination,
            stripWidthMillimetres: $stripWidthMillimetres,
            stripWidthMetres: $stripWidthMetres,
            surfaceLoad: $surfaceLoadCombination->value,
            lineLoad: $surfaceLoadCombination->value * $stripWidthMetres,
            name: $this->nameFor($surfaceLoadCombination->type),
            substitution: sprintf('w = %s × %s', $surfaceLoadCombination->value, $stripWidthMetres),
        );
    }

    private function nameFor(SlabSurfaceLoadCombinationType $type): string
    {
        return match ($type) {
            SlabSurfaceLoadCombinationType::ULTIMATE => 'Charge linéique ELU de la bande',
            SlabSurfaceLoadCombinationType::SERVICEABILITY_CHARACTERISTIC => 'Charge linéique ELS caractéristique de la bande',
            SlabSurfaceLoadCombinationType::SERVICEABILITY_FREQUENT => 'Charge linéique ELS fréquente de la bande',
            SlabSurfaceLoadCombinationType::SERVICEABILITY_QUASI_PERMANENT => 'Charge linéique ELS quasi-permanente de la bande',
        };
    }

    private function ensurePositiveFinite(float $value, SlabStripLinearLoadsRejectionReason $reason): void
    {
        if (! is_finite($value) || $value <= 0) {
            throw new SlabStripLinearLoadsException($reason);
        }
    }

    private function ensureNonNegativeFinite(float $value, SlabStripLinearLoadsRejectionReason $reason): void
    {
        if (! is_finite($value) || $value < 0) {
            throw new SlabStripLinearLoadsException($reason);
        }
    }
}
