<?php

namespace App\StructuralCalculation\Beams;

use BackedEnum;
use JsonSerializable;

/** Réponse transportable d'un calcul complet ; la normalisation ne recalcule aucune donnée métier. */
final readonly class BeamCalculationResponse implements JsonSerializable
{
    public function __construct(
        public BeamResultSummary $summary,
        public BeamVerificationAggregationResult $verifications,
        public BeamCalculationDetails $details,
    ) {}

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return self::normalize([
            'summary' => $this->summary,
            'verifications' => $this->verifications,
            'details' => $this->details,
        ]);
    }

    private static function normalize(mixed $value): mixed
    {
        if ($value instanceof BackedEnum) {
            return $value->value;
        }
        if ($value instanceof \UnitEnum) {
            return $value->name;
        }
        if (is_array($value)) {
            return array_map(self::normalize(...), $value);
        }
        if (is_object($value)) {
            return self::normalize(get_object_vars($value));
        }

        return $value;
    }
}
