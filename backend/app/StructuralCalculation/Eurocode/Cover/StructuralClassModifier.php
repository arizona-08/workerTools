<?php

namespace App\StructuralCalculation\Eurocode\Cover;

final readonly class StructuralClassModifier
{
    public function __construct(public string $rule, public int $value) {}
}
