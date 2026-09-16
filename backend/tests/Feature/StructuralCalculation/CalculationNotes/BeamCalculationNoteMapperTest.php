<?php

use App\StructuralCalculation\Beams\BeamCalculationInputFactory;
use App\StructuralCalculation\Beams\BeamCalculationMode;
use App\StructuralCalculation\Beams\BeamCalculationOrchestrator;
use App\StructuralCalculation\Beams\BeamCalculationResponse;
use App\StructuralCalculation\Beams\BeamCalculationSetup;
use App\StructuralCalculation\Beams\BeamVerificationStatus;
use App\StructuralCalculation\CalculationNotes\BeamCalculationNoteMapper;
use App\StructuralCalculation\CalculationNotes\CalculationNoteDocument;
use App\StructuralCalculation\CalculationNotes\Pdf\CalculationNotePdfPresentation;
use App\StructuralCalculation\CalculationNotes\Pdf\CalculationNoteRenderer;

function beamCalculationNotePayload(string $mode = 'DESIGN'): array
{
    return [
        'configuration' => [
            'calculationMode' => $mode,
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
        ...($mode === 'VERIFICATION' ? ['reinforcement' => ['longitudinal' => ['tension' => ['barCount' => 4, 'barDiameter' => 12, 'diameterUnit' => 'mm']]]] : []),
    ];
}

/** @return array{0: BeamCalculationSetup, 1: BeamCalculationResponse} */
function beamCalculationNoteSource(string $mode = 'DESIGN'): array
{
    $setup = app(BeamCalculationInputFactory::class)->fromPayload(beamCalculationNotePayload($mode));

    return [$setup, app(BeamCalculationOrchestrator::class)->calculate($setup)];
}

/** @return array{0: BeamCalculationSetup, 1: BeamCalculationResponse} */
function compliantBeamCalculationNoteSource(): array
{
    $payload = beamCalculationNotePayload();
    $payload['geometry']['height'] = 800;
    $payload['loads']['permanent']['additionalPermanentLoad'] = 1;
    $payload['loads']['variable']['characteristicLoad'] = 1;
    $setup = app(BeamCalculationInputFactory::class)->fromPayload($payload);

    return [$setup, app(BeamCalculationOrchestrator::class)->calculate($setup)];
}

/** @return array{0: BeamCalculationSetup, 1: BeamCalculationResponse} */
function cantileverBeamCalculationNoteSource(): array
{
    $payload = beamCalculationNotePayload();
    $payload['configuration']['submodule'] = 'BEAM_CANTILEVER_RECTANGULAR';
    $payload['configuration']['supportSystem'] = 'CANTILEVER';
    $setup = app(BeamCalculationInputFactory::class)->fromPayload($payload);

    return [$setup, app(BeamCalculationOrchestrator::class)->calculate($setup)];
}

function beamCalculationNote(array $source): CalculationNoteDocument
{
    return app(BeamCalculationNoteMapper::class)->map(
        $source[0],
        $source[1],
        new DateTimeImmutable('2026-09-16T10:00:00+02:00'),
    );
}

it('maps the real beam pipeline into the common document without recalculating values', function () {
    $source = beamCalculationNoteSource();
    [$setup, $result] = $source;
    $document = beamCalculationNote($source);

    expect($document->metadata->title)->toBe('Note de calcul — Poutre rectangulaire simplement appuyée')
        ->and($document->metadata->calculationType)->toBe($setup->configuration->elementType)
        ->and($document->metadata->designCodeProfile)->toBe($setup->configuration->designCodeProfile->value)
        ->and($document->geometry->items[0]->value)->toBe(6500.0)
        ->and($document->geometry->items[0]->unit)->toBe('mm')
        ->and($document->geometry->items[3]->value)->toBe($result->details->assumptions['cover']->cNom)
        ->and($document->geometry->items[4]->value)->toBe($result->summary->effectiveDepth)
        ->and($document->materials->items[0]->value)->toBe('C30/37')
        ->and($document->materials->items[1]->value)->toBe('B500B')
        ->and($document->materials->items[2]->value)->toBe('XC1')
        ->and($document->loads->items[1]->value)->toBe($result->details->combinations['characteristicActions']->permanent->selfWeight->characteristicLineLoad)
        ->and($document->loads->items[2]->value)->toBe($result->details->combinations['characteristicActions']->permanent->additionalPermanentLoad)
        ->and($document->loads->items[3]->value)->toBe($result->details->combinations['characteristicActions']->permanent->totalPermanentLoad)
        ->and($document->loads->items[4]->value)->toBe($result->details->combinations['characteristicActions']->variable->characteristicLoad)
        ->and($document->combinations?->items[0]->value)->toBe($result->details->combinations['ultimate']->designLineLoad)
        ->and($document->internalForces?->items[0]->value)->toBe($result->details->internalForces['bendingMoments']->ultimate->maximumMoment)
        ->and($document->internalForces?->items[1]->value)->toBe($result->details->internalForces['shearForces']->ultimate->maximumAbsoluteShear)
        ->and($document->finalStatus->summary[4]->value)->toBe($result->summary->designBendingMoment)
        ->and($document->finalStatus->summary[7]->value)->toBe($result->summary->requiredLongitudinalReinforcementArea);
});

it('transfers individual verification and final statuses exactly as produced by Beam', function () {
    $source = beamCalculationNoteSource();
    [, $result] = $source;
    $document = beamCalculationNote($source);
    $crack = collect($document->verifications)->firstWhere('type', 'CRACK');
    $deflection = collect($document->verifications)->firstWhere('type', 'DEFLECTION');

    expect($result->summary->status)->toBe(BeamVerificationStatus::NOT_COMPLIANT)
        ->and($document->finalStatus->status)->toBe($result->summary->status)
        ->and($document->finalStatus->governingVerificationType)->toBe($result->summary->governingVerificationType)
        ->and($document->finalStatus->governingUtilization)->toBe($result->summary->utilization)
        ->and($crack->status)->toBe($result->verifications->crackVerification->status)
        ->and($crack->governingValue)->toBe($result->details->serviceability['crack']->crackWidth)
        ->and($crack->limitValue)->toBe($result->details->serviceability['crack']->crackWidthLimit)
        ->and($deflection->method)->toBe('SIMPLIFIED_SPAN_DEPTH')
        ->and($deflection->details[1]->value)->toBe($result->details->serviceability['deflection']->actualSpanDepthRatio)
        ->and($deflection->details[2]->value)->toBe($result->details->serviceability['deflection']->allowableSpanDepthRatio)
        ->and($document->warnings)->toBe($result->details->warnings)
        ->and($document->warnings)->toContain('NO_EXPLICIT_DEFLECTION_CALCULATED');
});

it('preserves a compliant Beam status without deriving it from the mapped values', function () {
    $source = compliantBeamCalculationNoteSource();
    [, $result] = $source;
    $document = beamCalculationNote($source);

    expect($result->summary->status)->toBe(BeamVerificationStatus::COMPLIANT)
        ->and($document->finalStatus->status)->toBe(BeamVerificationStatus::COMPLIANT)
        ->and($document->finalStatus->governingVerificationType)->toBe($result->summary->governingVerificationType)
        ->and($document->finalStatus->governingUtilization)->toBe($result->summary->utilization);
});

it('distinguishes proposed reinforcement from supplied verification reinforcement', function () {
    $design = beamCalculationNote(beamCalculationNoteSource());
    $verification = beamCalculationNote(beamCalculationNoteSource('VERIFICATION'));

    expect($design->assumptions->items[0]->value)->toBe(BeamCalculationMode::DESIGN->value)
        ->and($design->reinforcement[0]->label)->toBe('Ferraillage longitudinal proposé — Partie inférieure')
        ->and($design->reinforcement[0]->designation)->toBeNull()
        ->and($design->reinforcement[0]->count)->toBe(2)
        ->and($design->reinforcement[0]->diameter)->toBe(16.0)
        ->and($verification->assumptions->items[0]->value)->toBe(BeamCalculationMode::VERIFICATION->value)
        ->and($verification->reinforcement[0]->label)->toBe('Ferraillage longitudinal fourni — Partie inférieure')
        ->and($verification->reinforcement[0]->count)->toBe(4)
        ->and($verification->reinforcement[0]->diameter)->toBe(12.0);
});

it('keeps the technical values while exposing French labels for note assumptions', function () {
    $document = beamCalculationNote(beamCalculationNoteSource());

    expect($document->assumptions->items[0]->value)->toBe('DESIGN')
        ->and($document->assumptions->items[0]->displayValue)->toBe('Dimensionnement')
        ->and($document->assumptions->items[1]->displayValue)->toBe('Poutre rectangulaire simplement appuyée')
        ->and($document->assumptions->items[2]->displayValue)->toBe('Béton armé')
        ->and($document->assumptions->items[3]->displayValue)->toBe('Rectangulaire')
        ->and($document->assumptions->items[4]->displayValue)->toBe('Simplement appuyée')
        ->and($document->assumptions->items[5]->displayValue)->toBe('Uniformément répartie')
        ->and($document->assumptions->items[7]->displayValue)->toBe('Persistante / transitoire')
        ->and($document->assumptions->items[8]->displayValue)->toBe('Automatique');
});

it('maps a cantilever into the shared note with fixed-end actions, top reinforcement and visible limitations', function () {
    $source = cantileverBeamCalculationNoteSource();
    [, $result] = $source;
    $document = beamCalculationNote($source);
    $shear = collect($document->verifications)->firstWhere('type', 'SHEAR');
    $crack = collect($document->verifications)->firstWhere('type', 'CRACK');

    expect($document->metadata->title)->toBe('Note de calcul — Poutre rectangulaire en console')
        ->and($document->assumptions->items[1]->value)->toBe('BEAM_CANTILEVER_RECTANGULAR')
        ->and($document->assumptions->items[1]->displayValue)->toBe('Poutre rectangulaire en console')
        ->and($document->assumptions->items[4]->value)->toBe('CANTILEVER')
        ->and($document->assumptions->items[4]->displayValue)->toBe('Console')
        ->and($document->assumptions->items[6]->value)->toContain('encastrement à une extrémité')
        ->and($document->internalForces?->items[0]->label)->toBe('Moment ELU MEd à l’encastrement')
        ->and($document->internalForces?->items[0]->value)->toBe($result->summary->designBendingMoment)
        ->and($document->internalForces?->items[0]->value)->toBeLessThan(0)
        ->and($document->internalForces?->items[1]->label)->toBe('Effort tranchant ELU VEd à l’encastrement')
        ->and($document->internalForces?->items[1]->value)->toBe($result->summary->designShearForce)
        ->and($document->reinforcement[0]->label)->toBe('Ferraillage longitudinal proposé — Partie supérieure')
        ->and($document->reinforcement[0]->details[0]->displayValue)->toBe('Partie supérieure')
        ->and($shear->status)->toBe($result->verifications->shearVerification->status)
        ->and($shear->details[2]->value)->toBe('CANTILEVER_FIXED_END_CRITICAL_SECTION_NOT_MODELLED')
        ->and($crack->status)->toBe($result->verifications->crackVerification->status)
        ->and($document->warnings)->toBe($result->details->warnings)
        ->and($document->warnings)->toContain('CANTILEVER_FIXED_END_CRITICAL_SECTION_NOT_MODELLED');
});

it('renders cantilever-specific labels and warnings through the common PDF template', function () {
    $html = view('pdf.calculation-note', [
        'document' => beamCalculationNote(cantileverBeamCalculationNoteSource()),
        'presentation' => app(CalculationNotePdfPresentation::class),
    ])->render();

    expect($html)->toContain('Poutre rectangulaire en console')
        ->and($html)->toContain('Console')
        ->and($html)->toContain('Moment ELU MEd à l’encastrement')
        ->and($html)->toContain('Effort tranchant ELU VEd à l’encastrement')
        ->and($html)->toContain('Partie supérieure')
        ->and($html)->toContain('Section critique de cisaillement à l’encastrement non modélisée');
});

it('renders French configuration labels in the beam PDF template', function () {
    $html = view('pdf.calculation-note', [
        'document' => beamCalculationNote(beamCalculationNoteSource()),
        'presentation' => app(CalculationNotePdfPresentation::class),
    ])->render();

    expect($html)->toContain('Dimensionnement')
        ->and($html)->toContain('Béton armé')
        ->and($html)->toContain('Simplement appuyée')
        ->and($html)->toContain('Aucune flèche explicite calculée')
        ->and($html)->not->toContain('REINFORCED_CONCRETE');
});

it('generates a real PDF from a Beam calculation through the shared renderer', function () {
    $document = beamCalculationNote(beamCalculationNoteSource());
    $rendered = app(CalculationNoteRenderer::class)->render($document);

    expect($rendered->content)->toStartWith('%PDF')
        ->and(strlen($rendered->content))->toBeGreaterThan(1_000)
        ->and($rendered->mimeType)->toBe('application/pdf');
});
