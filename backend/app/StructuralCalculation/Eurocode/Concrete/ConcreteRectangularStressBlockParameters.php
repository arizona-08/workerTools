<?php

namespace App\StructuralCalculation\Eurocode\Concrete;

/** Paramètres du bloc rectangulaire simplifié de béton d'EN 1992-1-1 §3.1.7. */
final readonly class ConcreteRectangularStressBlockParameters
{
    public function __construct(
        public float $fck,
        public float $lambda,
        public float $eta,
    ) {}
}
