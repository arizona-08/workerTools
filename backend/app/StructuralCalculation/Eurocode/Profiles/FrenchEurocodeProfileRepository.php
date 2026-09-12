<?php

namespace App\StructuralCalculation\Eurocode\Profiles;

use App\StructuralCalculation\Eurocode\Beams\BeamConcreteShearResistanceRequirements;
use App\StructuralCalculation\Eurocode\Beams\BeamLongitudinalReinforcementRequirements;
use App\StructuralCalculation\Eurocode\Cover\CoverRequirements;
use App\StructuralCalculation\Eurocode\ReinforcementSteel\ReinforcementSpacingRequirements;

/**
 * Profil MVP : NF EN 1992-1-1:2005 et Annexes Nationales françaises associées.
 *
 * La procédure française MVP retient EN 1990 6.10 pour l'ELU fondamental
 * persistant/transitoire bâtiment. Les variantes 6.10a/6.10b et ξ sont hors
 * périmètre, ainsi que les autres situations et catégories d'actions.
 */
final class FrenchEurocodeProfileRepository
{
    /** @var array{gammaC: float, gammaS: float, alphaCc: float} */
    private const MATERIAL_SAFETY_FACTORS = [
        'gammaC' => 1.5,
        'gammaS' => 1.15,
        'alphaCc' => 1.0,
    ];

    /** @var array{gammaGUnfavourable: float, gammaGFavourable: float, gammaQ: float} */
    private const ACTION_SAFETY_FACTORS = [
        'gammaGUnfavourable' => 1.35,
        'gammaGFavourable' => 1.0,
        'gammaQ' => 1.5,
    ];

    /** @var array<string, array{psi0: float, psi1: float, psi2: float}> */
    private const COMBINATION_FACTORS_BY_ACTION_CATEGORY = [
        'A' => ['psi0' => 0.7, 'psi1' => 0.5, 'psi2' => 0.3],
    ];

    /** @var array{minimumReinforcementStrengthCoefficient: float, minimumReinforcementRatio: float} */
    private const BEAM_LONGITUDINAL_REINFORCEMENT_REQUIREMENTS = [
        'minimumReinforcementStrengthCoefficient' => 0.26,
        'minimumReinforcementRatio' => 0.0013,
    ];

    /** @var array{concreteShearResistanceCoefficient: float, compressionStressCoefficient: float, minimumShearStressCoefficient: float, sizeEffectReferenceDepth: float, maximumSizeEffectFactor: float, maximumLongitudinalReinforcementRatio: float, minimumCotTheta: float, maximumCotTheta: float, minimumShearReinforcementCoefficient: float, concreteShearStrengthReductionCoefficient: float, concreteShearStrengthReductionReferenceStrength: float, nonPrestressedAlphaCw: float} */
    private const BEAM_CONCRETE_SHEAR_RESISTANCE_REQUIREMENTS = [
        'concreteShearResistanceCoefficient' => 0.12,
        'compressionStressCoefficient' => 0.15,
        'minimumShearStressCoefficient' => 0.035,
        'sizeEffectReferenceDepth' => 200.0,
        'maximumSizeEffectFactor' => 2.0,
        'maximumLongitudinalReinforcementRatio' => 0.02,
        'minimumCotTheta' => 1.0,
        'maximumCotTheta' => 2.5,
        'minimumShearReinforcementCoefficient' => 0.08,
        'concreteShearStrengthReductionCoefficient' => 0.6,
        'concreteShearStrengthReductionReferenceStrength' => 250.0,
        'nonPrestressedAlphaCw' => 1.0,
    ];

    /** @var array{barDiameterFactor: float, aggregateSizeAllowance: float, absoluteMinimumClearSpacing: float} */
    private const REINFORCEMENT_SPACING_REQUIREMENTS = [
        'barDiameterFactor' => 1.0,
        'aggregateSizeAllowance' => 5.0,
        'absoluteMinimumClearSpacing' => 20.0,
    ];

    public function get(): DesignCodeProfile
    {
        return new DesignCodeProfile(
            identifier: DesignCodeProfileIdentifier::NF_EN_1992_1_1_2005_FR,
            materialSafetyFactors: new MaterialSafetyFactors(...self::MATERIAL_SAFETY_FACTORS),
            actionSafetyFactors: new ActionSafetyFactors(...self::ACTION_SAFETY_FACTORS),
            fundamentalUltimateCombinationExpression: FundamentalUltimateCombinationExpression::EN1990_6_10,
            coverRequirements: CoverRequirements::frenchMvp(),
            beamLongitudinalReinforcementRequirements: new BeamLongitudinalReinforcementRequirements(
                ...self::BEAM_LONGITUDINAL_REINFORCEMENT_REQUIREMENTS,
            ),
            beamConcreteShearResistanceRequirements: new BeamConcreteShearResistanceRequirements(
                ...self::BEAM_CONCRETE_SHEAR_RESISTANCE_REQUIREMENTS,
            ),
            reinforcementSpacingRequirements: new ReinforcementSpacingRequirements(
                ...self::REINFORCEMENT_SPACING_REQUIREMENTS,
            ),
            combinationFactorsByActionCategory: array_map(
                fn (array $factors): CombinationFactors => new CombinationFactors(...$factors),
                self::COMBINATION_FACTORS_BY_ACTION_CATEGORY,
            ),
        );
    }

    public function find(string $identifier): ?DesignCodeProfile
    {
        return DesignCodeProfileIdentifier::tryFrom($identifier) === null ? null : $this->get();
    }
}
