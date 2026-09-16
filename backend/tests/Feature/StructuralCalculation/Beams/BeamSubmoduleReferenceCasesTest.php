<?php

use App\StructuralCalculation\Beams\BeamCalculationInputFactory;
use App\StructuralCalculation\Beams\BeamCalculationOrchestrator;
use App\StructuralCalculation\Beams\BeamReinforcementPosition;
use App\StructuralCalculation\Beams\BeamSubmodule;
use App\StructuralCalculation\Beams\BeamSupportSystem;
use App\StructuralCalculation\Beams\BeamVerificationStatus;

/**
 * Cas figés issus de calculs manuels. Les charges suivent le profil français :
 * γRC = 25 kN/m³, γG = 1,35, γQ = 1,50, ψ1 = 0,50 et ψ2 = 0,30.
 *
 * Les sorties de flexion sont des valeurs de référence EC2 du pipeline actuel,
 * documentées dans docs/qa/beam-reference-cases.md. Elles ne sont jamais
 * obtenues en appelant un service de production depuis les attentes du test.
 */
function beamReferencePayload(BeamSubmodule $submodule, int $effectiveSpan): array
{
    return [
        'configuration' => [
            'calculationMode' => 'DESIGN',
            'elementType' => 'BEAM',
            'materialType' => 'REINFORCED_CONCRETE',
            'sectionType' => 'RECTANGULAR',
            'submodule' => $submodule->value,
            'supportSystem' => $submodule->supportSystem()->value,
            'loadModel' => 'UNIFORMLY_DISTRIBUTED',
            'designCodeProfile' => 'NF_EN_1992_1_1_2005_FR',
            'designSituation' => 'PERSISTENT_TRANSIENT',
        ],
        'geometry' => ['effectiveSpan' => $effectiveSpan, 'width' => 300, 'height' => 600, 'unit' => 'mm'],
        'materials' => ['concreteClass' => 'C30/37', 'steelGrade' => 'B500B', 'exposureClasses' => ['XC1']],
        'loads' => [
            'permanent' => ['includeSelfWeight' => true, 'additionalPermanentLoad' => 5, 'unit' => 'kN/m'],
            'variable' => ['category' => 'A', 'characteristicLoad' => 3.5, 'unit' => 'kN/m'],
        ],
    ];
}

function assertReferenceClose(float $actual, float $expected, float $delta = 1.0e-9): void
{
    expect(abs($actual - $expected))->toBeLessThan($delta);
}

it('keeps the historical simply supported reference case stable through the complete pipeline', function () {
    $setup = app(BeamCalculationInputFactory::class)->fromPayload(beamReferencePayload(BeamSubmodule::BEAM_SIMPLE_RECTANGULAR, 6500));
    $result = app(BeamCalculationOrchestrator::class)->calculate($setup);
    $actions = $result->details->combinations['characteristicActions'];
    $ultimate = $result->details->combinations['ultimate'];
    $serviceability = $result->details->combinations['serviceability'];
    $moments = $result->details->internalForces['bendingMoments'];
    $shears = $result->details->internalForces['shearForces'];
    $selected = $result->details->reinforcement['selectedCandidate'];
    $stress = $result->details->serviceability['stress'];
    $crack = $result->details->serviceability['crack'];
    $deflection = $result->details->serviceability['deflection'];

    // b×h = 0,30×0,60 m² ; Gk,self = 0,18×25 = 4,50 kN/m.
    assertReferenceClose($actions->permanent->selfWeight->characteristicLineLoad, 4.5);
    assertReferenceClose($actions->permanent->totalPermanentLoad, 9.5);
    assertReferenceClose($actions->variable->characteristicLoad, 3.5);
    assertReferenceClose($ultimate->designLineLoad, 18.075);
    assertReferenceClose($serviceability->characteristic->resultingLineLoad, 13.0);
    assertReferenceClose($serviceability->frequent->resultingLineLoad, 11.25);
    assertReferenceClose($serviceability->quasiPermanent->resultingLineLoad, 10.55);

    // MEd = wEd L² / 8 = 95,45859375 kN·m ; VEd = wEd L / 2 = 58,74375 kN.
    assertReferenceClose($moments->ultimate->maximumMoment, 95.45859375);
    assertReferenceClose($shears->ultimate->maximumAbsoluteShear, 58.74375);
    assertReferenceClose($moments->characteristic->maximumMoment, 68.65625);
    assertReferenceClose($moments->frequent->maximumMoment, 59.4140625);
    assertReferenceClose($moments->quasiPermanent->maximumMoment, 55.7171875);
    assertReferenceClose($shears->characteristic->maximumAbsoluteShear, 42.25);
    assertReferenceClose($shears->frequent->maximumAbsoluteShear, 36.5625);
    assertReferenceClose($shears->quasiPermanent->maximumAbsoluteShear, 34.2875);

    assertReferenceClose($selected->effectiveDepth->effectiveDepth, 564.0);
    assertReferenceClose($selected->reducedMoment->reducedDesignMoment, 0.050015610460364);
    assertReferenceClose($selected->neutralAxis->neutralAxisRatio, 0.064166446202773);
    assertReferenceClose($selected->leverArm->leverArm, 549.52404973665);
    assertReferenceClose($selected->requiredArea->requiredReinforcementArea, 399.53622726834);
    assertReferenceClose($selected->minimumArea->requiredMinimum, 255.1536);
    expect($selected->originalCandidate->position)->toBe(BeamReinforcementPosition::BOTTOM)
        ->and($selected->originalCandidate->barCount)->toBe(2)
        ->and($selected->originalCandidate->barDiameter)->toBe(16.0);
    assertReferenceClose($selected->providedArea, 402.12385965949);
    expect($selected->providedArea)->toBeGreaterThanOrEqual($selected->targetArea->targetArea);

    assertReferenceClose($result->details->shear['concreteResistance']->concreteShearResistance, 65.368828962086);
    expect($result->verifications->shearVerification->status)->toBe(BeamVerificationStatus::COMPLIANT)
        ->and($result->verifications->stressVerification->status)->toBe(BeamVerificationStatus::COMPLIANT)
        ->and($result->verifications->crackVerification->status)->toBe(BeamVerificationStatus::NOT_COMPLIANT)
        ->and($result->verifications->deflectionVerification->status)->toBe(BeamVerificationStatus::COMPLIANT)
        ->and($result->summary->status)->toBe(BeamVerificationStatus::NOT_COMPLIANT);
    assertReferenceClose($stress->steelCharacteristic->stress, 319.31750268696);
    assertReferenceClose($crack->crackWidth, 0.57986999750718);
    assertReferenceClose($deflection->utilization, 0.20222029333527);
});

it('keeps the cantilever reference case traceable through actions, flexion, supported ELS and explicit limitations', function () {
    $setup = app(BeamCalculationInputFactory::class)->fromPayload(beamReferencePayload(BeamSubmodule::BEAM_CANTILEVER_RECTANGULAR, 4000));
    $result = app(BeamCalculationOrchestrator::class)->calculate($setup);
    $actions = $result->details->combinations['characteristicActions'];
    $ultimate = $result->details->combinations['ultimate'];
    $serviceability = $result->details->combinations['serviceability'];
    $moments = $result->details->internalForces['bendingMoments'];
    $shears = $result->details->internalForces['shearForces'];
    $selected = $result->details->reinforcement['selectedCandidate'];
    $scope = $result->details->shear['scope'];
    $stress = $result->details->serviceability['stress'];
    $crack = $result->details->serviceability['crack'];
    $deflection = $result->details->serviceability['deflection'];

    // Gk,self = 0,18×25 = 4,50 ; Gk,total = 9,50 ; wEd = 1,35×9,50 + 1,50×3,50 = 18,075.
    assertReferenceClose($actions->permanent->selfWeight->characteristicLineLoad, 4.5);
    assertReferenceClose($actions->permanent->totalPermanentLoad, 9.5);
    assertReferenceClose($actions->variable->characteristicLoad, 3.5);
    assertReferenceClose($ultimate->designLineLoad, 18.075);
    assertReferenceClose($serviceability->characteristic->resultingLineLoad, 13.0);
    assertReferenceClose($serviceability->frequent->resultingLineLoad, 11.25);
    assertReferenceClose($serviceability->quasiPermanent->resultingLineLoad, 10.55);

    // À l'encastrement, M = -wL²/2 et V = wL, avec L = 4,00 m.
    assertReferenceClose($moments->ultimate->maximumMoment, -144.6);
    assertReferenceClose($moments->ultimate->magnitude(), 144.6);
    assertReferenceClose($shears->ultimate->maximumAbsoluteShear, 72.3);
    assertReferenceClose($moments->characteristic->maximumMoment, -104.0);
    assertReferenceClose($moments->frequent->maximumMoment, -90.0);
    assertReferenceClose($moments->quasiPermanent->maximumMoment, -84.4);
    assertReferenceClose($shears->characteristic->maximumAbsoluteShear, 52.0);
    assertReferenceClose($shears->frequent->maximumAbsoluteShear, 45.0);
    assertReferenceClose($shears->quasiPermanent->maximumAbsoluteShear, 42.2);

    assertReferenceClose($selected->effectiveDepth->effectiveDepth, 565.0);
    assertReferenceClose($selected->reducedMoment->signedDesignMoment, -144.6);
    assertReferenceClose($selected->reducedMoment->reducedDesignMoment, 0.075495340277234);
    assertReferenceClose($selected->neutralAxis->neutralAxisRatio, 0.098228728595107);
    assertReferenceClose($selected->neutralAxis->neutralAxisDepth, 55.499231656235);
    assertReferenceClose($selected->leverArm->leverArm, 542.80030733751);
    assertReferenceClose($selected->requiredArea->requiredReinforcementArea, 612.71151748484);
    assertReferenceClose($selected->minimumArea->requiredMinimum, 255.606);
    expect($selected->effectiveDepth->tensionFace->value)->toBe('TOP')
        ->and($selected->originalCandidate->position)->toBe(BeamReinforcementPosition::TOP)
        ->and($selected->originalCandidate->barCount)->toBe(4)
        ->and($selected->originalCandidate->barDiameter)->toBe(14.0);
    assertReferenceClose($selected->providedArea, 615.7521601036);
    expect($selected->providedArea)->toBeGreaterThanOrEqual($selected->targetArea->targetArea);

    assertReferenceClose($scope->designShearForce->maximumAbsoluteShear, 72.3);
    assertReferenceClose($scope->longitudinalReinforcementArea, 615.7521601036);
    expect($scope->longitudinalReinforcementPosition)->toBe(BeamReinforcementPosition::TOP)
        ->and($result->verifications->shearVerification->status)->toBe(BeamVerificationStatus::CALCULATION_METHOD_NOT_SUPPORTED)
        ->and($scope->limitation)->toBe('CANTILEVER_FIXED_END_CRITICAL_SECTION_NOT_MODELLED');

    expect($result->verifications->stressVerification->status)->toBe(BeamVerificationStatus::COMPLIANT)
        ->and($stress->tensionFace->value)->toBe('TOP')
        ->and($stress->steelCharacteristic->moment)->toBeLessThan(0);
    assertReferenceClose($stress->steelCharacteristic->stress, 319.03308960606);
    expect($crack->tensionFace->value)->toBe('TOP')
        ->and($crack->longitudinalReinforcementPosition)->toBe(BeamReinforcementPosition::TOP)
        ->and($crack->longitudinalReinforcementArea)->toBe($selected->providedArea)
        ->and($crack->longitudinalBarDiameter)->toBe(14.0)
        ->and($result->verifications->crackVerification->status)->toBe(BeamVerificationStatus::CALCULATION_METHOD_NOT_SUPPORTED)
        ->and($result->verifications->deflectionVerification->status)->toBe(BeamVerificationStatus::CALCULATION_METHOD_NOT_SUPPORTED)
        ->and($deflection->utilization)->toBeNull()
        ->and($result->summary->status)->toBe(BeamVerificationStatus::NOT_CHECKED);

    expect($result->summary->module->value)->toBe('BEAM')
        ->and($result->summary->submodule)->toBe(BeamSubmodule::BEAM_CANTILEVER_RECTANGULAR)
        ->and($result->summary->supportSystem)->toBe(BeamSupportSystem::CANTILEVER)
        ->and($result->summary->longitudinalReinforcement->position)->toBe(BeamReinforcementPosition::TOP);
    assertReferenceClose($result->summary->designBendingMoment, -144.6);
    assertReferenceClose($result->summary->designShearForce ?? NAN, 72.3);
});
