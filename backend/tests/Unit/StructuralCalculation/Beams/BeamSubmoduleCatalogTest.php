<?php

use App\StructuralCalculation\Beams\BeamSubmoduleCatalog;
use App\StructuralCalculation\Beams\BeamSubmoduleStatus;
use App\StructuralCalculation\Beams\BeamSupportSystem;

function beamSubmoduleCatalog(): BeamSubmoduleCatalog
{
    return app(BeamSubmoduleCatalog::class);
}

it('contains exactly the two V1 Beam submodules with their product metadata', function () {
    $entries = beamSubmoduleCatalog()->all();

    expect($entries)->toHaveCount(2)
        ->and($entries[0]->toPublicArray())->toBe([
            'id' => 'BEAM_SIMPLE_RECTANGULAR',
            'label' => 'Poutre rectangulaire simplement appuyée',
            'status' => 'AVAILABLE',
            'supportSystem' => 'SIMPLY_SUPPORTED',
        ])
        ->and($entries[1]->toPublicArray())->toBe([
            'id' => 'BEAM_CANTILEVER_RECTANGULAR',
            'label' => 'Poutre rectangulaire en console',
            'status' => 'AVAILABLE',
            'supportSystem' => 'CANTILEVER',
        ]);
});

it('reuses the central submodule to support-system mapping', function () {
    expect(beamSubmoduleCatalog()->find('BEAM_SIMPLE_RECTANGULAR')?->supportSystem())->toBe(BeamSupportSystem::SIMPLY_SUPPORTED)
        ->and(beamSubmoduleCatalog()->find('BEAM_CANTILEVER_RECTANGULAR')?->supportSystem())->toBe(BeamSupportSystem::CANTILEVER);
});

it('returns no entry and no availability for an unknown submodule identifier', function () {
    expect(beamSubmoduleCatalog()->find('BEAM_UNKNOWN'))->toBeNull()
        ->and(beamSubmoduleCatalog()->isAvailable('BEAM_UNKNOWN'))->toBeFalse();
});

it('expresses available, coming-soon and unavailable product statuses', function () {
    expect(BeamSubmoduleStatus::cases())->toBe([
        BeamSubmoduleStatus::AVAILABLE,
        BeamSubmoduleStatus::COMING_SOON,
        BeamSubmoduleStatus::UNAVAILABLE,
    ]);
});
