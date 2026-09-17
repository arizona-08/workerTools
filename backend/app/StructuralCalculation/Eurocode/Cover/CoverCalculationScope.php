<?php

namespace App\StructuralCalculation\Eurocode\Cover;

/** Limites explicites du calcul automatique d'enrobage V1. */
final readonly class CoverCalculationScope
{
    public function __construct(
        public bool $reinforcedConcrete = true,
        public bool $passiveReinforcement = true,
        public bool $individualBars = true,
        public bool $ordinaryCarbonSteel = true,
        public bool $withoutAdditionalProtection = true,
        public bool $withoutSpecialSurfaceTreatment = true,
        public bool $withoutFireDesign = true,
    ) {}

    public function isSupported(): bool
    {
        return $this->reinforcedConcrete
            && $this->passiveReinforcement
            && $this->individualBars
            && $this->ordinaryCarbonSteel
            && $this->withoutAdditionalProtection
            && $this->withoutSpecialSurfaceTreatment
            && $this->withoutFireDesign;
    }
}
