<?php

namespace App\StructuralCalculation\Materials\Concrete;

/**
 * Référentiel des propriétés intrinsèques du béton de masse volumique normale.
 *
 * Valeurs de NF EN 1992-1-1:2005, tableau 3.1. Les valeurs de résistance sont
 * exprimées en MPa ; Ecm est stocké en MPa pour une convention interne unique.
 */
final class ConcreteClassRepository
{
    /**
     * @var array<string, array{fck: float, fcm: float, fctm: float, ecm: float}>
     */
    private const PROPERTIES_BY_CLASS = [
        'C20/25' => ['fck' => 20.0, 'fcm' => 28.0, 'fctm' => 2.2, 'ecm' => 30000.0],
        'C25/30' => ['fck' => 25.0, 'fcm' => 33.0, 'fctm' => 2.6, 'ecm' => 31000.0],
        'C30/37' => ['fck' => 30.0, 'fcm' => 38.0, 'fctm' => 2.9, 'ecm' => 33000.0],
    ];

    /**
     * @return list<ConcreteProperties>
     */
    public function all(): array
    {
        return array_map(
            fn (ConcreteStrengthClass $strengthClass): ConcreteProperties => $this->get($strengthClass),
            ConcreteStrengthClass::cases(),
        );
    }

    public function find(string $identifier): ?ConcreteProperties
    {
        $strengthClass = ConcreteStrengthClass::tryFrom($identifier);

        return $strengthClass === null ? null : $this->get($strengthClass);
    }

    public function get(ConcreteStrengthClass $strengthClass): ConcreteProperties
    {
        $properties = self::PROPERTIES_BY_CLASS[$strengthClass->value];

        return new ConcreteProperties(
            strengthClass: $strengthClass,
            fck: $properties['fck'],
            fcm: $properties['fcm'],
            fctm: $properties['fctm'],
            ecm: $properties['ecm'],
        );
    }
}
