<?php

use App\StructuralCalculation\Beams\BeamFlexuralConcreteDesignStrength;
use App\StructuralCalculation\Beams\BeamGeometryException;
use App\StructuralCalculation\Beams\BeamGeometryFactory;
use App\StructuralCalculation\Beams\BeamMaximumShearResistanceCalculator;
use App\StructuralCalculation\Beams\BeamMaximumShearResistanceStatus;
use App\StructuralCalculation\Beams\BeamShearReinforcementDesignResult;
use App\StructuralCalculation\Beams\BeamShearReinforcementGoverningRequirement;
use App\StructuralCalculation\Eurocode\Profiles\FrenchEurocodeProfileRepository;
use App\StructuralCalculation\Materials\Concrete\ConcreteClassRepository;
use App\StructuralCalculation\Materials\Concrete\ConcreteStrengthClass;
use App\StructuralCalculation\Slabs\SlabCalculationInputFactory;
use App\StructuralCalculation\Slabs\SlabCalculationOrchestrator;
use App\StructuralCalculation\Slabs\SlabGeometryException;
use App\StructuralCalculation\Slabs\SlabGeometryFactory;
use App\StructuralCalculation\Slabs\SlabSurfaceLoadsException;
use App\StructuralCalculation\Slabs\SlabSurfaceLoadsFactory;

function qaBoundaryBeamPayload(bool $includeSelfWeight = true, float $additionalPermanent = 5, float $variable = 3.5): array
{
    return [
        'configuration' => ['calculationMode' => 'DESIGN', 'elementType' => 'BEAM', 'materialType' => 'REINFORCED_CONCRETE', 'sectionType' => 'RECTANGULAR', 'supportSystem' => 'SIMPLY_SUPPORTED', 'loadModel' => 'UNIFORMLY_DISTRIBUTED', 'designCodeProfile' => 'NF_EN_1992_1_1_2005_FR', 'designSituation' => 'PERSISTENT_TRANSIENT'],
        'geometry' => ['effectiveSpan' => 5000, 'width' => 300, 'height' => 600, 'unit' => 'mm'],
        'materials' => ['concreteClass' => 'C30/37', 'steelGrade' => 'B500B', 'exposureClasses' => ['XC1']],
        'loads' => ['permanent' => ['includeSelfWeight' => $includeSelfWeight, 'additionalPermanentLoad' => $additionalPermanent, 'unit' => 'kN/m'], 'variable' => ['category' => 'A', 'characteristicLoad' => $variable, 'unit' => 'kN/m']],
    ];
}

function qaBoundarySlabPayload(float $thickness = 200, float $imposedLoad = 2): array
{
    return [
        'configuration' => ['elementType' => 'SLAB', 'slabType' => 'SOLID', 'spanningSystem' => 'ONE_WAY', 'structuralSystem' => 'SINGLE_SPAN_SIMPLY_SUPPORTED_ON_OPPOSITE_SIDES', 'loadModel' => 'VERTICAL_UNIFORMLY_DISTRIBUTED', 'materialType' => 'REINFORCED_CONCRETE', 'designCodeProfile' => 'NF_EN_1992_1_1_2005_FR', 'designSituation' => 'PERSISTENT_TRANSIENT'],
        'geometry' => ['effectiveSpan' => 5000, 'thickness' => $thickness],
        'materials' => ['concreteClass' => 'C30/37', 'steelGrade' => 'B500B', 'exposureClass' => 'XC1'],
        'loads' => ['finishes' => 0, 'partitions' => 0, 'otherPermanent' => 0, 'imposedLoad' => $imposedLoad],
    ];
}

function qaBoundaryMaximumShear(float $designShearForce): object
{
    $design = new BeamShearReinforcementDesignResult($designShearForce, 63.862728452911, false, 300, 531.019606207552, 500, 500 / 1.15, 2.5, 0.08 * sqrt(30) / 500, 0, 0.08 * sqrt(30) / 500 * 300, 0.08 * sqrt(30) / 500 * 300, BeamShearReinforcementGoverningRequirement::MINIMUM_TRANSVERSE_REINFORCEMENT, 151.748565285593);

    return app(BeamMaximumShearResistanceCalculator::class)->calculate(
        $design,
        app(ConcreteClassRepository::class)->get(ConcreteStrengthClass::C30_37),
        new BeamFlexuralConcreteDesignStrength(ConcreteStrengthClass::C30_37, 30, 1, 1.5, 20),
        app(FrenchEurocodeProfileRepository::class)->get(),
    );
}

it('returns a controlled 422 response for zero or negative beam dimensions', function (string $field, float $value, string $reason) {
    $payload = qaBoundaryBeamPayload();
    $payload['geometry'][$field] = $value;

    $this->postJson('/api/beam/calculations', $payload)
        ->assertUnprocessable()
        ->assertJsonPath('reason', $reason)
        ->assertJsonMissingPath('trace');
})->with([
    'zero span' => ['effectiveSpan', 0.0, 'INVALID_EFFECTIVE_SPAN'],
    'negative width' => ['width', -1.0, 'INVALID_WIDTH'],
    'zero height' => ['height', 0.0, 'INVALID_HEIGHT'],
]);

it('returns a controlled 422 response for zero or negative slab dimensions', function (string $field, float $value, string $reason) {
    $payload = qaBoundarySlabPayload();
    $payload['geometry'][$field] = $value;

    $this->postJson('/api/slab/calculations', $payload)
        ->assertUnprocessable()
        ->assertJsonPath('reason', $reason)
        ->assertJsonMissingPath('trace');
})->with([
    'zero span' => ['effectiveSpan', 0.0, 'INVALID_EFFECTIVE_SPAN'],
    'negative span' => ['effectiveSpan', -1.0, 'INVALID_EFFECTIVE_SPAN'],
    'zero thickness' => ['thickness', 0.0, 'INVALID_THICKNESS'],
]);

it('keeps optional zero loads valid when self weight remains', function () {
    $beam = $this->postJson('/api/beam/calculations', qaBoundaryBeamPayload(true, 0, 0));
    $slabPayload = qaBoundarySlabPayload(imposedLoad: 0);
    $slab = app(SlabCalculationOrchestrator::class)->calculate(app(SlabCalculationInputFactory::class)->fromPayload($slabPayload));

    $beam->assertOk()
        ->assertJsonPath('details.combinations.characteristicActions.permanent.totalPermanentLoad', 4.5)
        ->assertJsonPath('details.combinations.characteristicActions.variable.characteristicLoad', 0);
    expect($slab->details->combinations['uls']->permanentCharacteristicLoad)->toBe(5.0)
        ->and($slab->details->combinations['uls']->variableCharacteristicLoad)->toBe(0.0);
});

it('refuses a beam with no actions through its public HTTP contract', function () {
    $this->postJson('/api/beam/calculations', qaBoundaryBeamPayload(false, 0, 0))
        ->assertUnprocessable()
        ->assertJsonPath('reason', 'INVALID_REQUIRED_REINFORCEMENT')
        ->assertJsonMissingPath('trace');
});

it('handles technically tiny sections explicitly without non-finite output', function () {
    $beam = qaBoundaryBeamPayload();
    $beam['geometry']['width'] = 1;
    $slab = qaBoundarySlabPayload(thickness: 1);

    $this->postJson('/api/beam/calculations', $beam)
        ->assertUnprocessable()
        ->assertJsonPath('reason', 'INVALID_NEUTRAL_AXIS_RADICAND');
    $this->postJson('/api/slab/calculations', $slab)
        ->assertUnprocessable()
        ->assertJsonPath('reason', 'NON_POSITIVE_EFFECTIVE_DEPTH');
});

it('rejects non-finite public-domain values before they reach calculations', function () {
    expect(fn () => app(BeamGeometryFactory::class)->fromInternalValues(INF, 300, 600))->toThrow(BeamGeometryException::class, 'INVALID_EFFECTIVE_SPAN')
        ->and(fn () => app(SlabGeometryFactory::class)->fromInternalValues(5000, NAN))->toThrow(SlabGeometryException::class, 'INVALID_THICKNESS')
        ->and(fn () => app(SlabSurfaceLoadsFactory::class)->fromValues(0, 0, 0, INF))->toThrow(SlabSurfaceLoadsException::class, 'INVALID_IMPOSED_LOAD');
});

it('keeps a high but finite slab load numerically finite through its final result', function () {
    $result = app(SlabCalculationOrchestrator::class)->calculate(app(SlabCalculationInputFactory::class)->fromPayload(qaBoundarySlabPayload(imposedLoad: 20)));
    $flexure = $result->details->flexure['final'];
    $crack = $result->details->serviceability['crack'];
    $deflection = $result->details->serviceability['deflection'];

    expect($result->status->value)->toBe('NOT_COMPLIANT');
    foreach ([$flexure->effectiveDepth->effectiveDepth, $flexure->reducedMoment, $flexure->neutralAxisDepth, $flexure->leverArm, $flexure->requiredReinforcementArea, $flexure->minimumReinforcementArea, $crack->crackWidth, $deflection->utilization] as $value) {
        expect($value)->not->toBeNull()->and(is_finite($value))->toBeTrue();
    }
});

it('keeps the VRd,max comparison inclusive at and below its exact capacity', function () {
    $reference = qaBoundaryMaximumShear(1);
    $below = qaBoundaryMaximumShear($reference->maximumShearResistance - 0.001);
    $at = qaBoundaryMaximumShear($reference->maximumShearResistance);
    $above = qaBoundaryMaximumShear($reference->maximumShearResistance + 0.001);

    expect($below->status)->toBe(BeamMaximumShearResistanceStatus::MAXIMUM_SHEAR_RESISTANCE_OK)
        ->and($at->status)->toBe(BeamMaximumShearResistanceStatus::MAXIMUM_SHEAR_RESISTANCE_OK)
        ->and($at->utilizationMaximumShear)->toBe(1.0)
        ->and($above->status)->toBe(BeamMaximumShearResistanceStatus::MAXIMUM_SHEAR_RESISTANCE_EXCEEDED)
        ->and($above->utilizationMaximumShear)->toBeGreaterThan(1.0);
});
