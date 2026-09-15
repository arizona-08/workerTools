<?php

use App\StructuralCalculation\Beams\BeamCalculationInputFactory;
use App\StructuralCalculation\Beams\BeamCalculationOrchestrator;
use App\StructuralCalculation\Eurocode\Actions\ReinforcedConcreteUnitWeight;
use App\StructuralCalculation\Materials\Concrete\ConcreteClassRepository;
use App\StructuralCalculation\Materials\Concrete\ConcreteStrengthClass;
use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementBarAreaCalculator;
use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementSteelGrade;
use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementSteelGradeRepository;
use App\StructuralCalculation\Slabs\SlabCalculationInputFactory;
use App\StructuralCalculation\Slabs\SlabCalculationOrchestrator;
use App\StructuralCalculation\Slabs\SlabMainReinforcementProposal;
use App\StructuralCalculation\Units\ForceConverter;
use App\StructuralCalculation\Units\LengthConverter;
use App\StructuralCalculation\Units\MomentConverter;

function qaUnitsBeamPayload(): array
{
    return [
        'configuration' => ['calculationMode' => 'DESIGN', 'elementType' => 'BEAM', 'materialType' => 'REINFORCED_CONCRETE', 'sectionType' => 'RECTANGULAR', 'supportSystem' => 'SIMPLY_SUPPORTED', 'loadModel' => 'UNIFORMLY_DISTRIBUTED', 'designCodeProfile' => 'NF_EN_1992_1_1_2005_FR', 'designSituation' => 'PERSISTENT_TRANSIENT'],
        'geometry' => ['effectiveSpan' => 6500, 'width' => 300, 'height' => 600, 'unit' => 'mm'],
        'materials' => ['concreteClass' => 'C30/37', 'steelGrade' => 'B500B', 'exposureClasses' => ['XC1']],
        'loads' => ['permanent' => ['includeSelfWeight' => true, 'additionalPermanentLoad' => 5, 'unit' => 'kN/m'], 'variable' => ['category' => 'A', 'characteristicLoad' => 3.5, 'unit' => 'kN/m']],
    ];
}

function qaUnitsSlabPayload(): array
{
    return [
        'configuration' => ['elementType' => 'SLAB', 'slabType' => 'SOLID', 'spanningSystem' => 'ONE_WAY', 'structuralSystem' => 'SINGLE_SPAN_SIMPLY_SUPPORTED_ON_OPPOSITE_SIDES', 'loadModel' => 'VERTICAL_UNIFORMLY_DISTRIBUTED', 'materialType' => 'REINFORCED_CONCRETE', 'designCodeProfile' => 'NF_EN_1992_1_1_2005_FR', 'designSituation' => 'PERSISTENT_TRANSIENT'],
        'geometry' => ['effectiveSpan' => 5000, 'thickness' => 200],
        'materials' => ['concreteClass' => 'C30/37', 'steelGrade' => 'B500B', 'exposureClass' => 'XC1'],
        'loads' => ['finishes' => 1.5, 'partitions' => 1, 'otherPermanent' => 0.5, 'imposedLoad' => 2],
    ];
}

it('protects the central length, force and moment conversion factors', function () {
    $lengths = app(LengthConverter::class);
    $forces = app(ForceConverter::class);
    $moments = app(MomentConverter::class);

    expect($lengths->millimetresToMetres(1000))->toBe(1.0)
        ->and($lengths->millimetresToMetres(6500))->toBe(6.5)
        ->and($lengths->millimetresToMetres(2750))->toBe(2.75)
        ->and($forces->kilonewtonsToNewtons(1))->toBe(1000.0)
        ->and($forces->newtonsToKilonewtons(1000))->toBe(1.0)
        ->and($moments->kilonewtonMetresToNewtonMillimetres(1))->toBe(1_000_000.0)
        ->and($moments->kilonewtonMetresToNewtonMillimetres(43.125))->toBe(43_125_000.0);
});

it('keeps material strengths in MPa, numerically equal to N/mm²', function () {
    $concrete = app(ConcreteClassRepository::class)->get(ConcreteStrengthClass::C30_37);
    $steel = app(ReinforcementSteelGradeRepository::class)->get(ReinforcementSteelGrade::B500B);

    expect($concrete->fck)->toBe(30.0)
        ->and($concrete->fctm)->toBe(2.9)
        ->and($concrete->ecm)->toBe(33000.0)
        ->and($steel->fyk)->toBe(500.0)
        ->and($steel->es)->toBe(200000.0);
});

it('protects the beam end-to-end unit-sensitive reference values', function () {
    $result = app(BeamCalculationOrchestrator::class)->calculate(app(BeamCalculationInputFactory::class)->fromPayload(qaUnitsBeamPayload()));
    $details = $result->details;

    expect($details->assumptions['geometry']->effectiveSpan)->toBe(6500.0)
        ->and($details->assumptions['geometry']->width)->toBe(300.0)
        ->and($details->assumptions['geometry']->height)->toBe(600.0)
        ->and($details->combinations['characteristicActions']->permanent->selfWeight->unitWeight)->toBeInstanceOf(ReinforcedConcreteUnitWeight::class)
        ->and(abs($details->combinations['characteristicActions']->permanent->selfWeight->characteristicLineLoad - 4.5))->toBeLessThan(1e-10)
        ->and(abs($details->combinations['ultimate']->designLineLoad - 18.075))->toBeLessThan(1e-10)
        ->and(abs($details->internalForces['bendingMoments']->ultimate->maximumMoment - 95.45859375))->toBeLessThan(1e-9)
        ->and(abs($details->internalForces['shearForces']->ultimate->maximumAbsoluteShear - 58.74375))->toBeLessThan(1e-9);
});

it('protects the slab surface-to-line-load conversion and one-metre strip', function () {
    $result = app(SlabCalculationOrchestrator::class)->calculate(app(SlabCalculationInputFactory::class)->fromPayload(qaUnitsSlabPayload()));
    $details = $result->details;
    $linear = $details->internalForces['linearLoads']->uls;
    $forces = $details->internalForces['internalForces']->uls;
    $flexure = $details->flexure['final'];

    expect($details->assumptions['geometry']->effectiveSpan)->toBe(5000.0)
        ->and($details->assumptions['geometry']->thickness)->toBe(200.0)
        ->and($details->assumptions['geometry']->calculationStripWidth)->toBe(1000.0)
        ->and($details->combinations['uls']->permanentCharacteristicLoad)->toBe(8.0)
        ->and($details->combinations['uls']->value)->toBe(13.8)
        ->and($linear->stripWidthMetres)->toBe(1.0)
        ->and($linear->surfaceLoad)->toBe(13.8)
        ->and($linear->lineLoad)->toBe(13.8)
        ->and($forces->maximumMoment)->toBe(43.125)
        ->and($flexure->effectiveDepth->effectiveDepth)->toBe(169.0)
        ->and($flexure->requiredReinforcementArea)->toBeGreaterThan(600.0)
        ->and($flexure::REINFORCEMENT_AREA_UNIT)->toBe('mm²/m');
});

it('keeps bar areas, slab reinforcement per metre and spacing in millimetres', function () {
    $barArea = app(ReinforcementBarAreaCalculator::class)->calculate(10);
    $providedPerMetre = $barArea * 1000 / 150;

    expect(abs($barArea - M_PI * 10 ** 2 / 4))->toBeLessThan(1e-12)
        ->and(abs($providedPerMetre - 523.5987755982989))->toBeLessThan(1e-9)
        ->and(SlabMainReinforcementProposal::SPACING_UNIT)->toBe('mm')
        ->and(SlabMainReinforcementProposal::AREA_UNIT)->toBe('mm²')
        ->and(SlabMainReinforcementProposal::AREA_PER_METRE_UNIT)->toBe('mm²/m');
});

it('rejects a geometry unit other than millimetres at the backend boundary', function () {
    $payload = qaUnitsBeamPayload();
    $payload['geometry']['unit'] = 'm';

    $this->postJson('/api/beam/calculations', $payload)
        ->assertUnprocessable()
        ->assertJsonPath('reason', 'INVALID_GEOMETRY_UNIT');
});
