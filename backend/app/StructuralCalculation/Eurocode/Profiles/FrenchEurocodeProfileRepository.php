<?php

namespace App\StructuralCalculation\Eurocode\Profiles;

/**
 * Profil MVP : NF EN 1992-1-1:2005 et Annexes Nationales françaises associées.
 *
 * Les facteurs d'actions s'appliquent uniquement au cas bâtiment, ELU
 * fondamental persistant/transitoire. Les autres situations et catégories
 * d'actions doivent être ajoutées explicitement dans ce profil.
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

    public function get(): DesignCodeProfile
    {
        return new DesignCodeProfile(
            identifier: DesignCodeProfileIdentifier::NF_EN_1992_1_1_2005_FR,
            materialSafetyFactors: new MaterialSafetyFactors(...self::MATERIAL_SAFETY_FACTORS),
            actionSafetyFactors: new ActionSafetyFactors(...self::ACTION_SAFETY_FACTORS),
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
