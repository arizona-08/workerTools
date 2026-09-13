<?php

namespace App\StructuralCalculation\Eurocode\Beams;

use App\StructuralCalculation\Beams\BeamSupportSystem;

/** Paramètres EC2 §7.4.2 de la vérification simplifiée portée / hauteur utile. */
final readonly class BeamDeflectionRequirements
{
    /** @param array<string, float> $structuralFactorsBySupportSystem */
    public function __construct(
        public float $baseRatioConstant,
        public float $lowReinforcementCoefficient,
        public float $lowReinforcementAdditionalCoefficient,
        public float $highReinforcementCompressionCoefficient,
        public float $referenceReinforcementRatioFactor,
        public float $referenceSteelStrength,
        private array $structuralFactorsBySupportSystem,
    ) {}

    public function structuralFactorFor(BeamSupportSystem $supportSystem): ?float
    {
        return $this->structuralFactorsBySupportSystem[$supportSystem->value] ?? null;
    }
}
