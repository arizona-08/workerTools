<?php

namespace App\StructuralCalculation\Beams;

/**
 * Construit la géométrie depuis le futur payload, exclusivement exprimé en mm.
 * Les conversions d'unités UI doivent être faites avant cet adaptateur.
 */
final class BeamGeometryFactory
{
    public function fromInternalValues(mixed $effectiveSpan, mixed $width, mixed $height): BeamGeometry
    {
        return new BeamGeometry(
            effectiveSpan: $this->positiveFiniteNumber($effectiveSpan, BeamGeometryRejectionReason::MISSING_EFFECTIVE_SPAN, BeamGeometryRejectionReason::INVALID_EFFECTIVE_SPAN),
            width: $this->positiveFiniteNumber($width, BeamGeometryRejectionReason::MISSING_WIDTH, BeamGeometryRejectionReason::INVALID_WIDTH),
            height: $this->positiveFiniteNumber($height, BeamGeometryRejectionReason::MISSING_HEIGHT, BeamGeometryRejectionReason::INVALID_HEIGHT),
        );
    }

    private function positiveFiniteNumber(mixed $value, BeamGeometryRejectionReason $missingReason, BeamGeometryRejectionReason $invalidReason): float
    {
        if ($value === null) {
            throw new BeamGeometryException($missingReason);
        }
        if (is_bool($value) || ! is_numeric($value)) {
            throw new BeamGeometryException($invalidReason);
        }

        $number = (float) $value;

        if (! is_finite($number) || $number <= 0) {
            throw new BeamGeometryException($invalidReason);
        }

        return $number;
    }
}
