<?php

use App\StructuralCalculation\Materials\Exposure\ExposureClassCode;
use App\StructuralCalculation\Materials\Exposure\ExposureClassRepository;
use App\StructuralCalculation\Materials\Exposure\ExposureConditions;
use App\StructuralCalculation\Materials\Exposure\ExposureFamily;

it('returns X0 as an exposure class without degradation mechanism', function () {
    $exposure = app(ExposureClassRepository::class)->get(ExposureClassCode::X0);

    expect($exposure->code)->toBe(ExposureClassCode::X0)
        ->and($exposure->family)->toBe(ExposureFamily::NO_RISK)
        ->and($exposure->degradationMechanism)->toBeNull();
});

it('returns carbonation classes with their family and readable description', function (string $code) {
    $exposure = app(ExposureClassRepository::class)->find($code);

    expect($exposure)->not->toBeNull()
        ->and($exposure->family)->toBe(ExposureFamily::CARBONATION)
        ->and($exposure->label)->not->toBe('')
        ->and($exposure->description)->toContain('carbonatation');
})->with(['XC1', 'XC4']);

it('returns chloride exposure classes with their applicable family', function (string $code, ExposureFamily $family) {
    $exposure = app(ExposureClassRepository::class)->find($code);

    expect($exposure)->not->toBeNull()
        ->and($exposure->family)->toBe($family);
})->with([
    ['XD3', ExposureFamily::CHLORIDES_NOT_SEAWATER],
    ['XS3', ExposureFamily::CHLORIDES_SEAWATER],
]);

it('supports freeze thaw and chemical attack exposure families', function () {
    $freezeThaw = app(ExposureClassRepository::class)->get(ExposureClassCode::XF1);
    $chemicalAttack = app(ExposureClassRepository::class)->get(ExposureClassCode::XA1);

    expect($freezeThaw->family)->toBe(ExposureFamily::FREEZE_THAW)
        ->and($chemicalAttack->family)->toBe(ExposureFamily::CHEMICAL_ATTACK);
});

it('does not resolve an unknown exposure class', function () {
    $exposure = app(ExposureClassRepository::class)->find('XZ1');

    expect($exposure)->toBeNull();
});

it('does not store cover requirements in an exposure class definition', function () {
    $exposure = app(ExposureClassRepository::class)->get(ExposureClassCode::XC3);

    expect(property_exists($exposure, 'cMinDur'))->toBeFalse()
        ->and(property_exists($exposure, 'cNom'))->toBeFalse();
});

it('allows multiple exposure classes to be represented together', function () {
    $conditions = new ExposureConditions(ExposureClassCode::XC4, ExposureClassCode::XF1);

    expect($conditions->exposureClasses)->toBe([ExposureClassCode::XC4, ExposureClassCode::XF1]);
});
