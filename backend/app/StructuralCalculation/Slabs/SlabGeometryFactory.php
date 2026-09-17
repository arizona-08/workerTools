<?php

namespace App\StructuralCalculation\Slabs;

/** Valide les longueurs internes d'une dalle ; la bande unitaire reste une hypothèse du domaine. */
final class SlabGeometryFactory
{
    public function fromInternalValues(mixed $effectiveSpan, mixed $thickness): SlabGeometry
    {
        return new SlabGeometry(
            effectiveSpan: $this->positiveFiniteNumber($effectiveSpan, SlabGeometryRejectionReason::MISSING_EFFECTIVE_SPAN, SlabGeometryRejectionReason::INVALID_EFFECTIVE_SPAN),
            thickness: $this->positiveFiniteNumber($thickness, SlabGeometryRejectionReason::MISSING_THICKNESS, SlabGeometryRejectionReason::INVALID_THICKNESS),
        );
    }

    private function positiveFiniteNumber(mixed $value, SlabGeometryRejectionReason $missingReason, SlabGeometryRejectionReason $invalidReason): float
    {
        if ($value === null) {
            throw new SlabGeometryException($missingReason);
        }
        if (is_bool($value) || ! is_numeric($value)) {
            throw new SlabGeometryException($invalidReason);
        }

        $number = (float) $value;

        if (! is_finite($number) || $number <= 0) {
            throw new SlabGeometryException($invalidReason);
        }

        return $number;
    }
}
