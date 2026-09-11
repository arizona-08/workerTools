<?php

use App\StructuralCalculation\Beams\BeamCalculationInputFactory;
use App\StructuralCalculation\Beams\BeamGeometry;
use App\StructuralCalculation\Beams\BeamPermanentLoads;
use App\StructuralCalculation\Beams\BeamVariableLoad;
use App\StructuralCalculation\Beams\CharacteristicActionsCalculationException;
use App\StructuralCalculation\Beams\CharacteristicActionsCalculationRejectionReason;
use App\StructuralCalculation\Beams\CharacteristicActionsCalculator;
use App\StructuralCalculation\Beams\SelfWeightCalculator;
use App\StructuralCalculation\Beams\SelfWeightResult;
use App\StructuralCalculation\Eurocode\Actions\ReinforcedConcreteUnitWeight;
use App\StructuralCalculation\Eurocode\Actions\ReinforcedConcreteUnitWeightRepository;
use App\StructuralCalculation\Eurocode\Profiles\VariableActionCategory;

function characteristicActionsCalculator(): CharacteristicActionsCalculator
{
    return app(CharacteristicActionsCalculator::class);
}

function selfWeight(bool $includeSelfWeight): SelfWeightResult
{
    return app(SelfWeightCalculator::class)->calculate(
        new BeamGeometry(6500, 300, 600),
        $includeSelfWeight,
        app(ReinforcedConcreteUnitWeightRepository::class)->normalWeightReinforcedConcrete(),
    );
}

function categoryA(float $load): BeamVariableLoad
{
    return new BeamVariableLoad(VariableActionCategory::A_DOMESTIC_RESIDENTIAL_AREAS, $load);
}

it('builds the reference characteristic actions without coefficients', function () {
    $result = characteristicActionsCalculator()->calculate(selfWeight(true), new BeamPermanentLoads(true, 5), categoryA(3.5));

    expect($result->permanent->selfWeight->characteristicLineLoad)->toBe(4.5)
        ->and($result->permanent->additionalPermanentLoad)->toBe(5.0)
        ->and($result->permanent->totalPermanentLoad)->toBe(9.5)
        ->and($result->permanent::UNIT)->toBe('kN/m')
        ->and($result->permanent::TOTAL_FORMULA)->toBe('Gk_total = Gk_self + Gk_additional')
        ->and($result->variable->category)->toBe(VariableActionCategory::A_DOMESTIC_RESIDENTIAL_AREAS)
        ->and($result->variable->characteristicLoad)->toBe(3.5)
        ->and($result->variable::UNIT)->toBe('kN/m')
        ->and(property_exists($result, 'gammaG'))->toBeFalse()
        ->and(property_exists($result, 'gammaQ'))->toBeFalse()
        ->and(property_exists($result, 'psi0'))->toBeFalse();
});

it('keeps the unrounded self weight in the permanent total', function () {
    $selfWeight = new SelfWeightResult(true, 300, 600, 0.18, new ReinforcedConcreteUnitWeight(25), 4.512345);
    $result = characteristicActionsCalculator()->calculate($selfWeight, new BeamPermanentLoads(true, 0.123456), categoryA(0));

    expect(abs($result->permanent->totalPermanentLoad - 4.635801))->toBeLessThan(0.000000001);
});

it('keeps valid zero-action cases mathematically explicit', function (bool $includeSelfWeight, float $additional, float $qk, float $expectedTotal) {
    $result = characteristicActionsCalculator()->calculate(selfWeight($includeSelfWeight), new BeamPermanentLoads($includeSelfWeight, $additional), categoryA($qk));

    expect($result->permanent->totalPermanentLoad)->toBe($expectedTotal)
        ->and($result->variable->characteristicLoad)->toBe($qk);
})->with([
    'without self weight' => [false, 5.0, 3.5, 5.0],
    'without additional permanent load' => [true, 0.0, 3.5, 4.5],
    'without permanent action' => [false, 0.0, 0.0, 0.0],
]);

it('protects characteristic-action invariants', function (SelfWeightResult $selfWeight, BeamPermanentLoads $permanentLoads, BeamVariableLoad $variableLoad, CharacteristicActionsCalculationRejectionReason $reason) {
    try {
        characteristicActionsCalculator()->calculate($selfWeight, $permanentLoads, $variableLoad);
    } catch (CharacteristicActionsCalculationException $exception) {
        expect($exception->reason)->toBe($reason);

        return;
    }

    throw new RuntimeException('Expected invalid characteristic action to be rejected.');
})->with([
    'negative self weight' => [new SelfWeightResult(true, 300, 600, 0.18, new ReinforcedConcreteUnitWeight(25), -1), new BeamPermanentLoads(true, 0), categoryA(0), CharacteristicActionsCalculationRejectionReason::INVALID_SELF_WEIGHT],
    'negative additional permanent load' => [selfWeight(true), new BeamPermanentLoads(true, -1), categoryA(0), CharacteristicActionsCalculationRejectionReason::INVALID_ADDITIONAL_PERMANENT_LOAD],
    'negative variable load' => [selfWeight(true), new BeamPermanentLoads(true, 0), categoryA(-1), CharacteristicActionsCalculationRejectionReason::INVALID_CHARACTERISTIC_VARIABLE_LOAD],
]);

it('chains validated beam input through self weight into characteristic actions', function () {
    $setup = app(BeamCalculationInputFactory::class)->fromPayload([
        'configuration' => [
            'calculationMode' => 'DESIGN',
            'elementType' => 'BEAM',
            'materialType' => 'REINFORCED_CONCRETE',
            'sectionType' => 'RECTANGULAR',
            'supportSystem' => 'SIMPLY_SUPPORTED',
            'loadModel' => 'UNIFORMLY_DISTRIBUTED',
            'designCodeProfile' => 'NF_EN_1992_1_1_2005_FR',
            'designSituation' => 'PERSISTENT_TRANSIENT',
        ],
        'geometry' => ['effectiveSpan' => 6500, 'width' => 300, 'height' => 600, 'unit' => 'mm'],
        'materials' => ['concreteClass' => 'C30/37', 'steelGrade' => 'B500B', 'exposureClasses' => ['XC1']],
        'loads' => [
            'permanent' => ['includeSelfWeight' => true, 'additionalPermanentLoad' => 5, 'unit' => 'kN/m'],
            'variable' => ['category' => 'A', 'characteristicLoad' => 3.5, 'unit' => 'kN/m'],
        ],
    ]);
    $selfWeight = app(SelfWeightCalculator::class)->calculate(
        $setup->geometry,
        $setup->permanentLoads->includeSelfWeight,
        app(ReinforcedConcreteUnitWeightRepository::class)->normalWeightReinforcedConcrete(),
    );
    $result = characteristicActionsCalculator()->calculate($selfWeight, $setup->permanentLoads, $setup->variableLoad);

    expect($result->permanent->selfWeight->characteristicLineLoad)->toBe(4.5)
        ->and($result->permanent->totalPermanentLoad)->toBe(9.5)
        ->and($result->variable->characteristicLoad)->toBe(3.5);
});
