<?php

namespace App\StructuralCalculation\Beams;

/** Analyse du cisaillement du modèle statique Poutre V1, sans résistance de section. */
final readonly class BeamShearForceResult
{
    public const EFFECTIVE_SPAN_UNIT = 'm';

    public function __construct(
        public float $effectiveSpan,
        public BeamSupportSystem $supportSystem,
        public BeamLoadModel $loadModel,
        public float $shearCoefficient,
        public BeamShearForce $ultimate,
        public BeamShearForce $characteristic,
        public BeamShearForce $frequent,
        public BeamShearForce $quasiPermanent,
    ) {}
}
