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
 * Profil V1 : NF EN 1992-1-1:2005 et Annexes Nationales françaises associées.
 *
 * La procédure française V1 retient EN 1990 6.10 pour l'ELU fondamental
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

    /** @var array{concreteShearResistanceCoefficient: float, compressionStressCoefficient: float, minimumShearStressCoefficient: float, sizeEffectReferenceDepth: float, maximumSizeEffectFactor: float, maximumLongitudinalReinforcementRatio: float, minimumCotTheta: float, maximumCotTheta: float, minimumShearReinforcementCoefficient: float, concreteShearStrengthReductionCoefficient: float, concreteShearStrengthReductionReferenceStrength: float, nonPrestressedAlphaCw: float, maximumLongitudinalStirrupSpacingFactor: float, maximumTransverseLegSpacingFactor: float, absoluteMaximumTransverseLegSpacing: float} */
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
        'maximumLongitudinalStirrupSpacingFactor' => 0.75,
        'maximumTransverseLegSpacingFactor' => 0.75,
        'absoluteMaximumTransverseLegSpacing' => 600.0,
    ];

    /** @var array{concreteCharacteristicStressLimitFactor: float, concreteQuasiPermanentStressLimitFactor: float, reinforcementCharacteristicStressLimitFactor: float} */
    private const BEAM_SERVICE_STRESS_REQUIREMENTS = [
        'concreteCharacteristicStressLimitFactor' => 0.60,
        'concreteQuasiPermanentStressLimitFactor' => 0.45,
        'reinforcementCharacteristicStressLimitFactor' => 0.80,
    ];

    /** @var array{crackBondCoefficient: float, crackStrainDistributionCoefficient: float, crackSpacingCoefficient3: float, crackSpacingCoefficient4: float, shortTermKt: float, longTermKt: float, crackWidthLimitsByExposureClass: array<string, float>} */
    private const BEAM_CRACK_WIDTH_REQUIREMENTS = [
        'crackBondCoefficient' => 0.8,
        'crackStrainDistributionCoefficient' => 0.5,
        'crackSpacingCoefficient3' => 3.4,
        'crackSpacingCoefficient4' => 0.425,
        'shortTermKt' => 0.6,
        'longTermKt' => 0.4,
        'crackWidthLimitsByExposureClass' => ['XC1' => 0.4],
    ];

    /** @var array{baseRatioConstant: float, lowReinforcementCoefficient: float, lowReinforcementAdditionalCoefficient: float, highReinforcementCompressionCoefficient: float, referenceReinforcementRatioFactor: float, referenceSteelStrength: float, structuralFactorsBySupportSystem: array<string, float>} */
    private const BEAM_DEFLECTION_REQUIREMENTS = [
        'baseRatioConstant' => 11.0,
        'lowReinforcementCoefficient' => 1.5,
        'lowReinforcementAdditionalCoefficient' => 3.2,
        'highReinforcementCompressionCoefficient' => 1 / 12,
        'referenceReinforcementRatioFactor' => 0.001,
        'referenceSteelStrength' => 500.0,
        // EC2 §7.4.2, tableau 7.4N : K est propre au système structural.
        'structuralFactorsBySupportSystem' => ['SIMPLY_SUPPORTED' => 1.0, 'CANTILEVER' => 0.4],
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
            coverRequirements: CoverRequirements::frenchSupported(),
            beamLongitudinalReinforcementRequirements: new BeamLongitudinalReinforcementRequirements(
                ...self::BEAM_LONGITUDINAL_REINFORCEMENT_REQUIREMENTS,
            ),
            beamConcreteShearResistanceRequirements: new BeamConcreteShearResistanceRequirements(
                ...self::BEAM_CONCRETE_SHEAR_RESISTANCE_REQUIREMENTS,
            ),
            beamCrackWidthRequirements: new BeamCrackWidthRequirements(...self::BEAM_CRACK_WIDTH_REQUIREMENTS),
            beamDeflectionRequirements: new BeamDeflectionRequirements(...self::BEAM_DEFLECTION_REQUIREMENTS),
            beamServiceStressRequirements: new BeamServiceStressRequirements(...self::BEAM_SERVICE_STRESS_REQUIREMENTS),
            reinforcementSpacingRequirements: new ReinforcementSpacingRequirements(
                ...self::REINFORCEMENT_SPACING_REQUIREMENTS,
            ),
            slabReinforcementRequirements: SlabReinforcementRequirements::frenchSupported(),
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
