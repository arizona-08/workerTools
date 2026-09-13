<?php

namespace App\StructuralCalculation\Eurocode\Beams;

use App\StructuralCalculation\Beams\BeamCrackLoadDuration;
use App\StructuralCalculation\Materials\Exposure\ExposureClassCode;

/** Paramètres nationaux de fissuration EC2 §7.3.4, pour les cas MVP validés. */
final readonly class BeamCrackWidthRequirements
{
    /** @param array<string, float> $crackWidthLimitsByExposureClass */
    public function __construct(
        public float $crackBondCoefficient,
        public float $crackStrainDistributionCoefficient,
        public float $crackSpacingCoefficient3,
        public float $crackSpacingCoefficient4,
        public float $shortTermKt,
        public float $longTermKt,
        private array $crackWidthLimitsByExposureClass,
    ) {}

    public function crackWidthLimitFor(ExposureClassCode $exposureClass): ?float
    {
        return $this->crackWidthLimitsByExposureClass[$exposureClass->value] ?? null;
    }

    public function ktFor(BeamCrackLoadDuration $duration): float
    {
        return match ($duration) {
            BeamCrackLoadDuration::SHORT_TERM => $this->shortTermKt,
            BeamCrackLoadDuration::LONG_TERM => $this->longTermKt,
        };
    }
}
