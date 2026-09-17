<?php

use App\StructuralCalculation\Beams\BeamBendingMomentException;
use App\StructuralCalculation\Beams\BeamBendingMomentRejectionReason;
use App\StructuralCalculation\Beams\BeamCalculationConfiguration;
use App\StructuralCalculation\Beams\BeamGeometry;
use App\StructuralCalculation\Beams\BeamLoadModel;
use App\StructuralCalculation\Beams\BeamServiceabilityCombination;
use App\StructuralCalculation\Beams\BeamServiceabilityCombinationsResult;
use App\StructuralCalculation\Beams\BeamShearForceException;
use App\StructuralCalculation\Beams\BeamShearForceRejectionReason;
use App\StructuralCalculation\Beams\BeamSubmodule;
use App\StructuralCalculation\Beams\BeamSupportSystem;
use App\StructuralCalculation\Beams\BeamUltimateCombinationResult;
use App\StructuralCalculation\Beams\CantileverBeamBendingMomentCalculator;
use App\StructuralCalculation\Beams\CantileverBeamShearForceCalculator;
use App\StructuralCalculation\Eurocode\Profiles\FundamentalUltimateCombinationExpression;
use App\StructuralCalculation\Eurocode\Profiles\ServiceabilityCombinationExpression;

function cantileverConfiguration(BeamLoadModel $loadModel = BeamLoadModel::UNIFORMLY_DISTRIBUTED): BeamCalculationConfiguration
{
    $reference = BeamCalculationConfiguration::supported();

    return new BeamCalculationConfiguration(
        $reference->calculationMode,
        $reference->elementType,
        $reference->materialType,
        $reference->sectionType,
        BeamSupportSystem::CANTILEVER,
        $loadModel,
        $reference->designCodeProfile,
        $reference->designSituation,
        BeamSubmodule::BEAM_CANTILEVER_RECTANGULAR,
    );
}

function cantileverUltimate(float $lineLoad): BeamUltimateCombinationResult
{
    return new BeamUltimateCombinationResult(0, 1, 0, 0, 1, 0, $lineLoad, FundamentalUltimateCombinationExpression::EN1990_6_10);
}

function cantileverServiceability(float $characteristic, float $frequent, float $quasiPermanent): BeamServiceabilityCombinationsResult
{
    return new BeamServiceabilityCombinationsResult(
        new BeamServiceabilityCombination(0, 0, 1, 0, $characteristic, $characteristic, ServiceabilityCombinationExpression::EN1990_6_14, 'wSlsCharacteristic = Gk_total + Qk'),
        new BeamServiceabilityCombination(0, 0, 0.5, 0, $frequent, $frequent, ServiceabilityCombinationExpression::EN1990_6_15, 'wSlsFrequent = Gk_total + ψ1 × Qk'),
        new BeamServiceabilityCombination(0, 0, 0.3, 0, $quasiPermanent, $quasiPermanent, ServiceabilityCombinationExpression::EN1990_6_16, 'wSlsQuasiPermanent = Gk_total + ψ2 × Qk'),
    );
}

it('calculates independent ultimate and serviceability cantilever moments at the fixed end', function () {
    $result = app(CantileverBeamBendingMomentCalculator::class)->calculate(
        cantileverConfiguration(),
        new BeamGeometry(4000, 300, 600),
        cantileverUltimate(10),
        cantileverServiceability(8, 7, 6),
    );

    expect($result->effectiveSpan)->toBe(4.0)
        ->and($result->supportSystem)->toBe(BeamSupportSystem::CANTILEVER)
        ->and($result->momentCoefficient)->toBe(0.5)
        ->and($result->maximumMomentPosition)->toBe(0.0)
        ->and($result->ultimate->maximumMoment)->toBe(-80.0)
        ->and($result->ultimate->magnitude())->toBe(80.0)
        ->and($result->characteristic->maximumMoment)->toBe(-64.0)
        ->and($result->frequent->maximumMoment)->toBe(-56.0)
        ->and($result->quasiPermanent->maximumMoment)->toBe(-48.0)
        ->and($result->ultimate->formula)->toBe('Menc = -w × L² / 2');
});

it('calculates independent ultimate and serviceability cantilever shears at the fixed end', function () {
    $result = app(CantileverBeamShearForceCalculator::class)->calculate(
        cantileverConfiguration(),
        new BeamGeometry(4000, 300, 600),
        cantileverUltimate(10),
        cantileverServiceability(8, 7, 6),
    );

    expect($result->effectiveSpan)->toBe(4.0)
        ->and($result->supportSystem)->toBe(BeamSupportSystem::CANTILEVER)
        ->and($result->shearCoefficient)->toBe(1.0)
        ->and($result->ultimate->maximumAbsoluteShear)->toBe(40.0)
        ->and($result->ultimate->leftSupportShear)->toBe(40.0)
        ->and($result->ultimate->rightSupportShear)->toBe(0.0)
        ->and($result->characteristic->maximumAbsoluteShear)->toBe(32.0)
        ->and($result->frequent->maximumAbsoluteShear)->toBe(28.0)
        ->and($result->quasiPermanent->maximumAbsoluteShear)->toBe(24.0)
        ->and($result->ultimate->formula)->toBe('Venc = w × L');
});

it('keeps zero combined loads as zero cantilever efforts', function () {
    $moments = app(CantileverBeamBendingMomentCalculator::class)->calculate(cantileverConfiguration(), new BeamGeometry(4000, 300, 600), cantileverUltimate(0), cantileverServiceability(0, 0, 0));
    $shears = app(CantileverBeamShearForceCalculator::class)->calculate(cantileverConfiguration(), new BeamGeometry(4000, 300, 600), cantileverUltimate(0), cantileverServiceability(0, 0, 0));

    expect($moments->ultimate->maximumMoment)->toBe(0.0)
        ->and($moments->characteristic->maximumMoment)->toBe(0.0)
        ->and($shears->ultimate->maximumAbsoluteShear)->toBe(0.0)
        ->and($shears->quasiPermanent->maximumAbsoluteShear)->toBe(0.0);
});

it('protects the cantilever analysis against invalid span and load model inputs', function () {
    expect(fn () => app(CantileverBeamBendingMomentCalculator::class)->calculate(cantileverConfiguration(), new BeamGeometry(0, 300, 600), cantileverUltimate(10), cantileverServiceability(8, 7, 6)))
        ->toThrow(BeamBendingMomentException::class, BeamBendingMomentRejectionReason::INVALID_EFFECTIVE_SPAN->value);
    expect(fn () => app(CantileverBeamShearForceCalculator::class)->calculate(cantileverConfiguration(BeamLoadModel::POINT_LOAD), new BeamGeometry(4000, 300, 600), cantileverUltimate(10), cantileverServiceability(8, 7, 6)))
        ->toThrow(BeamShearForceException::class, BeamShearForceRejectionReason::UNSUPPORTED_LOAD_MODEL->value);
});
