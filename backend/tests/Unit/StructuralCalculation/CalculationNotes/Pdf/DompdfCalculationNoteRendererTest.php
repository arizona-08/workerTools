<?php

use App\StructuralCalculation\Beams\BeamVerificationStatus;
use App\StructuralCalculation\CalculationNotes\Pdf\CalculationNotePdfPresentation;
use App\StructuralCalculation\CalculationNotes\Pdf\CalculationNoteRenderer;
use App\StructuralCalculation\CalculationNotes\Pdf\CalculationNoteRenderingException;
use App\StructuralCalculation\CalculationNotes\Pdf\DompdfCalculationNoteRenderer;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Psr\Log\LoggerInterface;
use Tests\Fixtures\CalculationNoteDocumentFixture;
use Tests\TestCase;

uses(TestCase::class);

it('renders a generic document as an in-memory A4 PDF with its response metadata', function () {
    $rendered = app(CalculationNoteRenderer::class)->render(CalculationNoteDocumentFixture::make());

    expect($rendered->content)->toStartWith('%PDF')
        ->and(strlen($rendered->content))->toBeGreaterThan(1_000)
        ->and($rendered->mimeType)->toBe('application/pdf')
        ->and($rendered->filename)->toBe('worker-tools-calculation-note.pdf');
});

it('keeps UTF-8 formulas, warnings and limitations renderable', function () {
    $document = CalculationNoteDocumentFixture::make();
    $html = view('pdf.calculation-note', [
        'document' => $document,
        'presentation' => app(CalculationNotePdfPresentation::class),
    ])->render();

    expect($html)->toContain('μ = MEd / (b · d² · fcd)')
        ->and($html)->toContain('σ ≤ fyd ; φ γ ψ ξ ρ ε')
        ->and($html)->toContain('Avertissements')
        ->and($html)->toContain('Limitations')
        ->and(app(CalculationNoteRenderer::class)->render($document)->content)->toStartWith('%PDF');
});

it('omits unavailable optional sections instead of inventing zero-valued content', function () {
    $html = view('pdf.calculation-note', [
        'document' => CalculationNoteDocumentFixture::make(withOptionalSections: false),
        'presentation' => app(CalculationNotePdfPresentation::class),
    ])->render();

    expect($html)->not->toContain('<h2>Combinaisons</h2>')
        ->and($html)->not->toContain('<h2>Sollicitations</h2>');
});

it('renders several pages without dropping the final structured content', function () {
    $rendered = app(CalculationNoteRenderer::class)->render(
        CalculationNoteDocumentFixture::make(extraGeometryItems: 180),
    );

    expect(substr_count($rendered->content, '/Type /Page'))->toBeGreaterThanOrEqual(2)
        ->and($rendered->content)->toStartWith('%PDF');
});

it('renders non-compliant and unsupported-method statuses without recomputing them', function (BeamVerificationStatus $status, string $label) {
    $document = CalculationNoteDocumentFixture::make($status);
    $html = view('pdf.calculation-note', [
        'document' => $document,
        'presentation' => app(CalculationNotePdfPresentation::class),
    ])->render();

    expect($html)->toContain("Statut final : $label")
        ->and(app(CalculationNoteRenderer::class)->render($document)->content)->toStartWith('%PDF');
})->with([
    [BeamVerificationStatus::NOT_COMPLIANT, 'Non conforme'],
    [BeamVerificationStatus::CALCULATION_METHOD_NOT_SUPPORTED, 'Méthode non prise en charge'],
]);

it('logs rendering failures and exposes a domain-specific exception', function () {
    $views = Mockery::mock(ViewFactory::class);
    $views->shouldReceive('make')->once()->andThrow(new RuntimeException('Blade failure'));
    $logger = Mockery::mock(LoggerInterface::class);
    $logger->shouldReceive('error')->once()->withArgs(function (string $message, array $context): bool {
        return $message === 'Calculation note PDF rendering failed.'
            && $context['calculationType'] === 'BEAM'
            && $context['exception'] === RuntimeException::class;
    });

    $renderer = new DompdfCalculationNoteRenderer(
        $views,
        new CalculationNotePdfPresentation,
        $logger,
    );

    expect(fn () => $renderer->render(CalculationNoteDocumentFixture::make()))
        ->toThrow(CalculationNoteRenderingException::class, 'Unable to render calculation note PDF.');
});
