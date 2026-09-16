<?php

namespace App\StructuralCalculation\Slabs;

use App\StructuralCalculation\Statics\SimplySupportedUniformlyDistributedLoadCalculator;
use App\StructuralCalculation\Units\LengthConverter;

/** Analyse de la bande Dalle V1, limitée à une travée simplement appuyée sous charge uniforme. */
final readonly class SlabStripAnalysisCalculator
{
    public function __construct(
        private SlabStripLinearLoadsCalculator $linearLoadsCalculator,
        private LengthConverter $lengthConverter,
        private SimplySupportedUniformlyDistributedLoadCalculator $staticCalculator,
    ) {}

    public function calculate(
        SlabCalculationConfiguration $configuration,
        SlabGeometry $geometry,
        SlabActionCombinations $combinations,
    ): SlabStripAnalysis {
        $this->ensureSupportedConfiguration($configuration);
        $this->ensurePositiveFinite($geometry->effectiveSpan);

        $effectiveSpan = $this->lengthConverter->millimetresToMetres($geometry->effectiveSpan);
        $linearLoads = $this->linearLoadsCalculator->calculate($geometry, $combinations);

        return new SlabStripAnalysis(
            linearLoads: $linearLoads,
            internalForces: new SlabStripInternalForces(
                uls: $this->internalForce($linearLoads->uls, $effectiveSpan, 'ELU'),
                slsCharacteristic: $this->internalForce($linearLoads->slsCharacteristic, $effectiveSpan, 'ELS caractéristique'),
                slsFrequent: $this->internalForce($linearLoads->slsFrequent, $effectiveSpan, 'ELS fréquente'),
                slsQuasiPermanent: $this->internalForce($linearLoads->slsQuasiPermanent, $effectiveSpan, 'ELS quasi-permanente'),
            ),
        );
    }

    private function internalForce(SlabStripLinearLoad $linearLoad, float $effectiveSpan, string $combinationName): SlabStripInternalForce
    {
        return new SlabStripInternalForce(
            linearLoad: $linearLoad,
            effectiveSpan: $effectiveSpan,
            maximumMoment: $this->staticCalculator->maximumMoment($linearLoad->lineLoad, $effectiveSpan),
            maximumShear: $this->staticCalculator->maximumShear($linearLoad->lineLoad, $effectiveSpan),
            momentName: sprintf('Moment fléchissant %s maximal', $combinationName),
            momentSubstitution: sprintf('Mmax = %s × %s² / 8', $linearLoad->lineLoad, $effectiveSpan),
            shearName: sprintf('Effort tranchant %s maximal', $combinationName),
            shearSubstitution: sprintf('Vmax = %s × %s / 2', $linearLoad->lineLoad, $effectiveSpan),
        );
    }

    private function ensureSupportedConfiguration(SlabCalculationConfiguration $configuration): void
    {
        if ($configuration->structuralSystem !== SlabStructuralSystem::SINGLE_SPAN_SIMPLY_SUPPORTED_ON_OPPOSITE_SIDES) {
            throw new SlabStripAnalysisException(SlabStripAnalysisRejectionReason::UNSUPPORTED_STRUCTURAL_SYSTEM);
        }

        if ($configuration->loadModel !== SlabLoadModel::VERTICAL_UNIFORMLY_DISTRIBUTED) {
            throw new SlabStripAnalysisException(SlabStripAnalysisRejectionReason::UNSUPPORTED_LOAD_MODEL);
        }
    }

    private function ensurePositiveFinite(float $value): void
    {
        if (! is_finite($value) || $value <= 0) {
            throw new SlabStripAnalysisException(SlabStripAnalysisRejectionReason::INVALID_EFFECTIVE_SPAN);
        }
    }
}
