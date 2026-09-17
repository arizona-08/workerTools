<?php

namespace App\StructuralCalculation\Slabs;

use App\StructuralCalculation\Eurocode\Cover\CoverCalculationResult;

/** Détermine d pour la nappe tendue inférieure de la dalle simplement appuyée. */
final class SlabEffectiveDepthCalculator
{
    public function calculate(SlabGeometry $geometry, CoverCalculationResult $cover, SlabFlexuralDetailingAssumptions $detailing): SlabEffectiveDepthResult
    {
        $this->ensurePositiveFinite($geometry->thickness);
        $this->ensureNonNegativeFinite($cover->cNom);
        $this->ensurePositiveFinite($detailing->preliminaryMainBarDiameter);

        $offset = $cover->cNom + $detailing->preliminaryMainBarDiameter / 2;
        $effectiveDepth = $geometry->thickness - $offset;
        if (! is_finite($effectiveDepth) || $effectiveDepth <= 0) {
            throw new SlabUlsFlexureException(SlabUlsFlexureRejectionReason::NON_POSITIVE_EFFECTIVE_DEPTH);
        }

        return new SlabEffectiveDepthResult(
            overallDepth: $geometry->thickness,
            nominalCover: $cover->cNom,
            preliminaryMainBarDiameter: $detailing->preliminaryMainBarDiameter,
            tensionSteelCentroidOffset: $offset,
            effectiveDepth: $effectiveDepth,
            substitution: sprintf('d = %s - %s - %s / 2', $geometry->thickness, $cover->cNom, $detailing->preliminaryMainBarDiameter),
        );
    }

    private function ensurePositiveFinite(float $value): void
    {
        if (! is_finite($value) || $value <= 0) {
            throw new SlabUlsFlexureException(SlabUlsFlexureRejectionReason::INVALID_INPUT);
        }
    }

    private function ensureNonNegativeFinite(float $value): void
    {
        if (! is_finite($value) || $value < 0) {
            throw new SlabUlsFlexureException(SlabUlsFlexureRejectionReason::INVALID_INPUT);
        }
    }
}
