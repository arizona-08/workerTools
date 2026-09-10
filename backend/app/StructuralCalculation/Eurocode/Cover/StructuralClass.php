<?php

namespace App\StructuralCalculation\Eurocode\Cover;

enum StructuralClass: int
{
    case S1 = 1;
    case S2 = 2;
    case S3 = 3;
    case S4 = 4;
    case S5 = 5;
    case S6 = 6;

    public function withModifier(int $modifier): ?self
    {
        return self::tryFrom($this->value + $modifier);
    }

    public function label(): string
    {
        return 'S'.$this->value;
    }
}
