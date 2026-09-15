<?php

namespace App\StructuralCalculation\Eurocode\Profiles;

use App\StructuralCalculation\Eurocode\Beams\BeamConcreteShearResistanceRequirements;
use App\StructuralCalculation\Eurocode\Beams\BeamCrackWidthRequirements;
use App\StructuralCalculation\Eurocode\Beams\BeamDeflectionRequirements;
use App\StructuralCalculation\Eurocode\Beams\BeamLongitudinalReinforcementRequirements;
use App\StructuralCalculation\Eurocode\Beams\BeamServiceStressRequirements;
use App\StructuralCalculation\Eurocode\Cover\CoverRequirements;
use App\StructuralCalculation\Eurocode\ReinforcementSteel\ReinforcementSpacingRequirements;
use App\StructuralCalculation\Eurocode\Slabs\SlabReinforcementRequirements;

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
        public FundamentalUltimateCombinationExpression $fundamentalUltimateCombinationExpression,
        public CoverRequirements $coverRequirements,
        public BeamLongitudinalReinforcementRequirements $beamLongitudinalReinforcementRequirements,
        public BeamConcreteShearResistanceRequirements $beamConcreteShearResistanceRequirements,
        public BeamCrackWidthRequirements $beamCrackWidthRequirements,
        public BeamDeflectionRequirements $beamDeflectionRequirements,
        public BeamServiceStressRequirements $beamServiceStressRequirements,
        public ReinforcementSpacingRequirements $reinforcementSpacingRequirements,
        public SlabReinforcementRequirements $slabReinforcementRequirements,
        private array $combinationFactorsByActionCategory,
    ) {}

    public function combinationFactorsFor(VariableActionCategory $category): ?CombinationFactors
    {
        return $this->combinationFactorsByActionCategory[$category->value] ?? null;
    }
}
