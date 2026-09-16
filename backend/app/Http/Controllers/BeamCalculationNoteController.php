<?php

namespace App\Http\Controllers;

use App\StructuralCalculation\Beams\BeamCalculationInputFactory;
use App\StructuralCalculation\Beams\BeamCalculationOrchestrator;
use App\StructuralCalculation\CalculationFailureMessage;
use App\StructuralCalculation\CalculationNotes\BeamCalculationNoteMapper;
use App\StructuralCalculation\CalculationNotes\Pdf\CalculationNoteRenderer;
use App\StructuralCalculation\CalculationNotes\Pdf\CalculationNoteRenderingException;
use DateTimeImmutable;
use DomainException;
use Illuminate\Http\Request;
use LogicException;
use Symfony\Component\HttpFoundation\Response;

final class BeamCalculationNoteController extends Controller
{
    public function __invoke(Request $request, BeamCalculationInputFactory $inputs, BeamCalculationOrchestrator $calculations, BeamCalculationNoteMapper $mapper, CalculationNoteRenderer $renderer, CalculationFailureMessage $failureMessage): Response
    {
        try {
            $input = $inputs->fromPayload($request->all());
            $document = $mapper->map($input, $calculations->calculate($input), new DateTimeImmutable);
            $pdf = $renderer->render($document);

            return response($pdf->content, 200, [
                'Content-Type' => $pdf->mimeType,
                'Content-Disposition' => 'attachment; filename="note-calcul-poutre.pdf"',
            ]);
        } catch (DomainException|LogicException $exception) {
            return response()->json(['message' => $failureMessage->for($exception), 'reason' => $exception->getMessage()], 422);
        } catch (CalculationNoteRenderingException) {
            return response()->json(['message' => 'La note de calcul n’a pas pu être générée. Veuillez réessayer.'], 500);
        }
    }
}
