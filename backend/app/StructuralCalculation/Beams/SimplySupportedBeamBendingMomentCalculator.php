<?php

namespace App\StructuralCalculation\Beams;

use App\StructuralCalculation\Eurocode\Profiles\FundamentalUltimateCombinationExpression;
use App\StructuralCalculation\Eurocode\Profiles\ServiceabilityCombinationExpression;
use App\StructuralCalculation\Units\LengthConverter;

/** Analyse M = wL²/8, strictement limitée à la poutre simplement appuyée sous charge uniforme. */
final readonly class SimplySupportedBeamBendingMomentCalculator
{
    /** Coefficient analytique du moment maximal en travée, pas une constante Eurocode. */
    public const SIMPLY_SUPPORTED_UNIFORMLY_DISTRIBUTED_MAX_MOMENT_COEFFICIENT = 1 / 8;

    public const MAXIMUM_MOMENT_POSITION_FACTOR = 1 / 2;

    public const FORMULA = 'Mmax = w × l_eff² / 8';

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
            momentCoefficient: self::SIMPLY_SUPPORTED_UNIFORMLY_DISTRIBUTED_MAX_MOMENT_COEFFICIENT,
            maximumMomentPosition: $effectiveSpan * self::MAXIMUM_MOMENT_POSITION_FACTOR,
            ultimate: $this->moment($ultimateCombination->designLineLoad, $effectiveSpan, $ultimateCombination->expressionReference),
            characteristic: $this->moment(
                $serviceabilityCombinations->characteristic->resultingLineLoad,
                $effectiveSpan,
                $serviceabilityCombinations->characteristic->expressionReference,
            ),
            frequent: $this->moment(
                $serviceabilityCombinations->frequent->resultingLineLoad,
                $effectiveSpan,
                $serviceabilityCombinations->frequent->expressionReference,
            ),
            quasiPermanent: $this->moment(
                $serviceabilityCombinations->quasiPermanent->resultingLineLoad,
                $effectiveSpan,
                $serviceabilityCombinations->quasiPermanent->expressionReference,
            ),
        );
    }

    private function moment(
        float $lineLoad,
        float $effectiveSpan,
        FundamentalUltimateCombinationExpression|ServiceabilityCombinationExpression $combinationReference,
    ): BeamBendingMoment {
        return new BeamBendingMoment(
            lineLoad: $lineLoad,
            maximumMoment: $lineLoad * $effectiveSpan ** 2 * self::SIMPLY_SUPPORTED_UNIFORMLY_DISTRIBUTED_MAX_MOMENT_COEFFICIENT,
            combinationReference: $combinationReference,
            formula: self::FORMULA,
        );
    }

    private function ensureSupportedConfiguration(BeamCalculationConfiguration $configuration): void
    {
        if ($configuration->supportSystem !== BeamSupportSystem::SIMPLY_SUPPORTED) {
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
