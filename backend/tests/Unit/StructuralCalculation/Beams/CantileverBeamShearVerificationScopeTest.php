<?php

use App\StructuralCalculation\Beams\BeamAnalysisException;
use App\StructuralCalculation\Beams\BeamCalculationConfiguration;
use App\StructuralCalculation\Beams\BeamCalculationMode;
use App\StructuralCalculation\Beams\BeamEffectiveDepthResult;
use App\StructuralCalculation\Beams\BeamGeometry;
use App\StructuralCalculation\Beams\BeamReinforcementPosition;
use App\StructuralCalculation\Beams\BeamReinforcementProposalCandidate;
use App\StructuralCalculation\Beams\BeamShearCriticalSectionLocation;
use App\StructuralCalculation\Beams\BeamShearForce;
use App\StructuralCalculation\Beams\BeamSubmodule;
use App\StructuralCalculation\Beams\BeamSupportSystem;
use App\StructuralCalculation\Beams\BeamTensionFace;
use App\StructuralCalculation\Beams\CantileverBeamShearVerificationScope;
use App\StructuralCalculation\Beams\LongitudinalBarDiameterSource;
use App\StructuralCalculation\Eurocode\Profiles\FundamentalUltimateCombinationExpression;

function cantileverShearScopeConfiguration(): BeamCalculationConfiguration
{
    $simple = BeamCalculationConfiguration::supported();

    return new BeamCalculationConfiguration(
        $simple->calculationMode,
        $simple->elementType,
        $simple->materialType,
        $simple->sectionType,
        BeamSupportSystem::CANTILEVER,
        $simple->loadModel,
        $simple->designCodeProfile,
        $simple->designSituation,
        BeamSubmodule::BEAM_CANTILEVER_RECTANGULAR,
    );
}

function cantileverShearScopeForce(float $maximumAbsoluteShear = 40): BeamShearForce
{
    return new BeamShearForce(10, $maximumAbsoluteShear, $maximumAbsoluteShear, 0, FundamentalUltimateCombinationExpression::EN1990_6_10, 'Venc = w × L');
}

function cantileverTopReinforcement(): BeamReinforcementProposalCandidate
{
    return new BeamReinforcementProposalCandidate(4, 16, M_PI * 16 ** 2 / 4, 4 * M_PI * 16 ** 2 / 4, 500, 304.247719, .62, BeamReinforcementPosition::TOP);
}

function cantileverShearScopeGeometry(): BeamGeometry
{
    return new BeamGeometry(4000, 300, 600);
}

function cantileverShearScopeEffectiveDepth(float $depth = 500): BeamEffectiveDepthResult
{
    return new BeamEffectiveDepthResult(BeamCalculationMode::DESIGN, 600, 20, 8, 16, LongitudinalBarDiameterSource::CONFIG, 36, $depth, BeamTensionFace::TOP);
}

it('uses the EC2 critical section at d and calculates its UDL shear without replacing the fixed-end VEd', function () {
    $result = app(CantileverBeamShearVerificationScope::class)->assess(
        cantileverShearScopeConfiguration(),
        cantileverShearScopeGeometry(),
        cantileverShearScopeForce(),
        cantileverShearScopeEffectiveDepth(),
        cantileverTopReinforcement(),
    );

    expect($result->fixedEndDesignShearForce->maximumAbsoluteShear)->toBe(40.0)
        ->and($result->criticalSectionLocation)->toBe(BeamShearCriticalSectionLocation::EFFECTIVE_DEPTH_FROM_FIXED_END)
        ->and($result->criticalSectionPosition)->toBe(500.0)
        ->and($result->criticalSectionDesignShearForce->maximumAbsoluteShear)->toBe(35.0)
        ->and($result->criticalSectionDesignShearForce->formula)->toBe('VEd(x) = wEd × (L - x), avec x = d')
        ->and($result->longitudinalReinforcementPosition)->toBe(BeamReinforcementPosition::TOP)
        ->and($result->longitudinalReinforcementArea)->toBe(4 * M_PI * 16 ** 2 / 4);
});

it('does not apply the cantilever critical-section limitation to another structural system', function () {
    expect(fn () => app(CantileverBeamShearVerificationScope::class)->assess(
        BeamCalculationConfiguration::supported(),
        cantileverShearScopeGeometry(),
        cantileverShearScopeForce(),
        cantileverShearScopeEffectiveDepth(),
        cantileverTopReinforcement(),
    ))->toThrow(BeamAnalysisException::class);
});

it('rejects a console for which a section at d cannot exist before the free end', function () {
    expect(fn () => app(CantileverBeamShearVerificationScope::class)->assess(
        cantileverShearScopeConfiguration(),
        new BeamGeometry(500, 300, 600),
        cantileverShearScopeForce(),
        cantileverShearScopeEffectiveDepth(500),
        cantileverTopReinforcement(),
    ))->toThrow(BeamAnalysisException::class);
});
