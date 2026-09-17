<?php

namespace App\StructuralCalculation\Eurocode\Beams;

/** Paramètres de cisaillement fournis par le profil normatif (EC2 §§6.2.2, 6.2.3 et 9.2.2). */
final readonly class BeamConcreteShearResistanceRequirements
{
    public function __construct(
        public float $concreteShearResistanceCoefficient,
        public float $compressionStressCoefficient,
        public float $minimumShearStressCoefficient,
        public float $sizeEffectReferenceDepth,
        public float $maximumSizeEffectFactor,
        public float $maximumLongitudinalReinforcementRatio,
        public float $minimumCotTheta,
        public float $maximumCotTheta,
        public float $minimumShearReinforcementCoefficient,
        public float $concreteShearStrengthReductionCoefficient,
        public float $concreteShearStrengthReductionReferenceStrength,
        public float $nonPrestressedAlphaCw,
        public float $maximumLongitudinalStirrupSpacingFactor,
        public float $maximumTransverseLegSpacingFactor,
        public float $absoluteMaximumTransverseLegSpacing,
    ) {}
}
