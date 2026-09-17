<?php

namespace App\Http\Controllers;

use App\StructuralCalculation\Beams\BeamCalculationInputFactory;
use App\StructuralCalculation\Beams\BeamCalculationOrchestrator;
use App\StructuralCalculation\CalculationFailureMessage;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LogicException;

final class BeamCalculationController extends Controller
{
    public function __invoke(Request $request, BeamCalculationInputFactory $inputFactory, BeamCalculationOrchestrator $orchestrator, CalculationFailureMessage $failureMessage): JsonResponse
    {
        try {
            return response()->json($orchestrator->calculate($inputFactory->fromPayload($request->all())));
        } catch (DomainException|LogicException $exception) {
            return response()->json(['message' => $failureMessage->for($exception), 'reason' => $exception->getMessage()], 422);
        }
    }
}
