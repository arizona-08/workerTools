<?php

namespace App\StructuralCalculation\Beams;

use App\StructuralCalculation\Eurocode\Profiles\FundamentalUltimateCombinationExpression;
use App\StructuralCalculation\Eurocode\Profiles\ServiceabilityCombinationExpression;
use App\StructuralCalculation\Statics\SimplySupportedUniformlyDistributedLoadCalculator;
use App\StructuralCalculation\Units\LengthConverter;

/** Analyse V = wL/2, strictement limitée à la poutre simplement appuyée sous charge uniforme. */
final readonly class SimplySupportedBeamShearForceCalculator
{
    /** Coefficient analytique du cisaillement maximal aux appuis, pas une constante Eurocode. */
    public const SIMPLY_SUPPORTED_UNIFORMLY_DISTRIBUTED_MAX_SHEAR_COEFFICIENT = 1 / 2;

    public const FORMULA = 'Vmax = w × l_eff / 2';

    public function __construct(
        private LengthConverter $lengthConverter,
        private SimplySupportedUniformlyDistributedLoadCalculator $staticCalculator,
    ) {}

    public function calculate(
        BeamCalculationConfiguration $configuration,
        BeamGeometry $geometry,
        BeamUltimateCombinationResult $ultimateCombination,
        BeamServiceabilityCombinationsResult $serviceabilityCombinations,
    ): BeamShearForceResult {
        $this->ensureSupportedConfiguration($configuration);
        $this->ensurePositiveFinite($geometry->effectiveSpan, BeamShearForceRejectionReason::INVALID_EFFECTIVE_SPAN);

        $effectiveSpan = $this->lengthConverter->millimetresToMetres($geometry->effectiveSpan);
        $this->ensureNonNegativeFinite($ultimateCombination->designLineLoad, BeamShearForceRejectionReason::INVALID_ULTIMATE_LINE_LOAD);
        $this->ensureNonNegativeFinite($serviceabilityCombinations->characteristic->resultingLineLoad, BeamShearForceRejectionReason::INVALID_CHARACTERISTIC_SERVICEABILITY_LINE_LOAD);
        $this->ensureNonNegativeFinite($serviceabilityCombinations->frequent->resultingLineLoad, BeamShearForceRejectionReason::INVALID_FREQUENT_SERVICEABILITY_LINE_LOAD);
        $this->ensureNonNegativeFinite($serviceabilityCombinations->quasiPermanent->resultingLineLoad, BeamShearForceRejectionReason::INVALID_QUASI_PERMANENT_SERVICEABILITY_LINE_LOAD);

        return new BeamShearForceResult(
            effectiveSpan: $effectiveSpan,
            supportSystem: $configuration->supportSystem,
            loadModel: $configuration->loadModel,
            shearCoefficient: self::SIMPLY_SUPPORTED_UNIFORMLY_DISTRIBUTED_MAX_SHEAR_COEFFICIENT,
            ultimate: $this->shear($ultimateCombination->designLineLoad, $effectiveSpan, $ultimateCombination->expressionReference),
            characteristic: $this->shear(
                $serviceabilityCombinations->characteristic->resultingLineLoad,
                $effectiveSpan,
                $serviceabilityCombinations->characteristic->expressionReference,
            ),
            frequent: $this->shear(
                $serviceabilityCombinations->frequent->resultingLineLoad,
                $effectiveSpan,
                $serviceabilityCombinations->frequent->expressionReference,
            ),
            quasiPermanent: $this->shear(
                $serviceabilityCombinations->quasiPermanent->resultingLineLoad,
                $effectiveSpan,
                $serviceabilityCombinations->quasiPermanent->expressionReference,
            ),
        );
    }

    private function shear(
        float $lineLoad,
        float $effectiveSpan,
        FundamentalUltimateCombinationExpression|ServiceabilityCombinationExpression $combinationReference,
    ): BeamShearForce {
        $maximumAbsoluteShear = $this->staticCalculator->maximumShear($lineLoad, $effectiveSpan);

        return new BeamShearForce(
            lineLoad: $lineLoad,
            maximumAbsoluteShear: $maximumAbsoluteShear,
            leftSupportShear: $maximumAbsoluteShear,
            rightSupportShear: -$maximumAbsoluteShear,
            combinationReference: $combinationReference,
            formula: self::FORMULA,
        );
    }

    private function ensureSupportedConfiguration(BeamCalculationConfiguration $configuration): void
    {
        if ($configuration->supportSystem !== BeamSupportSystem::SIMPLY_SUPPORTED) {
            throw new BeamShearForceException(BeamShearForceRejectionReason::UNSUPPORTED_SUPPORT_SYSTEM);
        }

        if ($configuration->loadModel !== BeamLoadModel::UNIFORMLY_DISTRIBUTED) {
            throw new BeamShearForceException(BeamShearForceRejectionReason::UNSUPPORTED_LOAD_MODEL);
        }
    }

    private function ensurePositiveFinite(float $value, BeamShearForceRejectionReason $reason): void
    {
        if (! is_finite($value) || $value <= 0) {
            throw new BeamShearForceException($reason);
        }
    }

    private function ensureNonNegativeFinite(float $value, BeamShearForceRejectionReason $reason): void
    {
        if (! is_finite($value) || $value < 0) {
            throw new BeamShearForceException($reason);
        }
    }
}
