<?php

namespace App\StructuralCalculation\Eurocode\Cover;

use App\StructuralCalculation\Materials\Concrete\ConcreteStrengthClass;
use App\StructuralCalculation\Materials\Exposure\ExposureClassCode;

/**
 * Paramètres d'enrobage du profil français MVP.
 *
 * Les valeurs nationales sont centralisées ici ; aucun calculateur ne les
 * contient en dur. Les expositions XF et XA n'ont volontairement pas de
 * valeur : elles nécessitent respectivement une classe de référence et une
 * caractérisation complémentaire hors du périmètre MVP.
 */
final readonly class CoverRequirements
{
    /** @param array<string, array<string, float>> $minimumDurabilityCoverByStructuralClass */
    private function __construct(
        public StructuralClass $initialStructuralClass,
        public float $minimumAbsoluteCover,
        public float $deltaCDurGamma,
        public float $deltaCDurSt,
        public float $deltaCDurAdd,
        public float $defaultDeltaCDev,
        private array $minimumDurabilityCoverByStructuralClass,
    ) {}

    public static function frenchMvp(): self
    {
        return new self(
            initialStructuralClass: StructuralClass::S4,
            minimumAbsoluteCover: 10.0,
            deltaCDurGamma: 0.0,
            deltaCDurSt: 0.0,
            deltaCDurAdd: 0.0,
            defaultDeltaCDev: 10.0,
            minimumDurabilityCoverByStructuralClass: [
                'S1' => ['X0' => 10.0, 'XC1' => 10.0, 'XC2' => 10.0, 'XC3' => 10.0, 'XC4' => 15.0, 'XD1' => 20.0, 'XS1' => 20.0, 'XD2' => 25.0, 'XS2' => 25.0, 'XD3' => 30.0, 'XS3' => 30.0],
                'S2' => ['X0' => 10.0, 'XC1' => 10.0, 'XC2' => 15.0, 'XC3' => 15.0, 'XC4' => 20.0, 'XD1' => 25.0, 'XS1' => 25.0, 'XD2' => 30.0, 'XS2' => 30.0, 'XD3' => 35.0, 'XS3' => 35.0],
                'S3' => ['X0' => 10.0, 'XC1' => 10.0, 'XC2' => 20.0, 'XC3' => 20.0, 'XC4' => 25.0, 'XD1' => 30.0, 'XS1' => 30.0, 'XD2' => 35.0, 'XS2' => 35.0, 'XD3' => 40.0, 'XS3' => 40.0],
                'S4' => ['X0' => 10.0, 'XC1' => 15.0, 'XC2' => 25.0, 'XC3' => 25.0, 'XC4' => 30.0, 'XD1' => 35.0, 'XS1' => 35.0, 'XD2' => 40.0, 'XS2' => 40.0, 'XD3' => 45.0, 'XS3' => 45.0],
                'S5' => ['X0' => 15.0, 'XC1' => 20.0, 'XC2' => 30.0, 'XC3' => 30.0, 'XC4' => 35.0, 'XD1' => 40.0, 'XS1' => 40.0, 'XD2' => 45.0, 'XS2' => 45.0, 'XD3' => 50.0, 'XS3' => 50.0],
                'S6' => ['X0' => 20.0, 'XC1' => 25.0, 'XC2' => 35.0, 'XC3' => 35.0, 'XC4' => 40.0, 'XD1' => 45.0, 'XS1' => 45.0, 'XD2' => 50.0, 'XS2' => 50.0, 'XD3' => 55.0, 'XS3' => 55.0],
            ],
        );
    }

    public function minimumDurabilityCoverFor(StructuralClass $structuralClass, ExposureClassCode $exposureClass): ?float
    {
        return $this->minimumDurabilityCoverByStructuralClass[$structuralClass->label()][$exposureClass->value] ?? null;
    }

    public function workingLifeModifier(int $years): int
    {
        return match (true) {
            $years <= 25 => -1,
            $years === 100 => 2,
            default => 0,
        };
    }

    public function supportsDesignWorkingLife(int $years): bool
    {
        return in_array($years, [25, 50, 100], true);
    }

    public function compactCoverModifier(bool $compactCover): int
    {
        return $compactCover ? -1 : 0;
    }

    public function concreteStrengthModifier(ConcreteStrengthClass $concreteClass, ExposureClassCode $exposureClass): int
    {
        if ($concreteClass !== ConcreteStrengthClass::C30_37) {
            return 0;
        }

        return match ($exposureClass) {
            ExposureClassCode::X0,
            ExposureClassCode::XC1,
            ExposureClassCode::XC2,
            ExposureClassCode::XC3 => -1,
            default => 0,
        };
    }
}
