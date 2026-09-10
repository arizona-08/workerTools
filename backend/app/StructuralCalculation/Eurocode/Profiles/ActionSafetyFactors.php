<?php

namespace App\StructuralCalculation\Eurocode\Profiles;

/**
 * Coefficients d'actions pour la combinaison fondamentale ELU du MVP.
 */
final readonly class ActionSafetyFactors
{
    public function __construct(
        public float $gammaGUnfavourable,
        public float $gammaGFavourable,
        public float $gammaQ,
    ) {}
}
