<?php

use App\StructuralCalculation\Beams\BeamAnalysisException;
use App\StructuralCalculation\Beams\BeamCalculationConfiguration;
use App\StructuralCalculation\Beams\BeamReinforcementPosition;
use App\StructuralCalculation\Beams\BeamReinforcementProposalCandidate;
use App\StructuralCalculation\Beams\BeamShearCriticalSectionLocation;
use App\StructuralCalculation\Beams\BeamShearForce;
use App\StructuralCalculation\Beams\BeamShearVerificationScopeStatus;
use App\StructuralCalculation\Beams\BeamSubmodule;
use App\StructuralCalculation\Beams\BeamSupportSystem;
use App\StructuralCalculation\Beams\CantileverBeamShearVerificationScope;
use App\StructuralCalculation\Beams\CantileverBeamShearVerificationScopeResult;
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

it('traces the VEd produced by the cantilever analysis at the fixed end without substituting the simply-supported value', function () {
    $result = app(CantileverBeamShearVerificationScope::class)->assess(
        cantileverShearScopeConfiguration(),
        cantileverShearScopeForce(),
        cantileverTopReinforcement(),
    );

    expect($result->designShearForce->maximumAbsoluteShear)->toBe(40.0)
        ->and($result->designShearForce->maximumAbsoluteShear)->not->toBe(20.0)
        ->and($result->criticalSectionLocation)->toBe(BeamShearCriticalSectionLocation::FIXED_END)
        ->and($result->criticalSectionPosition)->toBe(0.0)
        ->and($result->longitudinalReinforcementPosition)->toBe(BeamReinforcementPosition::TOP)
        ->and($result->longitudinalReinforcementArea)->toBe(4 * M_PI * 16 ** 2 / 4)
        ->and($result->status)->toBe(BeamShearVerificationScopeStatus::CALCULATION_METHOD_NOT_SUPPORTED)
        ->and($result->limitation)->toBe(CantileverBeamShearVerificationScopeResult::LIMITATION);
});

it('does not apply the cantilever critical-section limitation to another structural system', function () {
    expect(fn () => app(CantileverBeamShearVerificationScope::class)->assess(
        BeamCalculationConfiguration::supported(),
        cantileverShearScopeForce(),
        cantileverTopReinforcement(),
    ))->toThrow(BeamAnalysisException::class);
});
