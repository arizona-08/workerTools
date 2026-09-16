<?php

namespace App\StructuralCalculation;

use Throwable;

/**
 * Traduit les codes de rejet du domaine en une cause exploitable dans l'UI.
 * Le code brut reste renvoyé par l'API dans `reason` pour le diagnostic, mais
 * ne constitue pas un message destiné à l'utilisateur final.
 */
final class CalculationFailureMessage
{
    public function for(Throwable $exception): string
    {
        return $this->forReason($exception->getMessage());
    }

    private function forReason(string $reason): string
    {
        return match (true) {
            $reason === 'NO_VALID_LONGITUDINAL_REINFORCEMENT_CANDIDATE' => 'Aucune disposition d’armatures longitudinales compatible avec cette section et les armatures requises n’a été trouvée.',
            $reason === 'NO_VALID_STIRRUP_CANDIDATE' => 'Aucune disposition d’étriers compatible avec cette section et l’effort tranchant n’a été trouvée.',
            $reason === 'MAXIMUM_SHEAR_RESISTANCE_EXCEEDED', $reason === 'SHEAR_RESISTANCE_INSUFFICIENT' => 'L’effort tranchant est trop important pour cette section dans le périmètre de calcul actuel.',
            $reason === 'INVALID_SINGLY_REINFORCED_DOMAIN', $reason === 'NEUTRAL_AXIS_BEYOND_EFFECTIVE_DEPTH', $reason === 'INVALID_NEUTRAL_AXIS_RADICAND' => 'Le moment de flexion est trop important pour une section simplement armée dans le périmètre de calcul actuel.',
            str_contains($reason, 'INSUFFICIENT_HORIZONTAL_SPACE'), str_contains($reason, 'SPACING_EXCEEDED') => 'Les armatures ne peuvent pas être disposées dans la section en respectant les espacements requis.',
            str_contains($reason, 'INSUFFICIENT_LONGITUDINAL_REINFORCEMENT'), str_contains($reason, 'INSUFFICIENT_REINFORCEMENT_PER_LENGTH') => 'Les armatures fournies sont insuffisantes pour cette configuration.',
            $reason === 'CALCULATION_METHOD_NOT_SUPPORTED' => 'Cette configuration nécessite une méthode de calcul qui n’est pas encore prise en charge par WorkerTools.',
            str_contains($reason, 'VARIABLE_LOAD') || str_contains($reason, 'IMPOSED_LOAD') => 'La charge d’exploitation doit être renseignée avec une valeur valide ; réduisez-la si la configuration dépasse le domaine de calcul.',
            str_contains($reason, 'PERMANENT_LOAD') || str_contains($reason, 'FINISHES') || str_contains($reason, 'PARTITIONS') || str_contains($reason, 'OTHER_PERMANENT') => 'Les charges permanentes doivent être renseignées avec des valeurs valides.',
            str_contains($reason, 'EFFECTIVE_SPAN') => 'La portée de calcul doit être renseignée avec une valeur strictement positive.',
            str_contains($reason, 'WIDTH') || str_contains($reason, 'HEIGHT') || str_contains($reason, 'THICKNESS') || str_contains($reason, 'EFFECTIVE_DEPTH') => 'Les dimensions de la section doivent être renseignées avec des valeurs strictement positives.',
            str_contains($reason, 'CONCRETE_CLASS') => 'La classe de béton sélectionnée est absente, invalide ou non prise en charge.',
            str_contains($reason, 'STEEL_GRADE') => 'La nuance d’acier sélectionnée est absente, invalide ou non prise en charge.',
            str_contains($reason, 'EXPOSURE_CLASS') => 'La classe d’exposition sélectionnée est absente, invalide ou non prise en charge.',
            str_starts_with($reason, 'UNSUPPORTED_') => 'Cette configuration ou ce matériau n’est pas pris en charge par le calculateur actuel.',
            str_contains($reason, 'REINFORCEMENT') || str_contains($reason, 'TENSION_BAR') || str_contains($reason, 'BAR_LAYOUT') => 'Le ferraillage renseigné est incomplet ou incompatible avec le calcul demandé.',
            str_contains($reason, 'CONFIGURATION') || str_contains($reason, 'MISSING_') || str_contains($reason, 'INVALID_') => 'Une donnée de configuration ou de saisie est invalide ou manquante.',
            default => 'Cette configuration ne peut pas être calculée dans le périmètre actuel. Vérifiez les données saisies et les hypothèses du calcul.',
        };
    }
}
