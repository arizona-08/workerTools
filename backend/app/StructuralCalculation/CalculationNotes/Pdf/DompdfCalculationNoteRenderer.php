<?php

namespace App\StructuralCalculation\CalculationNotes\Pdf;

use App\StructuralCalculation\CalculationNotes\CalculationNoteDocument;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Psr\Log\LoggerInterface;
use Throwable;

/** Rendu A4 en mémoire du contrat PDF-01, sans connaître les calculs Beam ou Slab. */
final readonly class DompdfCalculationNoteRenderer implements CalculationNoteRenderer
{
    public function __construct(
        private ViewFactory $views,
        private CalculationNotePdfPresentation $presentation,
        private LoggerInterface $logger,
    ) {}

    public function render(CalculationNoteDocument $document): RenderedCalculationNote
    {
        try {
            $html = $this->views->make('pdf.calculation-note', [
                'document' => $document,
                'presentation' => $this->presentation,
            ])->render();

            $content = Pdf::loadHtml($html)
                ->setPaper('a4', 'portrait')
                ->setOption('defaultFont', 'DejaVu Sans')
                ->setOption('isHtml5ParserEnabled', true)
                ->output();

            return new RenderedCalculationNote($content);
        } catch (Throwable $exception) {
            $this->logger->error('Calculation note PDF rendering failed.', [
                'exception' => $exception::class,
                'calculationType' => $document->metadata->calculationType->value,
            ]);

            throw new CalculationNoteRenderingException('Unable to render calculation note PDF.', previous: $exception);
        }
    }
}
