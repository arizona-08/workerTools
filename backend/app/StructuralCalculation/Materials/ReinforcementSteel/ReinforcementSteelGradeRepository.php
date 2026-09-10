<?php

namespace App\StructuralCalculation\Materials\ReinforcementSteel;

/**
 * Référentiel des propriétés intrinsèques des armatures passives du MVP.
 *
 * B500B reprend la classe B de l'annexe C de NF EN 1992-1-1:2005. Es est la
 * valeur de référence de 200 GPa admise par 3.2.7(2), stockée en MPa. Aucun
 * coefficient partiel ni aucune résistance de calcul ne relève du matériau.
 */
final class ReinforcementSteelGradeRepository
{
    /**
     * @var array<string, array{fyk: float, es: float, ductilityClass: SteelDuctilityClass}>
     */
    private const PROPERTIES_BY_GRADE = [
        'B500B' => [
            'fyk' => 500.0,
            'es' => 200000.0,
            'ductilityClass' => SteelDuctilityClass::B,
        ],
    ];

    /**
     * @return list<ReinforcementSteelProperties>
     */
    public function all(): array
    {
        return array_map(
            fn (ReinforcementSteelGrade $grade): ReinforcementSteelProperties => $this->get($grade),
            ReinforcementSteelGrade::cases(),
        );
    }

    public function find(string $identifier): ?ReinforcementSteelProperties
    {
        $grade = ReinforcementSteelGrade::tryFrom($identifier);

        return $grade === null ? null : $this->get($grade);
    }

    public function get(ReinforcementSteelGrade $grade): ReinforcementSteelProperties
    {
        $properties = self::PROPERTIES_BY_GRADE[$grade->value];

        return new ReinforcementSteelProperties(
            grade: $grade,
            fyk: $properties['fyk'],
            es: $properties['es'],
            ductilityClass: $properties['ductilityClass'],
        );
    }
}
