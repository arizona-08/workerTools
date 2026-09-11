# Variables, constantes et concepts métier

Ce référentiel décrit les concepts effectivement implémentés dans WorkerTools.
`USER` désigne une donnée saisie, `DERIVED` une valeur calculée, `PROFILE` une
valeur du profil normatif, `FIXED_MVP` une hypothèse figée et `CONFIG` une
configuration métier ou technique. Les calculs normatifs relèvent du backend.

## Contrats transverses

| Nom dans le code | Type / valeurs | Origine | Rôle | Unité interne / affichée |
|---|---|---|---|---|
| `ModuleSelectorFieldType` | `Poutre`, `Dalle` | USER | module sélectionné dans le calculateur ; aucun calcul associé à ce stade | — |
| `CalculatorModule` | `id`, `label`, `description` | CONFIG | métadonnées d'affichage des modules | — |
| `UNITS` | longueur, effort, charge linéaire/surfacique, moment, contrainte, armatures | CONFIG | unités UI permises | `mm/cm/m`, `kN`, `kN/m`, `kN/m²`, `kN·m`, `MPa`, `mm²/cm²/mm²/m` |
| `INTERNAL_UNITS` | dictionnaire par catégorie | CONFIG | convention prévue pour les échanges moteur/use case : `mm`, `kN`, `kN/m`, `kN/m²`, `kN·m`, `MPa`, `mm²` | selon catégorie |
| `CalculationStatus` | `compliant`, `non_compliant`, `warning`, `not_verified`, `not_applicable`, `not_calculated` | DERIVED | état de présentation générique | — |
| `CalculationValue` | `label`, `value`, `unit?` | DERIVED | valeur affichable | unité explicite si applicable |
| `CalculationVerification` | `id`, `label`, `status`, `utilization?`, `message?` | DERIVED | contrat de vérification ; aucun taux n'est encore calculé | taux sans dimension |
| `CalculationDetail` / `CalculationResult` | groupes de valeurs / résultat global | DERIVED | contrat frontend ; le backend reste source de vérité | — |

## Matériaux

### Béton de masse volumique normale

| Code / symbole | Type ou valeurs | Origine | Signification, dépendances, référence | Unité |
|---|---|---|---|---|
| `ConcreteStrengthClass` | `C20/25`, `C25/30`, `C30/37` | USER | clé du `ConcreteClassRepository` | — |
| `strengthClass` | `ConcreteStrengthClass` | DERIVED | classe attachée à `ConcreteProperties` | — |
| `fck` | 20, 25, 30 | DERIVED | résistance caractéristique compression à 28 jours ; dépend de la classe. NF EN 1992-1-1:2005, tableau 3.1 | MPa |
| `fcm` | 28, 33, 38 | DERIVED | résistance moyenne compression ; dépend de la classe. Tableau 3.1 | MPa |
| `fctm` | 2,2, 2,6, 2,9 | DERIVED | résistance moyenne traction ; dépend de la classe. Tableau 3.1 | MPa |
| `ecm` / `Ecm` | 30 000, 31 000, 33 000 | DERIVED | module sécant ; dépend de la classe. Tableau 3.1 | MPa |
| `fcd` | `alphaCc × fck / gammaC` | DERIVED | résistance de calcul ; dépend du matériau et du profil | MPa |

`fck / fcm / fctm / ecm` sont intrinsèques : aucun coefficient de sécurité
n'est stocké dans `ConcreteProperties`.

### Acier d'armature passive

| Code / symbole | Type ou valeurs | Origine | Signification, dépendances, référence | Unité |
|---|---|---|---|---|
| `ReinforcementSteelGrade` / `grade` | `B500B` | USER puis DERIVED | nuance du référentiel | — |
| `fyk` | 500 | DERIVED | limite caractéristique d'élasticité B500B | MPa |
| `es` / `Es` | 200 000 | DERIVED | module d'Young, valeur de référence de NF EN 1992-1-1:2005, 3.2.7(2) | MPa |
| `SteelDuctilityClass` / `ductilityClass` | `B` | DERIVED | classe de ductilité B500B ; annexe C | — |
| `fyd` | `fyk / gammaS` | DERIVED | résistance de calcul ; n'est pas stockée dans le matériau | MPa |

## Profil normatif français

`DesignCodeProfileIdentifier::NF_EN_1992_1_1_2005_FR` est le seul profil.
Il vise EN 1992-1-1:2004, NF EN 1992-1-1:2005 et les versions françaises
documentées dans `docs/normative/ec2-03-french-profile.md`.

| Code / symbole | Valeur | Origine | Utilisation et référence |
|---|---:|---|---|
| `gammaC` / `γc` | 1,50 | PROFILE | coefficient partiel béton de `fcd`, EC2 2.4.2.4 |
| `gammaS` / `γs` | 1,15 | PROFILE | coefficient partiel acier de `fyd`, EC2 2.4.2.4 |
| `alphaCc` / `αcc` | 1,00 | PROFILE | coefficient de `fcd`, EC2 3.1.6 ; statut A1:2026 encore à confirmer |
| `gammaGUnfavourable`, `gammaGFavourable`, `gammaQ` | 1,35 / 1,00 / 1,50 | PROFILE | combinaison ELU fondamentale bâtiment |
| `VariableActionCategory` | `A` supportée ; `B…E` connues | USER | catégorie normative de l'action variable. Le MVP Poutre n'accepte que A ; B à E sont explicitement hors périmètre |
| `psi0`, `psi1`, `psi2` / `ψ0`, `ψ1`, `ψ2` | 0,70 / 0,50 / 0,30 pour A | PROFILE | facteurs dépendant de la catégorie d'action, résolus par le profil ; ils ne sont pas des constantes universelles. EN 1990 annexe A1 / EN 1991-1-1 |

## Expositions

| Code / symbole | Type ou valeurs | Origine | Signification et limite |
|---|---|---|---|
| `ExposureClassCode` / `code` | `X0`, `XC1…XC4`, `XD1…XD3`, `XS1…XS3`, `XF1…XF4`, `XA1…XA3` | USER | environnement de EC2 tableau 4.1 ; pas une valeur d'enrobage |
| `ExposureFamily` / `family` | aucun risque, carbonatation, chlorures hors/avec eau de mer, gel-dégel, attaque chimique | DERIVED | famille de la classe |
| `DegradationMechanism` | corrosion carbonatation/chlorures, gel-dégel, attaque chimique | DERIVED | mécanisme descriptif ; `null` pour X0 |
| `ExposureConditions` / `exposureClasses` | liste de classes | USER | expositions d'un élément ; aucune priorité intrinsèque | 
| `label`, `description` | texte | CONFIG | contenu lisible issu du référentiel | — |

Référence : EN 1992-1-1:2004 tableau 4.1 / NF EN 1992-1-1:2005. Les
exigences de durabilité restent dans le profil normatif.

## Enrobage nominal EC2-05

Le calcul automatique est limité au béton armé, acier passif carbone,
barres individuelles, sans protection/traitement spécial ni feu. Les variables
sont retournées par `CoverCalculationResult` et exprimées en `mm` sauf mention.

| Code / symbole | Type ou valeurs | Origine | Signification, dépendances et référence |
|---|---|---|---|
| `coverMode` | `AUTO`, `MANUAL` | USER | active le calcul ou retient une valeur imposée |
| `designWorkingLifeYears` | 25, 50, 100 | USER | durées acceptées par le profil ; modulation -1, 0, +2 selon tableau 4.3NF |
| `reinforcementDiameter` / `φ` | nombre `> 0` | USER | diamètre de barre ; donne `cMinBond` |
| `compactCover` | booléen | USER | atteste les conditions de compacité du tableau 4.3NF ; le moteur ne les déduit pas |
| `manualNominalCover` / `c_nom` | nombre `> 0` | USER | valeur manuelle, sans conformité normative automatique |
| `CoverCalculationScope` | 7 booléens | FIXED_MVP | garde-fou de périmètre ; une valeur fausse refuse AUTO |
| `StructuralClass` | `S1…S6`, initiale `S4` | PROFILE puis DERIVED | classe structurale ; modifiée par durée, résistance et compacité. NA 4.4.1.2(5), tableau 4.3NF |
| `StructuralClassModifier` | règle et entier | DERIVED | trace `workingLife`, `concreteStrength`, `compactCover` |
| `cMinDurability` / `c_min,dur` | nombre | DERIVED | exigence par exposition, table du profil selon classe structurale |
| `cMinBond` / `c_min,b` | `φ` en MVP | DERIVED | exigence d'adhérence pour barre individuelle |
| `deltaCDurGamma`, `deltaCDurSt`, `deltaCDurAdd` | 0 / 0 / 0 | PROFILE | `Δc_dur,γ`, `Δc_dur,st`, `Δc_dur,add` ; pas d'acier/protection spéciaux MVP |
| `correctedCMinDurability` | nombre | DERIVED | `c_min,dur + Δc_dur,γ - Δc_dur,st - Δc_dur,add` |
| `minimumAbsoluteCover` | 10 | PROFILE | seuil absolu de la formule `c_min` |
| `cMin` / `c_min` | nombre | DERIVED | `max(c_min,b, correctedCMinDurability, 10 mm)` |
| `CoverGoverningCriterion` | `BOND`, `DURABILITY`, `MINIMUM_10_MM` | DERIVED | terme gouvernant ; égalité à 10 mm présentée comme minimum absolu |
| `defaultDeltaCDev`, `deltaCDev` / `Δc_dev` | 10 | PROFILE | tolérance d'exécution MVP, sans réduction arbitraire |
| `cNom` / `c_nom` | nombre | DERIVED ou USER | AUTO : `cMin + deltaCDev` ; MANUAL : valeur imposée |
| `governingExposureClass`, `exposureResults` | classe nullable, liste | DERIVED | exposition gouvernante et traces détaillées par exposition |
| `warnings` | liste de textes | DERIVED | notamment l'avertissement du mode manuel |
| `CoverCalculationRejectionReason` | 7 identifiants | DERIVED | motif de rejet métier : donnée invalide, règle/exposition non supportée, périmètre hors MVP |

Références détaillées et limites : `docs/normative/ec2-05-cover.md`. XF est
refusée sans classe XC/XD de référence, XA sans agent agressif caractérisé ;
précontrainte, paquets, feu, inox et protections ne sont pas implémentés.

## Configuration Poutre BEAM-01

`BeamCalculationConfiguration` décrit le cas que le module prépare, sans
géométrie, matériau détaillé, actions, ferraillage ni résultat structurel.
`BeamCalculationConfigurationValidator` vérifie le périmètre ;
`BeamCalculationConfigurationFactory` distingue un identifiant externe invalide
d'une valeur métier reconnue mais non supportée.

| Nom dans le code | Type / valeurs | Origine | Signification, dépendances et limite | Unité |
|---|---|---|---|---|
| `elementType` | `BeamElementType::BEAM` | FIXED_MVP | élément configuré ; la poutre est explicite même si le formulaire est déjà le module Poutre | — |
| `materialType` | `BeamMaterialType::REINFORCED_CONCRETE` | FIXED_MVP | matériau structurel du MVP ; aucun béton précontraint, acier, bois ou béton non armé | — |
| `sectionType` | `RECTANGULAR` supporté ; `T_SECTION`, `L_SECTION`, `VARIABLE`, `CIRCULAR` reconnus mais refusés | FIXED_MVP | type de section. La valeur détermine plus tard les champs de géométrie, sans les créer ici | — |
| `supportSystem` | `SIMPLY_SUPPORTED` supporté ; `CONTINUOUS`, `CANTILEVER`, `FIXED_ENDED`, `MULTI_SPAN` refusés | FIXED_MVP | système statique ; aucun calcul d'effort n'est effectué | — |
| `loadModel` | `UNIFORMLY_DISTRIBUTED` supporté ; `POINT_LOAD`, `TRIANGULAR`, `APPLIED_MOMENT` refusés | FIXED_MVP | modèle de chargement qui déterminera ultérieurement les données à saisir | — |
| `designCodeProfile` | `NF_EN_1992_1_1_2005_FR` | PROFILE | référence au profil `FrenchEurocodeProfileRepository`, sans duplication de `γ`, `α` ou `ψ` | — |
| `designSituation` | `PERSISTENT_TRANSIENT` | FIXED_MVP | situation persistante/transitoire du MVP ; accidentelle et sismique absentes | — |
| `BeamConfigurationRejectionReason` | `INVALID_CONFIGURATION_VALUE` et motifs `UNSUPPORTED_*` | DERIVED | retour métier de validation. Un identifiant libre inconnu est invalide ; une section T connue est explicitement non supportée | — |

Référence de contexte : EN 1990 pour la situation de projet et EN 1992-1-1
pour le calcul des structures en béton. Ces normes ne sont pas encore évaluées
par BEAM-01 ; elles sont seulement référencées par la configuration.

## Mode de calcul Poutre BEAM-02

| Nom dans le code | Type / valeurs | Origine | Signification, dépendances et limite | Unité |
|---|---|---|---|---|
| `BeamCalculationMode` | enum `DESIGN`, `VERIFICATION` | USER | mode stable sérialisable dans `BeamCalculationConfiguration.calculationMode` | — |
| `calculationMode` | `DESIGN` par défaut, ou `VERIFICATION` | USER | `DESIGN` préparera le dimensionnement d'armatures ; `VERIFICATION` préparera la vérification d'un ferraillage fourni. Aucun champ ni calcul associé n'est disponible à ce stade | — |
| `UNSUPPORTED_CALCULATION_MODE` | valeur de `BeamConfigurationRejectionReason` | DERIVED | réservé à un mode métier reconnu qui serait hors périmètre ; les deux modes actuels sont acceptés | — |
| `INVALID_CONFIGURATION_VALUE` | valeur de `BeamConfigurationRejectionReason` | DERIVED | une chaîne externe inconnue n'est jamais remplacée silencieusement par `DESIGN` | — |

Le formulaire Poutre conserve le mode dans le signal `calculationMode` et le
présente avec deux boutons accessibles, mutuellement exclusifs. Les prochaines
étapes pourront conditionner les champs par `mode === 'DESIGN'` ou
`mode === 'VERIFICATION'`; BEAM-02 n'ajoute ni géométrie, ni charge, ni
ferraillage, ni calcul de conformité.

## Géométrie Poutre BEAM-03

`BeamGeometry` isole les dimensions de la configuration de calcul. Son contrat
backend et le futur payload utilisent exclusivement les millimètres ;
`BeamGeometryFactory::fromInternalValues` distingue les champs absents des
valeurs non numériques, infinies ou non positives.

| Nom dans le code | Symbole | Type / origine | Signification, dépendances et limite | Unité interne / UI |
|---|---|---|---|---|
| `effectiveSpan` | `l_eff` | nombre fini `> 0` / USER | portée efficace de calcul d'une poutre simplement appuyée. Elle est saisie directement : aucune portée libre, largeur d'appui ou dérivation automatique n'est disponible | mm / m |
| `width` | `b` | nombre fini `> 0` / USER | largeur de la section rectangulaire constante ; dépend de `sectionType = RECTANGULAR` | mm / cm |
| `height` | `h` | nombre fini `> 0` / USER | hauteur totale de la section rectangulaire constante ; ce n'est pas la hauteur utile `d` | mm / cm |
| `BeamGeometry` | — | objet `effectiveSpan`, `width`, `height` / DERIVED | géométrie validée stockée en mm, séparée de la configuration et des futurs matériaux/actions | mm |
| `BeamGeometryPayload` | — | objet avec `unit: 'mm'` / DERIVED | forme frontend du futur payload : `{ configuration, geometry }` ; les nombres sans unité ne sont pas envoyés | mm |
| `buildBeamGeometryPayload` | — | helper frontend / CONFIG | conversion unique UI vers payload : `m × 1000`, `cm × 10` | mm |
| `BeamGeometryRejectionReason` | — | six identifiants / DERIVED | motifs `MISSING_*` ou `INVALID_*`, utilisés par la validation backend | — |

Exemple : `l_eff = 6,50 m`, `b = 30 cm`, `h = 60 cm` deviennent
`effectiveSpan = 6500 mm`, `width = 300 mm`, `height = 600 mm` dans le
payload. Aucune limite maximale UX ou normative, aucun calcul de `Ac`, `d`,
`MEd`, `VEd`, inertie ou poids propre n'est introduit par BEAM-03.

## Matériaux Poutre BEAM-04

| Nom | Type / valeurs | Origine | Rôle et limite | Unité |
|---|---|---|---|---|
| `concreteClass` | identifiant `C20/25`, `C25/30`, `C30/37` | USER | référence `ConcreteStrengthClass`, permettant au backend de retrouver `fck`, `fcm`, `fctm`, `Ecm` ; aucune propriété n'est transmise par le client | — |
| `steelGrade` | identifiant `B500B` | USER | référence `ReinforcementSteelGrade`, permettant au backend de retrouver `fyk`, `Es`, `ductilityClass` ; `fyd` reste dérivé du profil | — |
| `exposureClasses` | liste non vide de `ExposureClassCode` | USER | environnements applicables, compatibles avec plusieurs classes ; futur usage : classe structurale, `c_min,dur`, `c_nom` | — |
| `BeamMaterials` | objet de trois références | DERIVED | modèle backend validé via les repositories EC2-01, EC2-02 et EC2-04 ; aucune propriété mécanique | — |
| `BeamMaterialCatalog` | classes béton, nuances acier, codes/libellés exposition | CONFIG | catalogue API `/api/beam/material-catalog`, alimenté directement par les repositories backend | — |
| `BeamMaterialsPayload` | `concreteClass`, `steelGrade`, `exposureClasses` | DERIVED | partie `materials` du futur payload Poutre, sans résistance, module, coefficient ou valeur de calcul | — |

Les valeurs initiales C30/37, B500B et XC1 sont des defaults applicatifs
`CONFIG`, modifiables par l'utilisateur et non des prescriptions normatives.
Le profil français demeure dans `configuration` : la combinaison matériau /
profil ne sera résolue que par le moteur lors d'une future étape.

## Charges permanentes Poutre BEAM-05

| Nom | Symbole | Type / origine | Rôle et limite | Unité interne / UI |
|---|---|---|---|---|
| `includeSelfWeight` | — | booléen / USER | indique que le futur calcul devra inclure le poids propre. Défaut applicatif `CONFIG` : `true`, non normatif | — |
| `additionalPermanentLoad` | `Gk,additional` | nombre fini `>= 0` / USER | charge permanente caractéristique uniformément répartie hors poids propre ; `0` signifie qu'aucune charge additionnelle n'est déclarée | kN/m |
| `BeamPermanentLoads` | — | objet validé / DERIVED | isole les deux choix d'actions permanentes ; ne contient ni poids propre calculé ni total | kN/m pour la charge |
| `BeamPermanentLoadsPayload` | — | `{ includeSelfWeight, additionalPermanentLoad, unit: 'kN/m' }` / DERIVED | partie `loads.permanent` du futur payload Poutre ; l'unité est explicite | kN/m |
| `Gk_self` | `Gk,self` | DERIVED, futur | poids propre linéaire de la poutre ; sera introduit en `BEAM-CALC-01` | kN/m |
| `Gk_total` | `Gk,total` | DERIVED, futur | action permanente totale pour les combinaisons futures : `Gk_self + Gk,additional` si le poids propre est inclus, sinon `Gk,additional` | kN/m |

BEAM-05 n'introduit aucune masse volumique de béton. Sa source devra être
définie explicitement dans `BEAM-CALC-01`, sans constante magique dans le
formulaire. Aucun coefficient `γG`, aucune combinaison et aucun effort ne sont
calculés à cette étape.

## Charges d'exploitation Poutre BEAM-06

| Nom | Symbole | Type / origine | Rôle et limite | Unité interne / UI |
|---|---|---|---|---|
| `variableActionCategory` / `category` | — | `VariableActionCategory` / FIXED_MVP | catégorie d'action variable. Le formulaire représente discrètement A (locaux résidentiels / domestiques) ; B, C, D et E sont connues mais refusées par le MVP Poutre | — |
| `characteristicLoad` | `Qk` | nombre fini `>= 0` / USER | action variable caractéristique uniformément répartie, déjà ramenée sur la poutre ; défaut applicatif `CONFIG` : `0` | kN/m |
| `BeamVariableLoad` | — | objet validé / DERIVED | contient seulement la catégorie et `Qk`, sans `γQ` ni facteur `ψ` | kN/m pour la charge |
| `BeamVariableLoadPayload` | — | `{ category: 'A', characteristicLoad, unit: 'kN/m' }` / DERIVED | partie `loads.variable` du futur payload Poutre, sans coefficients de sécurité ou de combinaison | kN/m |

Les futures combinaisons ELU et ELS demanderont `ψ0`, `ψ1` et `ψ2` au profil
normatif via `DesignCodeProfile::combinationFactorsFor(category)`. BEAM-06 ne
les applique pas et ne calcule ni `γQ × Qk`, ni effort, ni poids propre. Les
actions neige, vent, climatiques, thermiques, accidentelles et plusieurs
actions variables indépendantes sont hors périmètre MVP.

## Ferraillage existant Poutre BEAM-07

| Nom | Symbole | Type / origine | Rôle et limite | Unité interne / UI |
|---|---|---|---|---|
| `tensionBarCount` | `n` | entier fini `>= 1` / USER | nombre de barres longitudinales tendues de la section vérifiée | — |
| `tensionBarDiameter` | `φ` | diamètre du catalogue / USER | diamètre nominal de toutes les barres tendues ; prépare `As_prov`, la hauteur utile future, la flexion et la fissuration | mm |
| `tensionRebarLayers` | — | `1` / FIXED_MVP | un seul lit de barres tendues ; plusieurs lits, diamètres mixtes, armatures comprimées et paquets sont hors périmètre | — |
| `providedSteelArea` | `As,prov` | nombre dérivé / DERIVED | aire totale d'acier tendu, recalculée côté backend à partir de `n` et `φ` avec `n × π × φ² / 4` ; elle n'est jamais une entrée fiable du client | mm² |
| `BeamLongitudinalReinforcement` | — | objet validé / DERIVED | ferraillage longitudinal existant uniquement ; aucun étrier, acier comprimé, `As_min`, `As_req`, `d` ou résistance | mm / mm² |
| `BeamLongitudinalReinforcementPayload` | — | `reinforcement.longitudinal.tension` / DERIVED | présent uniquement en mode `VERIFICATION`, avec `barCount`, `barDiameter`, `diameterUnit: 'mm'` ; `As_prov` est absent du payload | mm |
| `ReinforcementBarDiameterCatalog` | 8, 10, 12, 14, 16, 20, 25, 32 | CONFIG | catalogue applicatif centralisé de diamètres nominaux passifs proposés par le MVP ; non exhaustif et non normatif | mm |

Le ferraillage est requis en `VERIFICATION` et refusé dans le contrat backend
`DESIGN`. Exemple géométrique : 4 HA16 donnent `4 × π × 16² / 4 =
804,2477… mm²`, affiché comme `804 mm²` sans arrondir la valeur moteur.

## Contrat d'entrée Poutre MVP BEAM-08

`BeamCalculationInputFactory` assemble et valide les sections `configuration`,
`geometry`, `materials` et `loads`, avec `reinforcement` obligatoire seulement
en `VERIFICATION`. Il délègue la validation métier aux factories BEAM-01 à
BEAM-07, contrôle explicitement `mm` et `kN/m`, et refuse les propriétés client
non prévues — notamment les propriétés mécaniques et valeurs dérivées.

`BeamForm::isInputValid()` est l'état global frontend : le catalogue matériaux
doit être chargé et le payload centralisé doit pouvoir être construit. Cette
validité d'entrée ne représente aucune conformité structurelle. Le contrat et
les exemples complets DESIGN / VERIFICATION sont documentés dans
`docs/domain/beam-calculation-input.md`.
