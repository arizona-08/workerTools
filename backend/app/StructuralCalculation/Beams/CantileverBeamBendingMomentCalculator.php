<?php

namespace App\StructuralCalculation\Beams;

use App\StructuralCalculation\Eurocode\Profiles\FundamentalUltimateCombinationExpression;
use App\StructuralCalculation\Eurocode\Profiles\ServiceabilityCombinationExpression;
use App\StructuralCalculation\Units\LengthConverter;

/** Analyse du moment négatif M = -wL²/2 à l'encastrement d'une console sous UDL. */
final readonly class CantileverBeamBendingMomentCalculator
{
    /** Coefficient statique, sans caractère normatif. */
    public const CANTILEVER_UNIFORMLY_DISTRIBUTED_MAX_MOMENT_COEFFICIENT = 1 / 2;

    public const MAXIMUM_MOMENT_POSITION_FACTOR = 0.0;

    public const FORMULA = 'Menc = -w × L² / 2';

    public function __construct(private LengthConverter $lengthConverter) {}

    public function calculate(
        BeamCalculationConfiguration $configuration,
        BeamGeometry $geometry,
        BeamUltimateCombinationResult $ultimateCombination,
        BeamServiceabilityCombinationsResult $serviceabilityCombinations,
    ): BeamBendingMomentResult {
        $this->ensureSupportedConfiguration($configuration);
        $this->ensurePositiveFinite($geometry->effectiveSpan, BeamBendingMomentRejectionReason::INVALID_EFFECTIVE_SPAN);

        $effectiveSpan = $this->lengthConverter->millimetresToMetres($geometry->effectiveSpan);
        $this->ensureNonNegativeFinite($ultimateCombination->designLineLoad, BeamBendingMomentRejectionReason::INVALID_ULTIMATE_LINE_LOAD);
        $this->ensureNonNegativeFinite($serviceabilityCombinations->characteristic->resultingLineLoad, BeamBendingMomentRejectionReason::INVALID_CHARACTERISTIC_SERVICEABILITY_LINE_LOAD);
        $this->ensureNonNegativeFinite($serviceabilityCombinations->frequent->resultingLineLoad, BeamBendingMomentRejectionReason::INVALID_FREQUENT_SERVICEABILITY_LINE_LOAD);
        $this->ensureNonNegativeFinite($serviceabilityCombinations->quasiPermanent->resultingLineLoad, BeamBendingMomentRejectionReason::INVALID_QUASI_PERMANENT_SERVICEABILITY_LINE_LOAD);

        return new BeamBendingMomentResult(
            effectiveSpan: $effectiveSpan,
            supportSystem: $configuration->supportSystem,
            loadModel: $configuration->loadModel,
            momentCoefficient: self::CANTILEVER_UNIFORMLY_DISTRIBUTED_MAX_MOMENT_COEFFICIENT,
            maximumMomentPosition: $effectiveSpan * self::MAXIMUM_MOMENT_POSITION_FACTOR,
            ultimate: $this->moment($ultimateCombination->designLineLoad, $effectiveSpan, $ultimateCombination->expressionReference),
            characteristic: $this->moment($serviceabilityCombinations->characteristic->resultingLineLoad, $effectiveSpan, $serviceabilityCombinations->characteristic->expressionReference),
            frequent: $this->moment($serviceabilityCombinations->frequent->resultingLineLoad, $effectiveSpan, $serviceabilityCombinations->frequent->expressionReference),
            quasiPermanent: $this->moment($serviceabilityCombinations->quasiPermanent->resultingLineLoad, $effectiveSpan, $serviceabilityCombinations->quasiPermanent->expressionReference),
        );
    }

    private function moment(float $lineLoad, float $effectiveSpan, FundamentalUltimateCombinationExpression|ServiceabilityCombinationExpression $combinationReference): BeamBendingMoment
    {
        return new BeamBendingMoment($lineLoad, -$lineLoad * $effectiveSpan ** 2 * self::CANTILEVER_UNIFORMLY_DISTRIBUTED_MAX_MOMENT_COEFFICIENT, $combinationReference, self::FORMULA);
    }

    private function ensureSupportedConfiguration(BeamCalculationConfiguration $configuration): void
    {
        if ($configuration->supportSystem !== BeamSupportSystem::CANTILEVER) {
            throw new BeamBendingMomentException(BeamBendingMomentRejectionReason::UNSUPPORTED_SUPPORT_SYSTEM);
        }
        if ($configuration->loadModel !== BeamLoadModel::UNIFORMLY_DISTRIBUTED) {
            throw new BeamBendingMomentException(BeamBendingMomentRejectionReason::UNSUPPORTED_LOAD_MODEL);
        }
    }

    private function ensurePositiveFinite(float $value, BeamBendingMomentRejectionReason $reason): void
    {
        if (! is_finite($value) || $value <= 0) {
            throw new BeamBendingMomentException($reason);
        }
    }

    private function ensureNonNegativeFinite(float $value, BeamBendingMomentRejectionReason $reason): void
    {
        if (! is_finite($value) || $value < 0) {
            throw new BeamBendingMomentException($reason);
        }
    }
}
