<?php

use App\StructuralCalculation\Beams\BeamCalculationConfiguration;
use App\StructuralCalculation\Beams\BeamCalculationConfigurationFactory;
use App\StructuralCalculation\Beams\BeamCalculationConfigurationValidator;
use App\StructuralCalculation\Beams\BeamCalculationMode;
use App\StructuralCalculation\Beams\BeamConfigurationException;
use App\StructuralCalculation\Beams\BeamConfigurationRejectionReason;
use App\StructuralCalculation\Beams\BeamDesignSituation;
use App\StructuralCalculation\Beams\BeamLoadModel;
use App\StructuralCalculation\Beams\BeamMaterialType;
use App\StructuralCalculation\Beams\BeamSectionType;
use App\StructuralCalculation\Beams\BeamSupportSystem;
use App\StructuralCalculation\Eurocode\Profiles\DesignCodeProfileIdentifier;

function beamConfigurationValidator(): BeamCalculationConfigurationValidator
{
    return app(BeamCalculationConfigurationValidator::class);
}

it('validates the explicit MVP beam configuration and reuses the French profile', function () {
    $configuration = BeamCalculationConfiguration::mvp();

    beamConfigurationValidator()->validate($configuration);

    expect($configuration->sectionType)->toBe(BeamSectionType::RECTANGULAR)
        ->and($configuration->calculationMode)->toBe(BeamCalculationMode::DESIGN)
        ->and($configuration->materialType)->toBe(BeamMaterialType::REINFORCED_CONCRETE)
        ->and($configuration->supportSystem)->toBe(BeamSupportSystem::SIMPLY_SUPPORTED)
        ->and($configuration->loadModel)->toBe(BeamLoadModel::UNIFORMLY_DISTRIBUTED)
        ->and($configuration->designSituation)->toBe(BeamDesignSituation::PERSISTENT_TRANSIENT)
        ->and($configuration->designCodeProfile)->toBe(DesignCodeProfileIdentifier::NF_EN_1992_1_1_2005_FR);
});

it('accepts both supported calculation modes in the beam configuration', function (BeamCalculationMode $mode) {
    $values = configurationValues(calculationMode: $mode);
    $configuration = new BeamCalculationConfiguration(...$values);

    beamConfigurationValidator()->validate($configuration);

    expect($configuration->calculationMode)->toBe($mode);
})->with([BeamCalculationMode::DESIGN, BeamCalculationMode::VERIFICATION]);

it('rejects valid conceptual configurations that are not supported by the MVP', function (BeamCalculationConfiguration $configuration, BeamConfigurationRejectionReason $reason) {
    try {
        beamConfigurationValidator()->validate($configuration);
    } catch (BeamConfigurationException $exception) {
        expect($exception->reason)->toBe($reason);

        return;
    }

    throw new RuntimeException('Expected the unsupported configuration to be rejected.');
})->with([
    'T section' => [new BeamCalculationConfiguration(...[...configurationValues(sectionType: BeamSectionType::T_SECTION)]), BeamConfigurationRejectionReason::UNSUPPORTED_SECTION_TYPE],
    'continuous support' => [new BeamCalculationConfiguration(...[...configurationValues(supportSystem: BeamSupportSystem::CONTINUOUS)]), BeamConfigurationRejectionReason::UNSUPPORTED_SUPPORT_SYSTEM],
    'point load' => [new BeamCalculationConfiguration(...[...configurationValues(loadModel: BeamLoadModel::POINT_LOAD)]), BeamConfigurationRejectionReason::UNSUPPORTED_LOAD_MODEL],
]);

it('distinguishes an invalid external value from a supported-value request', function () {
    app(BeamCalculationConfigurationFactory::class)->fromValues(
        'UNKNOWN_MODE',
        'BEAM',
        'REINFORCED_CONCRETE',
        'UNKNOWN_SECTION',
        'SIMPLY_SUPPORTED',
        'UNIFORMLY_DISTRIBUTED',
        'NF_EN_1992_1_1_2005_FR',
        'PERSISTENT_TRANSIENT',
    );
})->throws(BeamConfigurationException::class, BeamConfigurationRejectionReason::INVALID_CONFIGURATION_VALUE->value);

/** @return array<string, mixed> */
function configurationValues(
    ?BeamCalculationMode $calculationMode = null,
    ?BeamSectionType $sectionType = null,
    ?BeamSupportSystem $supportSystem = null,
    ?BeamLoadModel $loadModel = null,
): array {
    $configuration = BeamCalculationConfiguration::mvp();

    return [
        'calculationMode' => $calculationMode ?? $configuration->calculationMode,
        'elementType' => $configuration->elementType,
        'materialType' => $configuration->materialType,
        'sectionType' => $sectionType ?? $configuration->sectionType,
        'supportSystem' => $supportSystem ?? $configuration->supportSystem,
        'loadModel' => $loadModel ?? $configuration->loadModel,
        'designCodeProfile' => $configuration->designCodeProfile,
        'designSituation' => $configuration->designSituation,
    ];
}
