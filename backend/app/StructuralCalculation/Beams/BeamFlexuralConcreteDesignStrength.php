<?php

namespace App\StructuralCalculation\Beams;

use App\StructuralCalculation\Materials\Concrete\ConcreteStrengthClass;

/** Résistance de calcul béton, avec les données matériau et profil qui la composent. */
final readonly class BeamFlexuralConcreteDesignStrength
{
    public const UNIT = 'MPa';

    public function __construct(
        public ConcreteStrengthClass $concreteClass,
        public float $fck,
        public float $alphaCc,
        public float $gammaC,
        public float $fcd,
    ) {}
}
