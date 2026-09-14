<?php

namespace App\StructuralCalculation\Slabs;

/** Sollicitations maximales d'une combinaison sur la bande de dalle unitaire. */
final readonly class SlabStripInternalForce
{
    public const MOMENT_UNIT = 'kN·m';

    public const SHEAR_UNIT = 'kN';

    public const MOMENT_FORMULA = 'Mmax = w × L² / 8';

    public const SHEAR_FORMULA = 'Vmax = w × L / 2';

    public function __construct(
        public SlabStripLinearLoad $linearLoad,
        public float $effectiveSpan,
        public float $maximumMoment,
        public float $maximumShear,
        public string $momentName,
        public string $momentSubstitution,
        public string $shearName,
        public string $shearSubstitution,
    ) {}
}
