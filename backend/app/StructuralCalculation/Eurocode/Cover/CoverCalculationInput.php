<?php

namespace App\StructuralCalculation\Eurocode\Cover;

use App\StructuralCalculation\Materials\Concrete\ConcreteStrengthClass;
use App\StructuralCalculation\Materials\Exposure\ExposureClassCode;

final readonly class CoverCalculationInput
{
    /** @param list<ExposureClassCode> $exposureClasses */
    public function __construct(
        public CoverMode $coverMode,
        public array $exposureClasses = [],
        public ?ConcreteStrengthClass $concreteClass = null,
        public ?int $designWorkingLifeYears = null,
        public ?float $reinforcementDiameter = null,
        public bool $compactCover = false,
        public ?float $manualNominalCover = null,
        public CoverCalculationScope $scope = new CoverCalculationScope,
    ) {}
}
