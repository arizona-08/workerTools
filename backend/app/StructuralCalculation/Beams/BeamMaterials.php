<?php

namespace App\StructuralCalculation\Beams;

use App\StructuralCalculation\Materials\Concrete\ConcreteStrengthClass;
use App\StructuralCalculation\Materials\Exposure\ExposureClassCode;
use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementSteelGrade;

/** Références matériaux seulement : aucune propriété mécanique n'est transportée. */
final readonly class BeamMaterials
{
    /** @param non-empty-list<ExposureClassCode> $exposureClasses */
    public function __construct(
        public ConcreteStrengthClass $concreteClass,
        public ReinforcementSteelGrade $steelGrade,
        public array $exposureClasses,
    ) {}
}
