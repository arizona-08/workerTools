<?php

namespace App\StructuralCalculation\Beams;

/** Analyse en flexion du modèle statique Poutre MVP, sans vérification de section. */
final readonly class BeamBendingMomentResult
{
    public const EFFECTIVE_SPAN_UNIT = 'm';

    public function __construct(
        public float $effectiveSpan,
        public BeamSupportSystem $supportSystem,
        public BeamLoadModel $loadModel,
        public float $momentCoefficient,
        public float $maximumMomentPosition,
        public BeamBendingMoment $ultimate,
        public BeamBendingMoment $characteristic,
        public BeamBendingMoment $frequent,
        public BeamBendingMoment $quasiPermanent,
    ) {}
}
