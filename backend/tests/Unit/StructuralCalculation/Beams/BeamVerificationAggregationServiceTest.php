<?php

use App\StructuralCalculation\Beams\BeamVerificationAggregationService;
use App\StructuralCalculation\Beams\BeamVerificationComponent;
use App\StructuralCalculation\Beams\BeamVerificationStatus;

function aggregationService(): BeamVerificationAggregationService
{
    return app(BeamVerificationAggregationService::class);
}
function verification(string $id, BeamVerificationStatus $status, array $warnings = []): BeamVerificationComponent
{
    return new BeamVerificationComponent($id, $status, warnings: $warnings);
}

it('aggregates all compliant local verifications without selecting a governing check', function () {
    $result = aggregationService()->aggregate(verification('FLEXURE', BeamVerificationStatus::COMPLIANT), verification('SHEAR', BeamVerificationStatus::COMPLIANT), verification('STRESS', BeamVerificationStatus::COMPLIANT), verification('CRACK', BeamVerificationStatus::COMPLIANT), verification('DEFLECTION', BeamVerificationStatus::COMPLIANT));
    expect($result->ulsStatus)->toBe(BeamVerificationStatus::COMPLIANT)->and($result->slsStatus)->toBe(BeamVerificationStatus::COMPLIANT)->and($result->overallStatus)->toBe(BeamVerificationStatus::COMPLIANT);
});

it('prioritizes a non-compliance from any required local verification', function (string $failed) {
    $components = ['FLEXURE' => verification('FLEXURE', BeamVerificationStatus::COMPLIANT), 'SHEAR' => verification('SHEAR', BeamVerificationStatus::COMPLIANT), 'STRESS' => verification('STRESS', BeamVerificationStatus::COMPLIANT), 'CRACK' => verification('CRACK', BeamVerificationStatus::COMPLIANT), 'DEFLECTION' => verification('DEFLECTION', BeamVerificationStatus::COMPLIANT)];
    $components[$failed] = verification($failed, BeamVerificationStatus::NOT_COMPLIANT);
    $result = aggregationService()->aggregate(...array_values($components));
    expect($result->overallStatus)->toBe(BeamVerificationStatus::NOT_COMPLIANT);
})->with(['flexure' => 'FLEXURE', 'shear' => 'SHEAR', 'stress' => 'STRESS', 'crack' => 'CRACK', 'deflection' => 'DEFLECTION']);

it('never converts missing or unsupported mandatory checks into compliance', function () {
    $missing = aggregationService()->aggregate(verification('FLEXURE', BeamVerificationStatus::COMPLIANT), verification('SHEAR', BeamVerificationStatus::COMPLIANT), verification('STRESS', BeamVerificationStatus::COMPLIANT), verification('CRACK', BeamVerificationStatus::COMPLIANT), null);
    $unsupported = aggregationService()->aggregate(verification('FLEXURE', BeamVerificationStatus::COMPLIANT), verification('SHEAR', BeamVerificationStatus::COMPLIANT), verification('STRESS', BeamVerificationStatus::COMPLIANT), verification('CRACK', BeamVerificationStatus::COMPLIANT), verification('DEFLECTION', BeamVerificationStatus::CALCULATION_METHOD_NOT_SUPPORTED));
    expect($missing->overallStatus)->toBe(BeamVerificationStatus::NOT_CHECKED)->and($missing->slsStatus)->toBe(BeamVerificationStatus::NOT_CHECKED)->and($missing->warnings)->toContain('REQUIRED_VERIFICATION_MISSING')->and($unsupported->overallStatus)->toBe(BeamVerificationStatus::NOT_CHECKED);
});

it('ignores explicitly non-applicable checks and deduplicates warnings', function () {
    $result = aggregationService()->aggregate(verification('FLEXURE', BeamVerificationStatus::COMPLIANT, ['LIMIT']), verification('SHEAR', BeamVerificationStatus::NOT_APPLICABLE, ['LIMIT']), verification('STRESS', BeamVerificationStatus::COMPLIANT), verification('CRACK', BeamVerificationStatus::COMPLIANT), verification('DEFLECTION', BeamVerificationStatus::COMPLIANT));
    expect($result->overallStatus)->toBe(BeamVerificationStatus::COMPLIANT)->and($result->warnings)->toBe(['LIMIT']);
});

it('keeps status priority independent from component ordering', function () {
    $components = [
        verification('FLEXURE', BeamVerificationStatus::COMPLIANT),
        verification('SHEAR', BeamVerificationStatus::NOT_CHECKED),
        verification('STRESS', BeamVerificationStatus::NOT_COMPLIANT),
        verification('CRACK', BeamVerificationStatus::NOT_APPLICABLE),
        verification('DEFLECTION', BeamVerificationStatus::COMPLIANT),
    ];

    $forward = aggregationService()->aggregate(...$components);
    $reverse = aggregationService()->aggregate(...array_reverse($components));

    expect($forward->overallStatus)->toBe(BeamVerificationStatus::NOT_COMPLIANT)
        ->and($reverse->overallStatus)->toBe(BeamVerificationStatus::NOT_COMPLIANT);
});
