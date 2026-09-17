<?php

namespace App\StructuralCalculation\CalculationNotes\Pdf;

/** PDF généré en mémoire ; aucun fichier n’est persisté. */
final readonly class RenderedCalculationNote
{
    public function __construct(
        public string $content,
        public string $mimeType = 'application/pdf',
        public string $filename = 'worker-tools-calculation-note.pdf',
    ) {}
}
