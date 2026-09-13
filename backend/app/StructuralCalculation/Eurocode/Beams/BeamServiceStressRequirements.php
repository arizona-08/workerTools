<?php

namespace App\StructuralCalculation\Eurocode\Beams;

/** Limites de contraintes ELS §7.2 fournies par un profil national. */
final readonly class BeamServiceStressRequirements
{
    public function __construct(
        public float $concreteCharacteristicStressLimitFactor,
        public float $concreteQuasiPermanentStressLimitFactor,
        public float $reinforcementCharacteristicStressLimitFactor,
    ) {}
}
