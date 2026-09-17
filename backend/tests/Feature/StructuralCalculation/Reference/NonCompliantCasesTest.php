<?php

use App\StructuralCalculation\Beams\BeamCalculationInputFactory;
use App\StructuralCalculation\Beams\BeamCalculationOrchestrator;
use App\StructuralCalculation\Slabs\SlabCalculationInputFactory;
use App\StructuralCalculation\Slabs\SlabCalculationOrchestrator;

function qaNonCompliantBeamPayload(float $span = 6500, float $width = 300, float $height = 600, float $additionalPermanent = 5, float $variable = 3.5, ?array $reinforcement = null): array
{
    return [
        'configuration' => ['calculationMode' => $reinforcement === null ? 'DESIGN' : 'VERIFICATION', 'elementType' => 'BEAM', 'materialType' => 'REINFORCED_CONCRETE', 'sectionType' => 'RECTANGULAR', 'supportSystem' => 'SIMPLY_SUPPORTED', 'loadModel' => 'UNIFORMLY_DISTRIBUTED', 'designCodeProfile' => 'NF_EN_1992_1_1_2005_FR', 'designSituation' => 'PERSISTENT_TRANSIENT'],
        'geometry' => ['effectiveSpan' => $span, 'width' => $width, 'height' => $height, 'unit' => 'mm'],
        'materials' => ['concreteClass' => 'C30/37', 'steelGrade' => 'B500B', 'exposureClasses' => ['XC1']],
        'loads' => ['permanent' => ['includeSelfWeight' => true, 'additionalPermanentLoad' => $additionalPermanent, 'unit' => 'kN/m'], 'variable' => ['category' => 'A', 'characteristicLoad' => $variable, 'unit' => 'kN/m']],
        ...($reinforcement === null ? [] : ['reinforcement' => ['longitudinal' => ['tension' => ['barCount' => $reinforcement['count'], 'barDiameter' => $reinforcement['diameter'], 'diameterUnit' => 'mm']]]]),
    ];
}

function qaNonCompliantSlabPayload(float $span, float $thickness, float $finishes, float $partitions, float $otherPermanent, float $imposedLoad): array
{
    return [
        'configuration' => ['elementType' => 'SLAB', 'slabType' => 'SOLID', 'spanningSystem' => 'ONE_WAY', 'structuralSystem' => 'SINGLE_SPAN_SIMPLY_SUPPORTED_ON_OPPOSITE_SIDES', 'loadModel' => 'VERTICAL_UNIFORMLY_DISTRIBUTED', 'materialType' => 'REINFORCED_CONCRETE', 'designCodeProfile' => 'NF_EN_1992_1_1_2005_FR', 'designSituation' => 'PERSISTENT_TRANSIENT'],
        'geometry' => ['effectiveSpan' => $span, 'thickness' => $thickness],
        'materials' => ['concreteClass' => 'C30/37', 'steelGrade' => 'B500B', 'exposureClass' => 'XC1'],
        'loads' => ['finishes' => $finishes, 'partitions' => $partitions, 'otherPermanent' => $otherPermanent, 'imposedLoad' => $imposedLoad],
    ];
}

it('reports the real beam crack-width failure through the complete pipeline', function () {
    $result = app(BeamCalculationOrchestrator::class)->calculate(app(BeamCalculationInputFactory::class)->fromPayload(qaNonCompliantBeamPayload()));
    $crack = $result->details->serviceability['crack'];

    expect($crack->status->value)->toBe('NOT_COMPLIANT')
        ->and($crack->loadCombination)->toBe('QUASI_PERMANENT')
        ->and($crack->crackWidth)->toBeGreaterThan($crack->crackWidthLimit)
        ->and($crack->utilization)->toBeGreaterThan(1.0)
        ->and($result->verifications->crackVerification->status->value)->toBe('NOT_COMPLIANT')
        ->and($result->verifications->overallStatus->value)->toBe('NOT_COMPLIANT')
        ->and($result->summary->status->value)->toBe('NOT_COMPLIANT')
        ->and($result->summary->governingVerificationType)->toBe('CRACK');
    expect(abs($crack->crackWidth - 0.5798699975071787))->toBeLessThan(1e-9)
        ->and(abs($crack->crackWidthLimit - 0.4))->toBeLessThan(1e-12)
        ->and(abs($crack->utilization - 1.4496749937679467))->toBeLessThan(1e-9);
});

it('detects an insufficient supplied beam reinforcement before an invalid candidate can be selected', function () {
    // As,provided = 2 × π × 8² / 4 = 100.53 mm², far below the ≈400 mm² demand.
    $payload = qaNonCompliantBeamPayload(reinforcement: ['count' => 2, 'diameter' => 8]);

    expect(fn () => app(BeamCalculationOrchestrator::class)->calculate(app(BeamCalculationInputFactory::class)->fromPayload($payload)))
        ->toThrow(LogicException::class, 'NO_VALID_LONGITUDINAL_REINFORCEMENT_CANDIDATE');
});

it('detects an excessive beam shear demand before an inadmissible stirrup can be selected', function () {
    // Gk = 25 × 0.2 × 0.6 + 5 = 8 kN/m; wEd = 1.35×8 + 1.50×250 = 385.8 kN/m;
    // VEd = wEd × 2 / 2 = 385.8 kN. The existing stirrup chain rejects every candidate.
    $payload = qaNonCompliantBeamPayload(span: 2000, width: 200, height: 600, variable: 250);

    expect(fn () => app(BeamCalculationOrchestrator::class)->calculate(app(BeamCalculationInputFactory::class)->fromPayload($payload)))
        ->toThrow(LogicException::class, 'NO_VALID_STIRRUP_CANDIDATE');
});

it('reports a real slab deflection failure and preserves it in the final result', function () {
    $result = app(SlabCalculationOrchestrator::class)->calculate(app(SlabCalculationInputFactory::class)->fromPayload(qaNonCompliantSlabPayload(5000, 200, 1.5, 1, 0.5, 5)));
    $deflection = $result->details->serviceability['deflection'];

    expect($deflection->status->value)->toBe('NOT_COMPLIANT')
        ->and($deflection->actualSpanDepthRatio)->toBeGreaterThan($deflection->allowableSpanDepthRatio)
        ->and($deflection->utilization)->toBeGreaterThan(1.0)
        ->and($result->status->value)->toBe('NOT_COMPLIANT')
        ->and($result->details->slsStatus->value)->toBe('NOT_COMPLIANT')
        ->and($result->summary->status->value)->toBe('NOT_COMPLIANT')
        ->and($result->summary->governingVerificationType)->toBe('DEFLECTION');
    expect(abs($deflection->actualSpanDepthRatio - 29.585798816568047))->toBeLessThan(1e-9)
        ->and(abs($deflection->allowableSpanDepthRatio - 22.526807297783796))->toBeLessThan(1e-9)
        ->and(abs($deflection->utilization - 1.3133596086419018))->toBeLessThan(1e-9);
});

it('reports a valid slab with no admissible main reinforcement proposal as non-compliant', function () {
    $result = app(SlabCalculationOrchestrator::class)->calculate(app(SlabCalculationInputFactory::class)->fromPayload(qaNonCompliantSlabPayload(10000, 200, 1.5, 1, 0.5, 3)));
    $main = $result->details->mainReinforcement['proposal'];
    $mainVerification = collect($result->verifications)->firstWhere('identifier', 'MAIN_REINFORCEMENT');

    expect($main->status->value)->toBe('NO_VALID_REINFORCEMENT_PROPOSAL')
        ->and($main->proposal)->toBeNull()
        ->and($result->details->flexure['initial']->designReinforcementArea)->toBeGreaterThan(3400.0)
        ->and($mainVerification)->not->toBeNull()
        ->and($mainVerification->status->value)->toBe('NOT_COMPLIANT')
        ->and($result->details->ulsStatus->value)->toBe('NOT_COMPLIANT')
        ->and($result->status->value)->toBe('NOT_COMPLIANT')
        ->and($result->summary->status->value)->toBe('NOT_COMPLIANT');
});
