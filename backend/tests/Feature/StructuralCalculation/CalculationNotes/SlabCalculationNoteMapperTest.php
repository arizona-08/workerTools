<?php

use App\StructuralCalculation\Beams\BeamVerificationStatus;
use App\StructuralCalculation\CalculationNotes\CalculationNoteDocument;
use App\StructuralCalculation\CalculationNotes\Pdf\CalculationNotePdfPresentation;
use App\StructuralCalculation\CalculationNotes\Pdf\CalculationNoteRenderer;
use App\StructuralCalculation\CalculationNotes\SlabCalculationNoteMapper;
use App\StructuralCalculation\Slabs\SlabCalculationInput;
use App\StructuralCalculation\Slabs\SlabCalculationInputFactory;
use App\StructuralCalculation\Slabs\SlabCalculationOrchestrator;
use App\StructuralCalculation\Slabs\SlabCalculationResult;

function slabCalculationNotePayload(float $span = 5000, float $thickness = 200, float $finishes = 1.5, float $partitions = 1, float $otherPermanent = 0.5, float $imposedLoad = 2): array
{
    return [
        'configuration' => [
            'elementType' => 'SLAB',
            'slabType' => 'SOLID',
            'spanningSystem' => 'ONE_WAY',
            'structuralSystem' => 'SINGLE_SPAN_SIMPLY_SUPPORTED_ON_OPPOSITE_SIDES',
            'loadModel' => 'VERTICAL_UNIFORMLY_DISTRIBUTED',
            'materialType' => 'REINFORCED_CONCRETE',
            'designCodeProfile' => 'NF_EN_1992_1_1_2005_FR',
            'designSituation' => 'PERSISTENT_TRANSIENT',
        ],
        'geometry' => ['effectiveSpan' => $span, 'thickness' => $thickness],
        'materials' => ['concreteClass' => 'C30/37', 'steelGrade' => 'B500B', 'exposureClass' => 'XC1'],
        'loads' => ['finishes' => $finishes, 'partitions' => $partitions, 'otherPermanent' => $otherPermanent, 'imposedLoad' => $imposedLoad],
    ];
}

/** @return array{0: SlabCalculationInput, 1: SlabCalculationResult} */
function slabCalculationNoteSource(float $imposedLoad = 2): array
{
    $input = app(SlabCalculationInputFactory::class)->fromPayload(slabCalculationNotePayload(imposedLoad: $imposedLoad));

    return [$input, app(SlabCalculationOrchestrator::class)->calculate($input)];
}

function slabCalculationNote(array $source): CalculationNoteDocument
{
    return app(SlabCalculationNoteMapper::class)->map(
        $source[0],
        $source[1],
        new DateTimeImmutable('2026-09-16T10:00:00+02:00'),
    );
}

it('maps the real compliant slab pipeline into the common document without recalculation', function () {
    $source = slabCalculationNoteSource();
    [$input, $result] = $source;
    $document = slabCalculationNote($source);

    expect($result->status)->toBe(BeamVerificationStatus::COMPLIANT)
        ->and($document->metadata->title)->toBe('Note de calcul — Dalle unidirectionnelle en béton armé')
        ->and($document->metadata->calculationType)->toBe($input->configuration->elementType)
        ->and($document->metadata->designCodeProfile)->toBe($input->configuration->designCodeProfile->value)
        ->and($document->geometry->items[0]->value)->toBe(5000.0)
        ->and($document->geometry->items[1]->value)->toBe(200.0)
        ->and($document->geometry->items[2]->value)->toBe(1.0)
        ->and($document->geometry->items[2]->unit)->toBe('m')
        ->and($document->geometry->items[3]->value)->toBe(24.0)
        ->and($document->geometry->items[4]->value)->toBe(169.0)
        ->and($document->materials->items[0]->value)->toBe('C30/37')
        ->and($document->materials->items[1]->value)->toBe('B500B')
        ->and($document->materials->items[2]->value)->toBe('XC1')
        ->and($document->loads->items[0]->value)->toBe($result->details->characteristicActions->selfWeight)
        ->and($document->loads->items[4]->value)->toBe($result->details->characteristicActions->permanentTotal)
        ->and($document->loads->items[5]->value)->toBe($result->details->characteristicActions->imposedLoad)
        ->and($document->combinations?->items[0]->value)->toBe($result->details->combinations['uls']->value)
        ->and($document->internalForces?->items[0]->value)->toBe($result->details->internalForces['internalForces']->uls->maximumMoment)
        ->and($document->internalForces?->items[1]->value)->toBe($result->details->internalForces['internalForces']->uls->maximumShear);
});

it('maps flexure, main and secondary reinforcements, ELS and warnings with their existing units', function () {
    $source = slabCalculationNoteSource();
    [, $result] = $source;
    $document = slabCalculationNote($source);
    $flexure = collect($document->verifications)->firstWhere('type', 'FLEXURE');
    $crack = collect($document->verifications)->firstWhere('type', 'CRACK');
    $deflection = collect($document->verifications)->firstWhere('type', 'DEFLECTION');
    $secondary = $result->details->secondaryReinforcement['proposal'];

    expect($flexure->details[5]->value)->toBe($result->details->flexure['final']->requiredReinforcementArea)
        ->and($flexure->details[5]->unit)->toBe('mm²/m')
        ->and($flexure->details[6]->value)->toBe($result->details->flexure['final']->minimumReinforcementArea)
        ->and($flexure->details[7]->value)->toBe($result->details->flexure['final']->designReinforcementArea)
        ->and($document->reinforcement[0]->designation)->toBe('HA14 / 250 mm')
        ->and($document->reinforcement[0]->providedArea)->toBe($result->summary->mainReinforcement->providedAreaPerMeter)
        ->and($document->reinforcement[0]->unit)->toBe('mm²/m')
        ->and($document->reinforcement[1]->designation)->toBe('HA8 / 300 mm')
        ->and($document->reinforcement[1]->requiredArea)->toBe($secondary->minimumRequiredAreaPerMeter)
        ->and($document->reinforcement[1]->providedArea)->toBe($secondary->proposal->providedAreaPerMeter)
        ->and($crack->status)->toBe($result->verifications[3]->status)
        ->and($crack->details[0]->value)->toBe($result->details->serviceability['crack']->crackWidth)
        ->and($crack->details[1]->value)->toBe($result->details->serviceability['crack']->crackWidthLimit)
        ->and($deflection->details[1]->value)->toBe($result->details->serviceability['deflection']->actualSpanDepthRatio)
        ->and($deflection->details[2]->value)->toBe($result->details->serviceability['deflection']->allowableSpanDepthRatio)
        ->and($document->warnings)->toBe($result->details->warnings)
        ->and($document->warnings)->toContain('NO_EXPLICIT_DEFLECTION_CALCULATED');
});

it('exposes French display labels for slab configuration assumptions', function () {
    $document = slabCalculationNote(slabCalculationNoteSource());

    expect($document->assumptions->items[0]->displayValue)->toBe('Dalle pleine')
        ->and($document->assumptions->items[1]->displayValue)->toBe('Unidirectionnelle')
        ->and($document->assumptions->items[2]->displayValue)->toBe('Une travée, simplement appuyée sur deux côtés opposés')
        ->and($document->assumptions->items[3]->displayValue)->toBe('Charges verticales uniformément réparties')
        ->and($document->assumptions->items[4]->displayValue)->toBe('Béton armé')
        ->and($document->assumptions->items[5]->displayValue)->toBe('Persistante / transitoire');
});

it('preserves a non-compliant slab result and its governing verification without deriving a new status', function () {
    $source = slabCalculationNoteSource(5);
    [, $result] = $source;
    $document = slabCalculationNote($source);

    expect($result->status)->toBe(BeamVerificationStatus::NOT_COMPLIANT)
        ->and($document->finalStatus->status)->toBe($result->status)
        ->and($document->finalStatus->governingVerificationType)->toBe($result->summary->governingVerificationType)
        ->and($document->finalStatus->governingUtilization)->toBe($result->summary->utilization)
        ->and(collect($document->verifications)->firstWhere('type', 'DEFLECTION')->status)->toBe(BeamVerificationStatus::NOT_COMPLIANT);
});

it('generates a real slab PDF through the shared renderer and template', function () {
    $document = slabCalculationNote(slabCalculationNoteSource());
    $html = view('pdf.calculation-note', ['document' => $document, 'presentation' => app(CalculationNotePdfPresentation::class)])->render();
    $rendered = app(CalculationNoteRenderer::class)->render($document);

    expect($html)->toContain('Dalle unidirectionnelle')
        ->and($html)->toContain('Dalle pleine')
        ->and($html)->toContain('Charges verticales uniformément réparties')
        ->and($html)->not->toContain('VERTICAL_UNIFORMLY_DISTRIBUTED')
        ->and($html)->toContain('Armatures principales')
        ->and($html)->toContain('Armatures secondaires')
        ->and($html)->toContain('ELS — fissuration')
        ->and($html)->toContain('ELS — flèche simplifiée')
        ->and($rendered->content)->toStartWith('%PDF')
        ->and($rendered->mimeType)->toBe('application/pdf')
        ->and(strlen($rendered->content))->toBeGreaterThan(1_000);
});
