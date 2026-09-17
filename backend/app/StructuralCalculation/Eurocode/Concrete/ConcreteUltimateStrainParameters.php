<?php

namespace App\StructuralCalculation\Eurocode\Concrete;

/** Déformation ultime béton εcu3 du diagramme de section EC2. */
final readonly class ConcreteUltimateStrainParameters
{
    public const STRAIN_UNIT = 'dimensionless';

    public function __construct(
        public float $characteristicConcreteStrength,
        public float $ultimateStrain,
    ) {}
}
