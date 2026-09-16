<?php

namespace App\Http\Controllers;

use App\StructuralCalculation\CalculationFailureMessage;
use App\StructuralCalculation\CalculationNotes\Pdf\CalculationNoteRenderer;
use App\StructuralCalculation\CalculationNotes\Pdf\CalculationNoteRenderingException;
use App\StructuralCalculation\CalculationNotes\SlabCalculationNoteMapper;
use App\StructuralCalculation\Slabs\SlabCalculationInputFactory;
use App\StructuralCalculation\Slabs\SlabCalculationOrchestrator;
use DateTimeImmutable;
use DomainException;
use Illuminate\Http\Request;
use LogicException;
use Symfony\Component\HttpFoundation\Response;

final class SlabCalculationNoteController extends Controller
{
    public function __invoke(Request $request, SlabCalculationInputFactory $inputs, SlabCalculationOrchestrator $calculations, SlabCalculationNoteMapper $mapper, CalculationNoteRenderer $renderer, CalculationFailureMessage $failureMessage): Response
    {
        try {
            $input = $inputs->fromPayload($request->all());
            $document = $mapper->map($input, $calculations->calculate($input), new DateTimeImmutable);
            $pdf = $renderer->render($document);

            return response($pdf->content, 200, [
                'Content-Type' => $pdf->mimeType,
                'Content-Disposition' => 'attachment; filename="note-calcul-dalle.pdf"',
            ]);
        } catch (DomainException|LogicException $exception) {
            return response()->json(['message' => $failureMessage->for($exception), 'reason' => $exception->getMessage()], 422);
        } catch (CalculationNoteRenderingException) {
            return response()->json(['message' => 'La note de calcul n’a pas pu être générée. Veuillez réessayer.'], 500);
        }
    }
}
