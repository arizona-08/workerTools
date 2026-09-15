<?php

namespace App\StructuralCalculation\Eurocode\Serviceability;

/** Section rectangulaire fissurée élastique transformée, indépendante de l'élément structural. */
final readonly class CrackedElasticSectionResult
{
    public function __construct(
        public float $modularRatio,
        public float $neutralAxisDepth,
        public float $secondMomentOfArea,
    ) {}
}
