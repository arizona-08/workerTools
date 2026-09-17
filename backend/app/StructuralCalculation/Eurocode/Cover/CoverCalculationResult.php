<?php

namespace App\StructuralCalculation\Eurocode\Cover;

use App\StructuralCalculation\Materials\Exposure\ExposureClassCode;

final readonly class CoverCalculationResult
{
    /** @param list<StructuralClassModifier> $structuralClassModifiers @param list<DurabilityExposureResult> $exposureResults @param list<string> $warnings */
    public function __construct(
        public CoverMode $coverMode,
        public ?StructuralClass $initialStructuralClass,
        public array $structuralClassModifiers,
        public ?StructuralClass $finalStructuralClass,
        public array $exposureResults,
        public ?ExposureClassCode $governingExposureClass,
        public ?float $cMinBond,
        public ?float $cMinDurability,
        public ?float $deltaCDurGamma,
        public ?float $deltaCDurSt,
        public ?float $deltaCDurAdd,
        public ?float $correctedCMinDurability,
        public ?float $minimumAbsoluteCover,
        public float $cMin,
        public ?CoverGoverningCriterion $governingCriterion,
        public ?float $deltaCDev,
        public float $cNom,
        public string $unit,
        public array $warnings,
    ) {}
}
