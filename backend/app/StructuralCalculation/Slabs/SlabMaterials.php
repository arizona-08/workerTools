<?php

namespace App\StructuralCalculation\Slabs;

use App\StructuralCalculation\Materials\Concrete\ConcreteStrengthClass;
use App\StructuralCalculation\Materials\Exposure\ExposureClassCode;
use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementSteelGrade;

/** Références vers les matériaux communs, sans propriété mécanique ni calcul. */
final readonly class SlabMaterials
{
    public function __construct(
        public ConcreteStrengthClass $concreteClass,
        public ReinforcementSteelGrade $steelGrade,
        public ExposureClassCode $exposureClass,
    ) {}
}
