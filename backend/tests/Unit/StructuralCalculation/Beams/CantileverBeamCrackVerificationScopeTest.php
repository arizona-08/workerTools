<?php

use App\StructuralCalculation\Beams\BeamCrackVerificationStatus;
use App\StructuralCalculation\Beams\BeamReinforcementPosition;
use App\StructuralCalculation\Beams\BeamReinforcementProposalCandidate;
use App\StructuralCalculation\Beams\BeamTensionFace;
use App\StructuralCalculation\Beams\CantileverBeamCrackVerificationScope;
use App\StructuralCalculation\Beams\CantileverBeamCrackVerificationScopeResult;

it('keeps the actual top longitudinal layout when crack verification lacks a fixed-end stirrup layout', function () {
    $reinforcement = new BeamReinforcementProposalCandidate(4, 16, M_PI * 16 ** 2 / 4, 4 * M_PI * 16 ** 2 / 4, 500, 304, .62, BeamReinforcementPosition::TOP);
    $result = app(CantileverBeamCrackVerificationScope::class)->assess($reinforcement, BeamTensionFace::TOP);

    expect($result->longitudinalReinforcementPosition)->toBe(BeamReinforcementPosition::TOP)
        ->and($result->longitudinalReinforcementArea)->toBe(4 * M_PI * 16 ** 2 / 4)
        ->and($result->longitudinalBarDiameter)->toBe(16.0)
        ->and($result->tensionFace)->toBe(BeamTensionFace::TOP)
        ->and($result->status)->toBe(BeamCrackVerificationStatus::CALCULATION_METHOD_NOT_SUPPORTED)
        ->and($result->limitation)->toBe(CantileverBeamCrackVerificationScopeResult::LIMITATION);
});
