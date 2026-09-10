<?php

namespace App\StructuralCalculation\Eurocode\Cover;

use App\StructuralCalculation\Materials\Exposure\ExposureClassCode;

final readonly class DurabilityExposureResult
{
    public function __construct(
        public ExposureClassCode $exposureClass,
        public StructuralClassResult $structuralClass,
        public float $minimumDurabilityCover,
    ) {}
}
