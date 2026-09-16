<?php

namespace App\StructuralCalculation\Eurocode\Slabs;

/** Paramètres EC2 §9.3.1.1 applicables aux armatures d'une dalle pleine unidirectionnelle. */
final readonly class SlabReinforcementRequirements
{
    public function __construct(
        public float $secondaryReinforcementRatio,
        public float $secondarySpacingHeightFactor,
        public float $maximumSecondarySpacing,
    ) {}

    /** Dispositions générales EN 1992-1-1:2004 §9.3.1.1 du profil français V1. */
    public static function frenchSupported(): self
    {
        return new self(0.20, 3.5, 450.0);
    }
}
