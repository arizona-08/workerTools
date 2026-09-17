<?php

namespace App\StructuralCalculation\Materials\Concrete;

enum ConcreteStrengthClass: string
{
    case C20_25 = 'C20/25';
    case C25_30 = 'C25/30';
    case C30_37 = 'C30/37';
}
