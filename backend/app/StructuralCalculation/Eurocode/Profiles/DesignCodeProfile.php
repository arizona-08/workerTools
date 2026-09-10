<?php

namespace App\StructuralCalculation\Eurocode\Profiles;

use App\StructuralCalculation\Eurocode\Cover\CoverRequirements;

/**
 * Jeu de paramètres normatifs sélectionné par un calcul.
 *
 * Les matériaux intrinsèques restent indépendants de ce profil.
 */
final readonly class DesignCodeProfile
{
    /**
     * @param  array<string, CombinationFactors>  $combinationFactorsByActionCategory
     */
    public function __construct(
        public DesignCodeProfileIdentifier $identifier,
        public MaterialSafetyFactors $materialSafetyFactors,
        public ActionSafetyFactors $actionSafetyFactors,
        public CoverRequirements $coverRequirements,
        private array $combinationFactorsByActionCategory,
    ) {}

    public function combinationFactorsFor(VariableActionCategory $category): ?CombinationFactors
    {
        return $this->combinationFactorsByActionCategory[$category->value] ?? null;
    }
}
