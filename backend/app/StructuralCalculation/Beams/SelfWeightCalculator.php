<?php

namespace App\StructuralCalculation\Beams;

use App\StructuralCalculation\Eurocode\Actions\ReinforcedConcreteUnitWeight;
use App\StructuralCalculation\Units\LengthConverter;

/** Transforme la section rectangulaire en charge linéaire de poids propre. */
final readonly class SelfWeightCalculator
{
    public function __construct(private LengthConverter $lengthConverter) {}

    public function calculate(
        BeamGeometry $geometry,
        bool $includeSelfWeight,
        ReinforcedConcreteUnitWeight $unitWeight,
    ): SelfWeightResult {
        $this->ensurePositiveFinite($geometry->width, SelfWeightCalculationRejectionReason::INVALID_WIDTH);
        $this->ensurePositiveFinite($geometry->height, SelfWeightCalculationRejectionReason::INVALID_HEIGHT);
        $this->ensurePositiveFinite($unitWeight->value, SelfWeightCalculationRejectionReason::INVALID_REINFORCED_CONCRETE_UNIT_WEIGHT);

        $sectionArea = $this->lengthConverter->millimetresToMetres($geometry->width)
            * $this->lengthConverter->millimetresToMetres($geometry->height);
        $characteristicLineLoad = $includeSelfWeight ? $sectionArea * $unitWeight->value : 0.0;

        return new SelfWeightResult(
            included: $includeSelfWeight,
            widthMillimetres: $geometry->width,
            heightMillimetres: $geometry->height,
            sectionArea: $sectionArea,
            unitWeight: $unitWeight,
            characteristicLineLoad: $characteristicLineLoad,
        );
    }

    private function ensurePositiveFinite(float $value, SelfWeightCalculationRejectionReason $reason): void
    {
        if (! is_finite($value) || $value <= 0) {
            throw new SelfWeightCalculationException($reason);
        }
    }
}
