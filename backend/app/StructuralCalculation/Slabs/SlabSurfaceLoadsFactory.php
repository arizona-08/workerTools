<?php

namespace App\StructuralCalculation\Slabs;

/** Valide uniquement les actions caractéristiques surfaciques saisies par l'utilisateur. */
final class SlabSurfaceLoadsFactory
{
    public function fromValues(mixed $finishes, mixed $partitions, mixed $otherPermanent, mixed $imposedLoad): SlabSurfaceLoads
    {
        return new SlabSurfaceLoads(
            finishes: $this->nonNegativeFiniteNumber($finishes, SlabSurfaceLoadsRejectionReason::MISSING_FINISHES, SlabSurfaceLoadsRejectionReason::INVALID_FINISHES),
            partitions: $this->nonNegativeFiniteNumber($partitions, SlabSurfaceLoadsRejectionReason::MISSING_PARTITIONS, SlabSurfaceLoadsRejectionReason::INVALID_PARTITIONS),
            otherPermanent: $this->nonNegativeFiniteNumber($otherPermanent, SlabSurfaceLoadsRejectionReason::MISSING_OTHER_PERMANENT, SlabSurfaceLoadsRejectionReason::INVALID_OTHER_PERMANENT),
            imposedLoad: $this->nonNegativeFiniteNumber($imposedLoad, SlabSurfaceLoadsRejectionReason::MISSING_IMPOSED_LOAD, SlabSurfaceLoadsRejectionReason::INVALID_IMPOSED_LOAD),
        );
    }

    private function nonNegativeFiniteNumber(mixed $value, SlabSurfaceLoadsRejectionReason $missingReason, SlabSurfaceLoadsRejectionReason $invalidReason): float
    {
        if ($value === null) {
            throw new SlabSurfaceLoadsException($missingReason);
        }
        if (is_bool($value) || ! is_numeric($value)) {
            throw new SlabSurfaceLoadsException($invalidReason);
        }

        $number = (float) $value;

        if (! is_finite($number) || $number < 0) {
            throw new SlabSurfaceLoadsException($invalidReason);
        }

        return $number;
    }
}
