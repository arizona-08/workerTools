<?php

use App\StructuralCalculation\Beams\BeamGoverningVerificationResolver;
use App\StructuralCalculation\Beams\BeamVerificationAggregationResult;
use App\StructuralCalculation\Beams\BeamVerificationComponent;
use App\StructuralCalculation\Beams\BeamVerificationStatus;

function governingResolver(): BeamGoverningVerificationResolver
{
    return app(BeamGoverningVerificationResolver::class);
}
function governingComponent(string $id, ?float $utilization, BeamVerificationStatus $status = BeamVerificationStatus::COMPLIANT): BeamVerificationComponent
{
    return new BeamVerificationComponent($id, $status, $utilization);
}
function governingAggregation(BeamVerificationComponent $flexure, BeamVerificationComponent $shear, BeamVerificationComponent $stress, BeamVerificationComponent $crack, BeamVerificationComponent $deflection): BeamVerificationAggregationResult
{
    return new BeamVerificationAggregationResult(BeamVerificationStatus::COMPLIANT, BeamVerificationStatus::COMPLIANT, BeamVerificationStatus::COMPLIANT, $flexure, $shear, $stress, $crack, $deflection, []);
}

it('selects the highest existing utilization among each verification family', function (string $expected) {
    $values = ['FLEXURE' => .8, 'SHEAR' => .5, 'STRESS' => .6, 'CRACK' => .61, 'DEFLECTION' => .21];
    $values[$expected] = 1.2;
    $result = governingResolver()->resolve(governingAggregation(...array_map(fn ($id) => governingComponent($id, $values[$id]), array_keys($values))));
    expect($result->governingVerification->identifier)->toBe($expected)->and($result->governingVerification->utilization)->toBe(1.2);
})->with(['flexure' => 'FLEXURE', 'shear' => 'SHEAR', 'stress' => 'STRESS', 'crack' => 'CRACK', 'deflection' => 'DEFLECTION']);

it('allows a failed numerical verification to govern and excludes unavailable checks', function () {
    $result = governingResolver()->resolve(governingAggregation(governingComponent('FLEXURE', .8), governingComponent('SHEAR', .5), governingComponent('STRESS', null, BeamVerificationStatus::NOT_CHECKED), governingComponent('CRACK', 1.2, BeamVerificationStatus::NOT_COMPLIANT), governingComponent('DEFLECTION', .21)));
    expect($result->governingVerification->identifier)->toBe('CRACK')->and($result->exclusions[0]->identifier)->toBe('STRESS')->and($result->exclusions[0]->reason)->toBe('NOT_CHECKED');
});

it('excludes non-applicable and utilization-less checks, with a deterministic flexure tie', function () {
    $result = governingResolver()->resolve(governingAggregation(governingComponent('FLEXURE', .8), governingComponent('SHEAR', .8), governingComponent('STRESS', null), governingComponent('CRACK', 2, BeamVerificationStatus::NOT_APPLICABLE), governingComponent('DEFLECTION', null, BeamVerificationStatus::CALCULATION_METHOD_NOT_SUPPORTED)));
    expect($result->governingVerification->identifier)->toBe('FLEXURE')->and($result->exclusions)->toHaveCount(3)->and(governingResolver()->resolve(governingAggregation(governingComponent('FLEXURE', null, BeamVerificationStatus::NOT_CHECKED), governingComponent('SHEAR', null, BeamVerificationStatus::NOT_APPLICABLE), governingComponent('STRESS', null), governingComponent('CRACK', null), governingComponent('DEFLECTION', null)))->governingVerification)->toBeNull();
});
