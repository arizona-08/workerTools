<?php

namespace App\StructuralCalculation\CalculationNotes;

/** Traduit les identifiants techniques uniquement au moment de présenter une note. */
final class CalculationNoteDisplayValue
{
    public static function french(string|int|float|bool|null $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $labels = [
            'BEAM' => 'Poutre',
            'SLAB' => 'Dalle',
            'DESIGN' => 'Dimensionnement',
            'VERIFICATION' => 'Vérification',
            'REINFORCED_CONCRETE' => 'Béton armé',
            'RECTANGULAR' => 'Rectangulaire',
            'SIMPLY_SUPPORTED' => 'Simplement appuyée',
            'CANTILEVER' => 'Console',
            'BEAM_SIMPLE_RECTANGULAR' => 'Poutre rectangulaire simplement appuyée',
            'BEAM_CANTILEVER_RECTANGULAR' => 'Poutre rectangulaire en console',
            'TOP' => 'Partie supérieure',
            'BOTTOM' => 'Partie inférieure',
            'FIXED_END' => 'Encastrement',
            'UNIFORMLY_DISTRIBUTED' => 'Uniformément répartie',
            'PERSISTENT_TRANSIENT' => 'Persistante / transitoire',
            'AUTO' => 'Automatique',
            'NF_EN_1992_1_1_2005_FR' => 'NF EN 1992-1-1:2005 — France',
            'SOLID' => 'Dalle pleine',
            'ONE_WAY' => 'Unidirectionnelle',
            'SINGLE_SPAN_SIMPLY_SUPPORTED_ON_OPPOSITE_SIDES' => 'Une travée, simplement appuyée sur deux côtés opposés',
            'VERTICAL_UNIFORMLY_DISTRIBUTED' => 'Charges verticales uniformément réparties',
            'FLEXURE' => 'Flexion',
            'SHEAR' => 'Cisaillement',
            'STRESS' => 'Contraintes ELS',
            'CRACK' => 'Fissuration',
            'DEFLECTION' => 'Déformation',
            'MAIN_REINFORCEMENT' => 'Armatures principales',
            'SECONDARY_REINFORCEMENT' => 'Armatures secondaires',
            'SIMPLIFIED_SPAN_DEPTH' => 'Contrôle simplifié portée / hauteur utile',
            'ULS_FUNDAMENTAL' => 'ELU fondamentale',
            'SLS_CHARACTERISTIC' => 'ELS caractéristique',
            'SLS_FREQUENT' => 'ELS fréquente',
            'SLS_QUASI_PERMANENT' => 'ELS quasi-permanente',
            'QUASI_PERMANENT' => 'Quasi-permanente',
            'CANDIDATE_RECALCULATION' => 'Recalcul du candidat de ferraillage',
            'SHEAR_CHAIN' => 'Chaîne de vérification au cisaillement',
            'ULS_FLEXURE' => 'Flexion ELU',
            'SLAB_08' => 'Proposition de ferraillage de dalle',
            'SLAB_09' => 'Armatures secondaires de dalle',
            'CRACKED_ELASTIC' => 'Section fissurée élastique',
            'DIRECT_CRACK_WIDTH' => 'Calcul direct de l’ouverture de fissure',
            'CONCRETE_CHARACTERISTIC_STRESS' => 'Contrainte caractéristique du béton',
            'STEEL_CHARACTERISTIC_STRESS' => 'Contrainte caractéristique de l’acier',
            'CONCRETE_QUASI_PERMANENT_STRESS' => 'Contrainte quasi-permanente du béton',
            'STEEL_QUASI_PERMANENT_STRESS' => 'Contrainte quasi-permanente de l’acier',
            'VALID_AFTER_RECALCULATION' => 'Valide après recalcul',
            'INSUFFICIENT_AFTER_RECALCULATION' => 'Insuffisant après recalcul',
            'INVALID_SINGLY_REINFORCED_DOMAIN' => 'Domaine de section simplement armée non valide',
            'CANDIDATES_AVAILABLE' => 'Candidats disponibles',
            'NO_REINFORCEMENT_CANDIDATE' => 'Aucun candidat de ferraillage',
            'SHEAR_REINFORCEMENT_NOT_REQUIRED_BY_VRDC_CHECK' => 'Armatures transversales non requises selon VRd,c',
            'SHEAR_REINFORCEMENT_REQUIRED' => 'Armatures transversales requises',
            'MAXIMUM_SHEAR_RESISTANCE_OK' => 'Résistance maximale au cisaillement vérifiée',
            'MAIN_EXPRESSION' => 'Expression principale',
            'MINIMUM_SHEAR_RESISTANCE' => 'Résistance minimale au cisaillement',
            'EQUAL_RESISTANCES' => 'Résistances égales',
            'BAR_DIAMETER' => 'Diamètre de barre',
            'AGGREGATE_SIZE' => 'Dimension des granulats',
            'ABSOLUTE_MINIMUM' => 'Minimum absolu',
            'TIE' => 'Égalité des critères',
            'FCTM_FYK' => 'Critère fctm / fyk',
            'ABSOLUTE_RATIO' => 'Ratio minimal absolu',
            'FLEXURAL_DEMAND' => 'Besoin en flexion',
            'MINIMUM_REINFORCEMENT' => 'Armature minimale',
            'EQUAL_REQUIREMENTS' => 'Exigences égales',
            'SHEAR_DEMAND' => 'Besoin au cisaillement',
            'MINIMUM_TRANSVERSE_REINFORCEMENT' => 'Armatures transversales minimales',
            'MAIN_STRAIN_EXPRESSION' => 'Expression principale de déformation',
            'MINIMUM_STRAIN_DIFFERENCE' => 'Différence minimale de déformation',
            'REINFORCEMENT_PROPOSAL_FOUND' => 'Proposition de ferraillage disponible',
            'SECONDARY_REINFORCEMENT_PROPOSAL_FOUND' => 'Proposition d’armatures secondaires disponible',
            'NO_VALID_REINFORCEMENT_PROPOSAL' => 'Aucune proposition de ferraillage valide',
            'NO_VALID_SECONDARY_REINFORCEMENT_PROPOSAL' => 'Aucune proposition d’armatures secondaires valide',
            'NO_VALID_STIRRUP_CANDIDATE' => 'Aucun candidat d’étrier valide',
            'INSUFFICIENT_HORIZONTAL_SPACE' => 'Largeur disponible insuffisante',
            'INSUFFICIENT_REINFORCEMENT_PER_LENGTH' => 'Armatures insuffisantes par unité de longueur',
            'LONGITUDINAL_SPACING_EXCEEDED' => 'Espacement longitudinal dépassé',
            'TRANSVERSE_LEG_SPACING_EXCEEDED' => 'Espacement entre branches dépassé',
            'MAXIMUM_SHEAR_RESISTANCE_EXCEEDED' => 'Résistance maximale au cisaillement dépassée',
            'SHEAR_RESISTANCE_INSUFFICIENT' => 'Résistance au cisaillement insuffisante',
            'MISSING_MAIN_REINFORCEMENT_PROPOSAL' => 'Proposition d’armatures principales absente',
            'REQUIRED_VERIFICATION_MISSING' => 'Vérification requise absente',
            'NO_EXPLICIT_DEFLECTION_CALCULATED' => 'Aucune flèche explicite calculée',
            'LONG_TERM_EFFECTS_NOT_EXPLICITLY_MODELLED' => 'Effets de long terme non modélisés explicitement',
            'PARTITION_DAMAGE_CHECK_NOT_MODELLED' => 'Vérification des dommages aux cloisons non modélisée',
            'UTILIZATION_UNAVAILABLE' => 'Taux d’utilisation indisponible',
            'CANTILEVER_FIXED_END_SCOPE' => 'Méthode non prise en charge à l’encastrement',
            'CANTILEVER_FIXED_END_CRITICAL_SECTION_NOT_MODELLED' => 'Section critique de cisaillement à l’encastrement non modélisée',
            'CANTILEVER_CRACK_VERIFICATION_REQUIRES_FIXED_END_STIRRUP_LAYOUT' => 'Vérification de fissuration nécessitant un ferraillage transversal à l’encastrement',
            'CANTILEVER_STRUCTURAL_FACTOR_NOT_DEFINED_IN_PROFILE' => 'Facteur de système non défini pour la console',
            'A' => 'Catégorie A',
        ];

        return $labels[$value] ?? self::humanize($value);
    }

    private static function humanize(string $value): ?string
    {
        if (preg_match('/^[A-Z0-9]+(?:_[A-Z0-9]+)+$/', $value) !== 1) {
            return null;
        }

        $words = [
            'INVALID' => 'invalide', 'MISSING' => 'absent', 'UNSUPPORTED' => 'non pris en charge', 'NOT' => 'non', 'NO' => 'aucun', 'REQUIRED' => 'requis', 'AVAILABLE' => 'disponible', 'EXCEEDED' => 'dépassé', 'INSUFFICIENT' => 'insuffisant', 'CALCULATION' => 'calcul', 'METHOD' => 'méthode', 'LOAD' => 'charge', 'LOADS' => 'charges', 'REINFORCEMENT' => 'armatures', 'REINFORCED' => 'armé', 'CONCRETE' => 'béton', 'STEEL' => 'acier', 'SHEAR' => 'cisaillement', 'STRESS' => 'contrainte', 'CRACK' => 'fissuration', 'DEFLECTION' => 'flèche', 'EFFECTIVE' => 'effective', 'SPAN' => 'portée', 'WIDTH' => 'largeur', 'HEIGHT' => 'hauteur', 'THICKNESS' => 'épaisseur', 'DEPTH' => 'hauteur utile', 'AREA' => 'aire', 'RESISTANCE' => 'résistance', 'MAXIMUM' => 'maximale', 'MINIMUM' => 'minimale', 'DESIGN' => 'de calcul', 'CHARACTERISTIC' => 'caractéristique', 'SERVICEABILITY' => 'service', 'PERMANENT' => 'permanente', 'VARIABLE' => 'variable', 'LONGITUDINAL' => 'longitudinal', 'TRANSVERSE' => 'transversal', 'BAR' => 'barre', 'DIAMETER' => 'diamètre', 'SPACING' => 'espacement', 'CANDIDATE' => 'candidat', 'PROPOSAL' => 'proposition', 'CHECK' => 'vérification', 'STATUS' => 'statut', 'STRUCTURAL' => 'structural', 'SYSTEM' => 'système', 'SECTION' => 'section', 'MATERIAL' => 'matériau', 'EXPOSURE' => 'exposition', 'CLASS' => 'classe', 'NORMAL' => 'normal', 'FORCE' => 'effort', 'MOMENT' => 'moment', 'TENSION' => 'traction', 'COMPRESSION' => 'compression', 'PARTIAL' => 'partiel', 'FACTOR' => 'coefficient', 'UNIT' => 'unité', 'PROFILE' => 'profil', 'SITUATION' => 'situation', 'ACTION' => 'action', 'CATEGORY' => 'catégorie', 'INPUT' => 'entrée', 'VALUE' => 'valeur', 'PROPERTY' => 'propriété',
        ];

        return implode(' ', array_map(fn (string $word): string => $words[$word] ?? strtolower($word), explode('_', $value)));
    }
}
