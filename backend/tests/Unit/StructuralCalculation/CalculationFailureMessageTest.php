<?php

use App\StructuralCalculation\CalculationFailureMessage;

it('turns beam and slab rejection codes into actionable user messages', function (string $reason, string $message) {
    expect(app(CalculationFailureMessage::class)->for(new DomainException($reason)))->toBe($message);
})->with([
    'unsupported configuration' => ['UNSUPPORTED_SUPPORT_SYSTEM', 'Cette configuration ou ce matériau n’est pas pris en charge par le calculateur actuel.'],
    'unsupported exposure class' => ['UNSUPPORTED_EXPOSURE_CLASS', 'La classe d’exposition sélectionnée est absente, invalide ou non prise en charge.'],
    'beam variable load' => ['INVALID_CHARACTERISTIC_VARIABLE_LOAD', 'La charge d’exploitation doit être renseignée avec une valeur valide ; réduisez-la si la configuration dépasse le domaine de calcul.'],
    'slab imposed load' => ['INVALID_IMPOSED_LOAD', 'La charge d’exploitation doit être renseignée avec une valeur valide ; réduisez-la si la configuration dépasse le domaine de calcul.'],
    'permanent load' => ['INVALID_FINISHES', 'Les charges permanentes doivent être renseignées avec des valeurs valides.'],
    'beam geometry' => ['INVALID_WIDTH', 'Les dimensions de la section doivent être renseignées avec des valeurs strictement positives.'],
    'slab geometry' => ['INVALID_THICKNESS', 'Les dimensions de la section doivent être renseignées avec des valeurs strictement positives.'],
    'span' => ['INVALID_EFFECTIVE_SPAN', 'La portée de calcul doit être renseignée avec une valeur strictement positive.'],
    'flexural capacity' => ['INVALID_SINGLY_REINFORCED_DOMAIN', 'Le moment de flexion est trop important pour une section simplement armée dans le périmètre de calcul actuel.'],
    'shear capacity' => ['MAXIMUM_SHEAR_RESISTANCE_EXCEEDED', 'L’effort tranchant est trop important pour cette section dans le périmètre de calcul actuel.'],
    'bar spacing' => ['LONGITUDINAL_SPACING_EXCEEDED', 'Les armatures ne peuvent pas être disposées dans la section en respectant les espacements requis.'],
    'provided reinforcement' => ['INSUFFICIENT_LONGITUDINAL_REINFORCEMENT', 'Les armatures fournies sont insuffisantes pour cette configuration.'],
    'unsupported method' => ['CALCULATION_METHOD_NOT_SUPPORTED', 'Cette configuration nécessite une méthode de calcul qui n’est pas encore prise en charge par WorkerTools.'],
    'fallback' => ['UNEXPECTED_DOMAIN_FAILURE', 'Cette configuration ne peut pas être calculée dans le périmètre actuel. Vérifiez les données saisies et les hypothèses du calcul.'],
]);
