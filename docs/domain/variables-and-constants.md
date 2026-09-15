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
| `psi0`, `psi1`, `psi2` / `ψ0`, `ψ1`, `ψ2` | 0,70 / 0,50 / 0,30 pour A | PROFILE | facteurs dépendant de la catégorie d'action, résolus par le profil ; `ψ0` concerne notamment les variables accompagnatrices de la combinaison caractéristique, `ψ1` la valeur fréquente et `ψ2` la valeur quasi-permanente. Ils ne sont pas des constantes universelles. EN 1990 annexe A1 / EN 1991-1-1 |

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
| `elementType` | `ElementType::BEAM` | FIXED_MVP | élément configuré ; enum partagé avec le module Dalle | — |
| `materialType` | `MaterialType::REINFORCED_CONCRETE` | FIXED_MVP | matériau structurel du MVP ; enum partagé avec le module Dalle, sans béton précontraint, acier, bois ou béton non armé | — |
| `sectionType` | `RECTANGULAR` supporté ; `T_SECTION`, `L_SECTION`, `VARIABLE`, `CIRCULAR` reconnus mais refusés | FIXED_MVP | type de section. La valeur détermine plus tard les champs de géométrie, sans les créer ici | — |
| `supportSystem` | `SIMPLY_SUPPORTED` supporté ; `CONTINUOUS`, `CANTILEVER`, `FIXED_ENDED`, `MULTI_SPAN` refusés | FIXED_MVP | système statique ; aucun calcul d'effort n'est effectué | — |
| `loadModel` | `UNIFORMLY_DISTRIBUTED` supporté ; `POINT_LOAD`, `TRIANGULAR`, `APPLIED_MOMENT` refusés | FIXED_MVP | modèle de chargement qui déterminera ultérieurement les données à saisir | — |
| `designCodeProfile` | `NF_EN_1992_1_1_2005_FR` | PROFILE | référence au profil `FrenchEurocodeProfileRepository`, sans duplication de `γ`, `α` ou `ψ` | — |
| `designSituation` | `DesignSituation::PERSISTENT_TRANSIENT` | FIXED_MVP | situation persistante/transitoire du MVP ; enum partagé avec le module Dalle, accidentelle et sismique absentes | — |
| `BeamConfigurationRejectionReason` | `INVALID_CONFIGURATION_VALUE` et motifs `UNSUPPORTED_*` | DERIVED | retour métier de validation. Un identifiant libre inconnu est invalide ; une section T connue est explicitement non supportée | — |

Référence de contexte : EN 1990 pour la situation de projet et EN 1992-1-1
pour le calcul des structures en béton. Ces normes ne sont pas encore évaluées
par BEAM-01 ; elles sont seulement référencées par la configuration.

## Configuration Dalle SLAB-01

`SlabCalculationConfiguration` fixe explicitement le seul cas préparé par le
module Dalle. Il ne porte aucune dimension, classe de béton, nuance d'acier,
exposition, action, résultat ou formule.

| Nom dans le code | Type / valeurs | Origine | Signification, dépendances et limite | Unité |
|---|---|---|---|---|
| `elementType` | `ElementType::SLAB` | FIXED_MVP | élément associé au module Dalle ; `ElementType` est partagé avec Poutre (`BEAM`) | — |
| `slabType` | `SlabType::SOLID` | FIXED_MVP | dalle pleine ; aucune dalle nervurée, alvéolaire, précontrainte ou mixte n'est représentée | — |
| `spanningSystem` | `SlabSpanningSystem::ONE_WAY` | FIXED_MVP | fonctionnement dans une direction principale ; aucun comportement bidirectionnel n'est disponible | — |
| `structuralSystem` | `SlabStructuralSystem::SINGLE_SPAN_SIMPLY_SUPPORTED_ON_OPPOSITE_SIDES` | FIXED_MVP | une travée : bande de calcul simplement appuyée sur deux côtés opposés ; aucune continuité, console ou autre condition d'appui n'est représentée | — |
| `loadModel` | `SlabLoadModel::VERTICAL_UNIFORMLY_DISTRIBUTED` | FIXED_MVP | charges verticales uniformément réparties ; aucune charge ponctuelle, linéaire localisée ou horizontale n'est représentée | — |
| `materialType` | `MaterialType::REINFORCED_CONCRETE` | FIXED_MVP | béton armé, sans classe de béton ni acier d'armature détaillés avant SLAB-03 | — |
| `designCodeProfile` | `DesignCodeProfileIdentifier::NF_EN_1992_1_1_2005_FR` | PROFILE | identifiant du profil français partagé avec Poutre ; aucun coefficient ni règle normative n'est évalué ici | — |
| `designSituation` | `DesignSituation::PERSISTENT_TRANSIENT` | FIXED_MVP | situation persistante/transitoire préparée, sans combinaison d'actions | — |
| `SlabCalculationConfigurationFactory` / `Validator` | configuration et motifs `INVALID_CONFIGURATION_VALUE`, `UNSUPPORTED_*` | DERIVED | conversion typée et validation backend, indépendante du frontend ; pas de DTO de calcul Dalle à ce stade | — |

Le formulaire affiche comme hypothèses non interactives « Dalle pleine »,
« Unidirectionnelle », « Béton armé », « Une travée », « Bande simplement
appuyée sur deux côtés opposés » et « Charges verticales uniformément
réparties ». Les matériaux détaillés (SLAB-03), les charges surfaciques
(SLAB-04) et tout calcul restent explicitement hors périmètre.

## Géométrie Dalle SLAB-02

`SlabGeometry` contient seulement les longueurs nécessaires à la future bande
unitaire. Les conversions sont réalisées une seule fois dans
`buildSlabGeometryPayload`, entre la saisie Angular et le payload interne ;
`SlabGeometryFactory` valide ensuite exclusivement des millimètres.

| Nom dans le code | Symbole | Type / valeurs | Origine | Signification et limite | Unité UI / interne |
|---|---|---|---|---|---|
| `effectiveSpan` | `L` | nombre fini `> 0` | USER | portée de calcul saisie directement ; aucune portée libre, appui ou dérivation automatique | m / mm |
| `thickness` | `h` | nombre fini `> 0` | USER | épaisseur totale de la dalle pleine ; ce n'est pas la hauteur utile `d` | cm / mm |
| `calculationStripWidth` | `b` | `SlabGeometry::CALCULATION_STRIP_WIDTH_MM = 1000` | FIXED_MVP | bande unitaire automatique du modèle unidirectionnel ; elle n'est ni saisie ni envoyée par le client | — / mm |
| `SlabGeometryPayload` | — | `effectiveSpan`, `thickness`, `unit: 'mm'` | DERIVED | contrat de géométrie sans `width`, `stripWidth` ou autre largeur utilisateur | mm |
| `SlabGeometryRejectionReason` | — | motifs `MISSING_*`, `INVALID_*` | DERIVED | validation backend : présence, nombre fini et strictement positif | — |

La bande de 1 m est une convention de modélisation du MVP, sans référence
normative attribuée à ce stade. SLAB-02 ne calcule ni surface, volume, poids
propre, hauteur utile, ratio `L/h`, moment, effort, armature ou conformité.

## Matériaux Dalle SLAB-03

La dalle réutilise directement `ConcreteStrengthClass`,
`ReinforcementSteelGrade` et `ExposureClassCode` des référentiels communs ;
elle ne définit aucun équivalent `Slab*`. `SlabMaterialsFactory` délègue la
résolution et les capabilities MVP à `BeamMaterialsFactory` et
`BeamCalculationCapabilities`, qui restent la source unique actuelle.

| Nom | Origine | Contexte Dalle |
|---|---|---|
| `concreteClass` | USER | identifiant de `ConcreteStrengthClass`, parmi les capabilities communes |
| `steelGrade` | USER | identifiant de `ReinforcementSteelGrade`, explicitement B500B dans le MVP courant |
| `exposureClass` | USER | identifiant de `ExposureClassCode`, explicitement XC1 dans le MVP courant |

Les propriétés `fck`, `fcm`, `fctm`, `Ecm`, `fyd`, les coefficients de profil
et l'enrobage restent dérivés par le moteur commun ; elles ne sont ni saisies
ni calculées dans SLAB-03. Aucune charge ou vérification de dalle n'est ajoutée.

## Charges surfaciques Dalle SLAB-04

`SlabCalculationInput` agrège configuration, géométrie, matériaux et
`SlabSurfaceLoads` sans lancer de chaîne de calcul. `SlabCharacteristicActionsCalculator`
combine les actions avec la géométrie Dalle et récupère le poids volumique via
`ReinforcedConcreteUnitWeightRepository`. Toutes les charges restent surfaciques : aucune
largeur de bande, charge linéique, combinaison ELU/ELS ou sollicitation n'est
produite.

| Nom dans le code | Symbole | Origine | Signification, dépendances et limite | Unité |
|---|---|---|---|---|
| `finishes` | `gk_finishes` | USER | revêtement caractéristique ; peut être nul | kN/m² |
| `partitions` | `gk_partitions` | USER | cloisons caractéristiques ; peut être nul | kN/m² |
| `otherPermanent` | `gk_otherPermanent` | USER | autres charges permanentes caractéristiques ; peut être nul | kN/m² |
| `imposedLoad` | `qk` / `Qk` | USER | charge d'exploitation caractéristique, distincte des permanentes ; peut être nulle | kN/m² |
| `thicknessMillimetres` | `h` | USER | épaisseur de `SlabGeometry`, conservée en interne avant conversion | mm |
| `thicknessMetres` | `h` | DERIVED | épaisseur convertie par `LengthConverter` pour le poids propre | m |
| `ReinforcedConcreteUnitWeight` / `unitWeight` | `γ_concrete` | CONFIG | poids volumique du béton armé normal : 25 ; référentiel commun EN 1991-1-1, indépendant de `ConcreteStrengthClass` | kN/m³ |
| `selfWeight` | `gk_self` | DERIVED | poids propre surfacique : `γ_concrete × h` ; la largeur de bande n'intervient pas | kN/m² |
| `permanentTotal` | `Gk_total` | DERIVED | `gk_self + gk_finishes + gk_partitions + gk_otherPermanent` ; reste une action caractéristique | kN/m² |

`SlabCharacteristicActions` garde les valeurs utilisées, les résultats et les
formules textuelles pour la traçabilité. Aucun coefficient `γG`, `γQ` ou `ψ`
n'est appliqué dans SLAB-04 ; `wEd`, `MEd`, `VEd`, armatures, ELS et conformité
ne sont pas calculés. La valeur de 25 kN/m³ provient de
`ReinforcedConcreteUnitWeightRepository::normalWeightReinforcedConcrete()` ;
elle est relative au béton armé de masse volumique normale et non à une classe
de résistance de béton.

## Combinaisons Dalle SLAB-05

Le moteur commun `ActionCombinationCalculator` est la seule implémentation des
combinaisons EN 1990 pour la paire `Gk_total` / `Qk`. Les adaptateurs Poutre et
Dalle lui transmettent des actions caractéristiques, puis conservent leurs
unités propres : `kN/m` pour la Poutre et `kN/m²` pour la Dalle. Il ne connaît
ni portée, ni bande de calcul, ni condition d'appui, ni analyse.

| Nom dans le code | Symbole | Origine | Signification, dépendances et référence | Unité Dalle |
|---|---|---|---|---|
| `permanentTotal` | `Gk,total` | DERIVED | total permanent issu de SLAB-04, consommé sans recalcul des contributions | kN/m² |
| `imposedLoad` | `Qk` | USER | action variable principale issue de SLAB-04 ; catégorie A fixée par le MVP, sans sélecteur utilisateur | kN/m² |
| `gammaGUnfavourable`, `gammaGFavourable`, `gammaQ` | `γG,sup`, `γG,inf`, `γQ` | PROFILE | `ActionSafetyFactors` du profil français ; ELU fondamental EN 1990 6.10 | — |
| `psi0`, `psi1`, `psi2` | `ψ0`, `ψ1`, `ψ2` | PROFILE | `CombinationFactors` du profil selon la catégorie A ; `ψ0` n'est pas utilisé pour l'unique action principale de l'ELS caractéristique | — |
| `uls.value` | `qEd` | DERIVED | `γG,sup × Gk,total + γQ × Qk`, expression du profil `EN1990_6_10` | kN/m² |
| `slsCharacteristic.value` | `qSlsCharacteristic` | DERIVED | `Gk,total + Qk`, EN 1990 6.14 ; la variable principale garde le facteur 1 | kN/m² |
| `slsFrequent.value` | `qSlsFrequent` | DERIVED | `Gk,total + ψ1 × Qk`, EN 1990 6.15 | kN/m² |
| `slsQuasiPermanent.value` | `qSlsQuasiPermanent` | DERIVED | `Gk,total + ψ2 × Qk`, EN 1990 6.16 | kN/m² |

`SlabActionCombinations` regroupe ces quatre résultats de
`SlabSurfaceLoadCombination`, chacun traçable par ses entrées, contributions,
facteurs, formule et référence d'expression. Les valeurs `γG`, `γQ`, `ψ1` et
`ψ2` ne sont jamais saisies ou éditables dans Angular. SLAB-05 ne crée aucune
charge linéique, aucun `MEd`, `VEd`, calcul de flexion, armature ou vérification
de conformité ; ces sujets restent hors périmètre jusqu'à SLAB-06 et suivants.

## Pré-analyse de bande Dalle SLAB-06

SLAB-01 définit maintenant explicitement une bande à une travée simplement
appuyée sur deux côtés opposés, sous charges verticales uniformément réparties.
Cette hypothèse était absente lors de la première implémentation de SLAB-06 :
le calculateur existant reste volontairement limité à la conversion de charge
de bande et ne produit encore aucun moment ni effort tranchant.

| Nom dans le code | Symbole | Origine | Signification et limite | Unité |
|---|---|---|---|---|
| `calculationStripWidth` | `b` | FIXED_MVP | bande de `SlabGeometry`, égale à 1000 mm ; non saisie par l'utilisateur | mm |
| `stripWidthMetres` | `b` | DERIVED | conversion explicite de la bande via `LengthConverter` avant le produit physique | m |
| `SlabSurfaceLoadCombination.value` | `qEd`, `qSls*` | DERIVED | charge surfacique issue directement de SLAB-05 ; jamais recombinée ici | kN/m² |
| `SlabStripLinearLoad.lineLoad` | `wEd`, `wSlsCharacteristic`, `wSlsFrequent`, `wSlsQuasiPermanent` | DERIVED | `q × b`, charge linéique de la bande ; aucune analyse d'appui ni sollicitation interne | kN/m |
| `SlabStripAnalysis.linearLoads` | `wEd`, `wSlsCharacteristic`, `wSlsFrequent`, `wSlsQuasiPermanent` | DERIVED | charges linéiques obtenues sans recalcul de `q`, facteurs `γ` ou `ψ` ; chaque résultat conserve son nom, formule et substitution | kN/m |
| `SlabStripInternalForce.maximumMoment` | `MEd`, `MCharacteristic`, `MFrequent`, `MQuasiPermanent` | DERIVED | `w × L² / 8` pour la bande unitaire simplement appuyée ; les valeurs sont en kN·m pour une bande de 1 m, et non en kN·m/m | kN·m |
| `SlabStripInternalForce.maximumShear` | `VEd`, `VCharacteristic`, `VFrequent`, `VQuasiPermanent` | DERIVED | `w × L / 2`, effort maximal aux appuis pour la même hypothèse statique | kN |
| `SlabStripInternalForce.effectiveSpan` | `L` | USER → DERIVED | portée de `SlabGeometry`, convertie explicitement de mm vers m avant l'analyse | m |

La chaîne SLAB-06 est `q → w → M/V` : elle utilise `w = q × b`, puis
`Mmax = w × L² / 8` et `Vmax = w × L / 2`. `SlabStripAnalysisCalculator`
refuse une portée non positive et exige le système fixe
`SINGLE_SPAN_SIMPLY_SUPPORTED_ON_OPPOSITE_SIDES` ainsi que le modèle de charge
`VERTICAL_UNIFORMLY_DISTRIBUTED`. Les expressions analytiques sont centralisées
dans `SimplySupportedUniformlyDistributedLoadCalculator`, également utilisé par
la Poutre. SLAB-06 ne produit ni armature, ni hauteur utile, ni résistance au
cisaillement, ni vérification ELS matériau, ni conformité.

## Flexion ELU de la bande Dalle SLAB-07

`SlabUlsFlexureCalculator` consomme exclusivement `MEd` fourni par
`SlabStripAnalysis.internalForces.uls` : il ne refait ni l'analyse statique,
ni les actions ou combinaisons. La section est celle de la bande unitaire en
flexion positive, avec les armatures principales tendues en sous-face. Les
valeurs d'aire sont exprimées en `mm²/m` ; elles sont numériquement celles de
la section de 1000 mm, sans constituer une proposition de diamètre ou
d'espacement.

| Nom dans le code | Symbole | Origine | Signification, dépendances et limite | Unité |
|---|---|---|---|---|
| `SlabStripInternalForce.maximumMoment` | `MEd` | DERIVED | moment ELU reçu de SLAB-06, correspondant à la bande de 1 m ; converti explicitement en N·mm par le moteur commun avant les équations de section | kN·m |
| `SlabGeometry.calculationStripWidth` | `b` | FIXED_MVP | largeur de la section rectangulaire, toujours 1000 mm ; jamais une saisie utilisateur | mm |
| `SlabGeometry.thickness` | `h` | USER | hauteur totale de la dalle, provenant de SLAB-02 | mm |
| `SlabFlexuralDetailingAssumptions.preliminaryMainBarDiameter` | `φmain` | CONFIG | diamètre provisoire centralisé à 16 mm, partagé avec l'hypothèse initiale Poutre ; utilisé pour l'enrobage et `d`, à réévaluer quand SLAB-08 sélectionnera un diamètre réel | mm |
| `CoverCalculationResult.cNom` | `cnom` | DERIVED / PROFILE | calcul automatique commun EC2-05 à partir de la classe de béton, de l'exposition, de la durée de vie de 50 ans et de `φmain` ; aucune valeur d'enrobage n'est codée dans SLAB-07 | mm |
| `SlabEffectiveDepthResult.effectiveDepth` | `d` | DERIVED | `d = h - cnom - φmain / 2`, sans diamètre d'étrier car la nappe principale de dalle n'est pas enveloppée comme celle d'une poutre | mm |
| `concreteDesignStrength` | `fcd` | DERIVED / PROFILE | résistance de calcul commune, obtenue de la classe de béton et du profil français | MPa |
| `steelDesignStrength` | `fyd` | DERIVED / PROFILE | résistance de calcul commune, obtenue de la nuance d'acier et du profil français | MPa |
| `meanTensileConcreteStrength` | `fctm` | DERIVED | propriété de la classe de béton commune, utilisée par l'armature minimale | MPa |
| `reducedMoment` | `μEd` | DERIVED | `MEd_Nmm / (b × d² × fcd)`, par le calculateur commun de moment réduit | — |
| `neutralAxisRatio`, `neutralAxisDepth` | `ξ`, `x` | DERIVED | axe neutre issu du bloc comprimé rectangulaire EC2 commun ; le domaine simplement armé est refusé s'il n'est pas valide | — / mm |
| `leverArm` | `z` | DERIVED | bras de levier commun : `z = d - λ × x / 2` | mm |
| `requiredReinforcementArea` | `As,req` | DERIVED | acier requis par l'équilibre `MEd / (fyd × z)` pour la bande de 1 m | mm²/m |
| `minimumStrengthBasedReinforcementArea`, `minimumAbsoluteReinforcementArea`, `minimumReinforcementArea` | `As,min,1`, `As,min,2`, `As,min` | DERIVED / PROFILE | règle EC2 actuellement portée par le profil français : `max(0.26 × fctm/fyk × bt × d, 0.0013 × bt × d)`, avec `bt = b = 1000 mm` | mm²/m |
| `designReinforcementArea` | `As,design` | DERIVED | cible continue `max(As,req, As,min)` pour SLAB-08 ; pas une aire fournie | mm²/m |

Les calculateurs de moment réduit, axe neutre, bras de levier, armature requise,
minimum et domaine sont les briques communes déjà validées dans le moteur
Poutre. SLAB-07 les adapte à sa géométrie puis ne retourne que le résultat
Dalle. Aucun `As,provided`, ferraillage discret, armature secondaire,
cisaillement, ELS ou conformité n'est produit.

## Proposition de ferraillage principal Dalle SLAB-08

`SlabMainReinforcementProposalGenerator` consomme `As,design` de SLAB-07 puis
recalcule entièrement SLAB-07 pour chaque diamètre candidat. Le diamètre réel
est donc utilisé par le calcul commun d'enrobage, puis par `d`, `μ`, `ξ`, `x`,
`z`, `As,req`, `As,min` et `As,design` avant toute acceptation.

| Nom dans le code | Symbole | Origine | Signification et limite | Unité |
|---|---|---|---|---|
| `ReinforcementBarDiameterCatalog` | `φmain` | CONFIG | catalogue commun MVP : 8, 10, 12, 14, 16, 20, 25, 32 ; ce n'est pas une liste normative exhaustive | mm |
| `SlabMainReinforcementProposalConfiguration.candidateSpacings()` | `s` | CONFIG | discrétisation Dalle : 100, 125, 150, 175, 200, 250, 300 ; aucune limite normative d'espacement Dalle n'est prétendue à ce stade | mm |
| `SlabMainReinforcementProposal.barArea` | `Aφ` | DERIVED | aire géométrique commune : `π × φ² / 4` | mm² |
| `SlabMainReinforcementProposal.providedAreaPerMeter` | `As,provided` | DERIVED | `Aφ × 1000 / s` pour la bande de référence | mm²/m |
| `SlabUlsFlexureResult.designReinforcementArea` | `As,target` / `As,design` | DERIVED | cible SLAB-07 initiale puis cible finale recalculée avec le diamètre candidat | mm²/m |
| `SlabMainReinforcementProposal.overProvision` | — | DERIVED | `As,provided - As,design final`, toujours positif ou nul pour une proposition retenue | mm²/m |

Le classement est une politique applicative déterministe, non normative : plus
faible surdimensionnement, puis espacement le plus grand, puis diamètre le plus
petit, puis ordre stable du catalogue. Une proposition est retenue seulement si
`As,provided ≥ As,design` après recalcul. En l'absence de candidat valide, le
statut local est `NO_VALID_REINFORCEMENT_PROPOSAL` sans fallback. SLAB-08 ne
calcule ni armatures secondaires, ni ELS, ni conformité globale.

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

## Poids propre Poutre BEAM-CALC-01

| Nom | Symbole | Type / origine | Rôle et limite | Unité |
|---|---|---|---|---|
| `sectionArea` | `Ac` | nombre dérivé / DERIVED | aire brute de la section rectangulaire pour le poids propre ; dépend de `b` et `h`, convertis de mm en m | m² |
| `ReinforcedConcreteUnitWeight.value` | `γ_RC` | référence normative centralisée / CONFIG | poids volumique du béton armé de masse volumique normale ; valeur MVP `25`, issue du cadre EN 1991-1-1 ; distinct des classes de béton EC2 | kN/m³ |
| `characteristicLineLoad` | `Gk_self` | nombre dérivé / DERIVED | charge permanente linéaire de poids propre : `Ac × γ_RC` lorsque `includeSelfWeight` est vrai, sinon `0` explicitement | kN/m |
| `SelfWeightResult` | — | résultat traçable / DERIVED | conserve l'état d'inclusion, `b`, `h`, `Ac`, `γ_RC`, la formule et `Gk_self`, sans total permanent ni combinaison | unités explicites |

`SelfWeightCalculator` convertit les longueurs internes de mm vers m via
`LengthConverter`, sans arrondi intermédiaire. Ni `Gk_total`, ni `γG`, `γQ`,
`ψ`, combinaison, moment ou effort tranchant ne font partie de BEAM-CALC-01.

## Actions caractéristiques Poutre BEAM-CALC-02

| Nom | Symbole | Type / origine | Rôle et limite | Unité |
|---|---|---|---|---|
| `SelfWeightResult.characteristicLineLoad` | `Gk_self` | valeur dérivée / DERIVED | poids propre produit exclusivement par BEAM-CALC-01 ; l'état `included` est conservé | kN/m |
| `BeamPermanentLoads.additionalPermanentLoad` | `Gk_additional` | entrée validée / USER | charge permanente caractéristique additionnelle hors poids propre | kN/m |
| `CharacteristicPermanentActions.totalPermanentLoad` | `Gk_total` | valeur dérivée / DERIVED | somme exacte `Gk_self + Gk_additional`, destinée aux combinaisons futures | kN/m |
| `CharacteristicVariableAction.characteristicLoad` | `Qk` | entrée validée / USER | action variable caractéristique uniformément répartie, conservée sans transformation | kN/m |
| `CharacteristicVariableAction.category` | — | catégorie validée / FIXED_MVP | catégorie A conservée pour l'obtention future de `ψ0`, `ψ1`, `ψ2` auprès du profil | — |
| `BeamCharacteristicActionsResult` | — | résultat traçable / DERIVED | sépare actions permanentes (`Gk_self`, `Gk_additional`, `Gk_total`) et action variable (`Qk`, catégorie) | unités explicites |

`CharacteristicActionsCalculator` ne recalcule pas le poids propre et
n'applique aucun `γG`, `γQ` ou `ψ`. Il ne crée ni combinaison ELU/ELS, ni
moment, ni effort tranchant ; les valeurs restent des actions caractéristiques.

## Combinaison ELU fondamentale Poutre BEAM-CALC-03

| Nom | Symbole | Type / origine | Rôle et limite | Unité |
|---|---|---|---|---|
| `gammaGUnfavourable` | `γG,sup` | coefficient du profil / PROFILE | coefficient partiel des actions permanentes défavorables ; valeur MVP `1,35` | — |
| `gammaGFavourable` | `γG,inf` | coefficient du profil / PROFILE | coefficient partiel des actions permanentes favorables ; valeur MVP `1,00`, conservée mais non utilisée pour le cas gravitaire MVP | — |
| `gammaQ` | `γQ` | coefficient du profil / PROFILE | coefficient partiel de l'action variable principale ; valeur MVP `1,50` | — |
| `FundamentalUltimateCombinationExpression::EN1990_6_10` | EN 1990 6.10 | règle du profil / PROFILE | expression fondamentale retenue par la procédure française `a` ; 6.10a, 6.10b et `ξ` sont hors périmètre | — |
| `designLineLoad` | `wEd` | valeur dérivée / DERIVED | charge linéaire ELU : `γG,sup × Gk_total + γQ × Qk`, sans `ψ` sur l'unique action variable principale | kN/m |
| `BeamUltimateCombinationResult` | — | résultat traçable / DERIVED | conserve valeurs caractéristiques, facteurs, contributions, expression et `wEd` sans moment ni effort | kN/m |

Le calculateur utilise explicitement l'action permanente défavorable. Les
facteurs `ψ` restent dans le profil pour les actions accompagnatrices et les
ELS futurs ; aucune combinaison ELS, aucun `MEd` ni `VEd` ne sont créés ici.

## Combinaisons ELS Poutre BEAM-CALC-04

| Nom | Symbole | Type / origine | Rôle et limite | Unité |
|---|---|---|---|---|
| `wSlsCharacteristic` | `w_ELS,car` | valeur dérivée / DERIVED | `Gk_total + Qk` pour l'unique action variable principale ; `ψ0` ne s'y applique pas. EN 1990 6.14 | kN/m |
| `wSlsFrequent` | `w_ELS,freq` | valeur dérivée / DERIVED | `Gk_total + ψ1 × Qk`. Le facteur `ψ1` est obtenu par `DesignCodeProfile::combinationFactorsFor(category)`. EN 1990 6.15 | kN/m |
| `wSlsQuasiPermanent` | `w_ELS,qp` | valeur dérivée / DERIVED | `Gk_total + ψ2 × Qk`. Le facteur `ψ2` est obtenu par `DesignCodeProfile::combinationFactorsFor(category)`. EN 1990 6.16 | kN/m |
| `ServiceabilityCombinationExpression` | — | règle typée / PROFILE | références `EN1990_6_14`, `EN1990_6_15`, `EN1990_6_16`, sans chaînes libres dans le moteur | — |
| `BeamServiceabilityCombination` | — | résultat traçable / DERIVED | conserve `Gk_total`, `Qk`, le facteur variable, les contributions, la formule, la référence et la charge résultante | kN/m |
| `BeamServiceabilityCombinationsResult` | — | résultat agrégé / DERIVED | contient séparément les combinaisons caractéristique, fréquente et quasi-permanente | kN/m |

Les actions ELS restent non majorées : le terme permanent a un facteur de 1,0
dans les expressions EN 1990 et l'action variable principale vaut `Qk` dans
6.14. Aucun coefficient `γG` ou `γQ` ELU, aucun moment, effort tranchant,
contrôle de fissuration, de flèche ou de contraintes n'est produit. Le MVP ne
couvre qu'une action variable principale : les variables accompagnatrices et
l'emploi de `ψ0` restent hors périmètre.

## Moments fléchissants Poutre BEAM-CALC-05

| Nom | Symbole | Type / origine | Rôle et limite | Unité |
|---|---|---|---|---|
| `BeamGeometry.effectiveSpan` | `l_eff` | entrée validée / USER | portée efficace interne convertie de `mm` vers `m` avant l'analyse ; aucune autre portée n'est utilisée | mm puis m |
| `SIMPLY_SUPPORTED_UNIFORMLY_DISTRIBUTED_MAX_MOMENT_COEFFICIENT` | `1/8` | modèle mécanique / FIXED_MVP | coefficient analytique du maximum en travée pour une poutre simplement appuyée sous charge uniformément répartie ; ce n'est pas une valeur Eurocode | — |
| `maximumMomentPosition` | `x = l_eff / 2` | valeur dérivée / DERIVED | position du moment maximal au milieu de travée dans le modèle MVP | m |
| `MEd` / `ultimate.maximumMoment` | `MEd` | valeur dérivée / DERIVED | moment maximal ELU : `wEd × l_eff² / 8` | kN·m |
| `MCharacteristic` / `characteristic.maximumMoment` | `M_ELS,car` | valeur dérivée / DERIVED | moment maximal ELS caractéristique : `wSlsCharacteristic × l_eff² / 8` | kN·m |
| `MFrequent` / `frequent.maximumMoment` | `M_ELS,freq` | valeur dérivée / DERIVED | moment maximal ELS fréquent : `wSlsFrequent × l_eff² / 8` | kN·m |
| `MQuasiPermanent` / `quasiPermanent.maximumMoment` | `M_ELS,qp` | valeur dérivée / DERIVED | moment maximal ELS quasi-permanent : `wSlsQuasiPermanent × l_eff² / 8` | kN·m |
| `BeamBendingMoment` | — | résultat traçable / DERIVED | conserve charge, formule, référence de combinaison et moment maximal | kN/m, kN·m |
| `BeamBendingMomentResult` | — | résultat agrégé / DERIVED | conserve le modèle statique, `l_eff`, coefficient, position et les quatre moments | unités explicites |

`SimplySupportedBeamBendingMomentCalculator` ne s'applique qu'à
`SIMPLY_SUPPORTED` et `UNIFORMLY_DISTRIBUTED`; toute autre configuration est
refusée. La convention MVP retient un moment positif en travée pour les charges
gravitaire actuelles : `MEd` est donc une valeur positive de dimensionnement.
Il n'existe ici ni `VEd`, ni hauteur utile `d`, ni armature, ni résistance de
section `MRd`, ni vérification ELU ou ELS.

## Efforts tranchants Poutre BEAM-CALC-06

| Nom | Symbole | Type / origine | Rôle et limite | Unité |
|---|---|---|---|---|
| `SIMPLY_SUPPORTED_UNIFORMLY_DISTRIBUTED_MAX_SHEAR_COEFFICIENT` | `1/2` | modèle mécanique / FIXED_MVP | coefficient analytique du cisaillement maximal aux appuis pour une poutre simplement appuyée sous charge uniformément répartie ; ce n'est pas une valeur Eurocode | — |
| `VEd` / `ultimate.maximumAbsoluteShear` | `VEd` | valeur dérivée / DERIVED | effort tranchant maximal absolu ELU : `wEd × l_eff / 2` | kN |
| `VCharacteristic` / `characteristic.maximumAbsoluteShear` | `V_ELS,car` | valeur dérivée / DERIVED | effort tranchant maximal absolu ELS caractéristique : `wSlsCharacteristic × l_eff / 2` | kN |
| `VFrequent` / `frequent.maximumAbsoluteShear` | `V_ELS,freq` | valeur dérivée / DERIVED | effort tranchant maximal absolu ELS fréquent : `wSlsFrequent × l_eff / 2` | kN |
| `VQuasiPermanent` / `quasiPermanent.maximumAbsoluteShear` | `V_ELS,qp` | valeur dérivée / DERIVED | effort tranchant maximal absolu ELS quasi-permanent : `wSlsQuasiPermanent × l_eff / 2` | kN |
| `leftSupportShear`, `rightSupportShear` | `+Vmax`, `-Vmax` | valeurs dérivées / DERIVED | valeurs signées aux appuis gauche et droit ; le résultat principal conserve leur valeur absolue maximale | kN |
| `BeamShearForce` | — | résultat traçable / DERIVED | conserve charge, efforts aux appuis, formule et référence de combinaison | kN/m, kN |
| `BeamShearForceResult` | — | résultat agrégé / DERIVED | conserve `l_eff` convertie en m, le modèle statique, le coefficient et les quatre résultats de cisaillement | unités explicites |

`SimplySupportedBeamShearForceCalculator` applique le même domaine mécanique
que le calcul du moment : `SIMPLY_SUPPORTED` et `UNIFORMLY_DISTRIBUTED` sont
obligatoires. Pour les charges gravitaires MVP, l'effort est positif à l'appui
gauche et négatif à l'appui droit ; `VEd` est la valeur maximale absolue,
toujours positive. Aucun diagramme détaillé, aucune résistance `VRd,c`,
`VRd,s` ou `VRd,max`, aucun étrier, hauteur utile ou contrôle de cisaillement
EC2 n'est produit.

## Géométrie de flexion Poutre BEAM-FLEX-01

| Nom | Symbole | Type / origine | Rôle et limite | Unité |
|---|---|---|---|---|
| `overallDepth` / `BeamGeometry.height` | `h` | entrée validée / USER | hauteur totale de section, positive, utilisée directement en mm | mm |
| `CoverCalculationResult.cNom` / `nominalCover` | `c_nom` | résultat EC2-05 / DERIVED ou USER en manuel | enrobage nominal calculé ou imposé par le moteur EC2-05 ; la profondeur utile ne le recalcule jamais | mm |
| `BeamFlexuralDetailingAssumptions.transverseReinforcementDiameter` | `φ_st` | hypothèse de detailing / CONFIG | diamètre de l'étrier externe présumé. Valeur MVP `8 mm`, configurable ; ce n'est pas une prescription Eurocode et il sera remplacé plus tard par le ferraillage transversal réel | mm |
| `BeamFlexuralDetailingAssumptions.designTensionBarDiameter` | `φ_long,design` | hypothèse de detailing / CONFIG | diamètre longitudinal supposé pour le premier calcul en mode DESIGN. Valeur MVP `16 mm`, configurable ; ce n'est pas le ferraillage final | mm |
| `BeamLongitudinalReinforcement.tensionBarDiameter` | `φ_long` | entrée validée / USER | diamètre réellement fourni en mode VERIFICATION ; un unique lit est requis | mm |
| `LongitudinalBarDiameterSource` | — | DERIVED | `CONFIG` en DESIGN, `USER` en VERIFICATION ; trace la provenance du diamètre utilisé | — |
| `tensionSteelCentroidOffset` | `a_s` | valeur dérivée / DERIVED | distance entre la face tendue et le centre du lit tendu : `c_nom + φ_st + φ_long / 2` | mm |
| `effectiveDepth` | `d` | valeur dérivée / DERIVED | distance entre la fibre comprimée supérieure et le centre du lit tendu : `h - c_nom - φ_st - φ_long / 2` | mm |
| `BeamEffectiveDepthResult` | — | résultat traçable / DERIVED | conserve mode, `h`, `c_nom`, diamètres, source, `a_s`, `d` et formule, sans calcul de résistance | mm |

Sous le moment positif de travée MVP, la compression est en face supérieure et
la traction en face inférieure : `d` est donc mesuré depuis la face supérieure.
Le calculateur exige `h > 0`, `c_nom ≥ 0`, des diamètres positifs et `d > 0`.
Il est limité à un seul lit de barres longitudinales tendues. DESIGN utilise
l'hypothèse configurable `φ_long,design`; VERIFICATION utilise le diamètre
réel validé par BEAM-07. Aucune itération après choix des barres, aucun `As`,
`x`, `z`, `MRd` ni conformité en flexion n'est produit.

## Résistances de calcul pour flexion Poutre BEAM-FLEX-02

| Nom | Symbole | Type / origine | Rôle et limite | Unité |
|---|---|---|---|---|
| `ConcreteProperties.fck` | `fck` | propriété de référentiel / DERIVED | résistance caractéristique du béton de la classe choisie ; C30/37 vaut `30` | MPa (`N/mm²`) |
| `MaterialSafetyFactors.alphaCc` | `αcc` | profil normatif / PROFILE | coefficient du profil français, valeur MVP `1,00`, utilisé exclusivement via `ConcreteDesignStrengthCalculator` | — |
| `MaterialSafetyFactors.gammaC` | `γc` | profil normatif / PROFILE | coefficient partiel béton du profil français, valeur MVP `1,50` | — |
| `BeamFlexuralConcreteDesignStrength.fcd` | `fcd` | valeur dérivée / DERIVED | résistance de calcul `αcc × fck / γc`, calculée par `ConcreteDesignStrengthCalculator` | MPa (`N/mm²`) |
| `ReinforcementSteelProperties.fyk` | `fyk` | propriété de référentiel / DERIVED | limite caractéristique de la nuance choisie ; B500B vaut `500` | MPa (`N/mm²`) |
| `MaterialSafetyFactors.gammaS` | `γs` | profil normatif / PROFILE | coefficient partiel acier du profil français, valeur MVP `1,15` | — |
| `BeamFlexuralSteelDesignStrength.fyd` | `fyd` | valeur dérivée / DERIVED | résistance de calcul `fyk / γs`, calculée par `ReinforcementSteelDesignStrengthCalculator` | MPa (`N/mm²`) |
| `BeamFlexuralDesignStrengthsResult` | — | résultat traçable / DERIVED | réunit matériaux résolus, facteurs du profil et résistances de calcul, sans donnée de géométrie, charge ou moment | MPa |

`MPa` et `N/mm²` sont numériquement équivalents ; le moteur conserve `MPa`
dans cette étape, compatible avec les futures équations utilisant des longueurs
en `mm`. Les résistances ne dépendent pas du mode DESIGN ou VERIFICATION. Les
coefficients ne sont ni copiés dans les matériaux ni redéfinis par le module
Poutre : ils sont lus du `DesignCodeProfile` puis transmis aux calculateurs
EC2-03 existants.

## Moment réduit ELU Poutre BEAM-FLEX-03

| Nom | Symbole | Type / origine | Rôle et limite | Unité |
|---|---|---|---|---|
| `BeamBendingMoment.maximumMoment` | `MEd` | résultat BEAM-CALC-05 / DERIVED | moment ELU positif en travée, réutilisé sans recalcul | kN·m |
| `MomentConverter::NEWTON_MILLIMETRES_PER_KILONEWTON_METRE` | `10⁶` | conversion d'unité / CONFIG | facteur nommé de conversion `1 kN·m = 1 000 000 N·mm` | N·mm / kN·m |
| `designMomentInNewtonMillimetres` | `MEd_Nmm` | conversion d'unité / DERIVED | `MEd × 10⁶`, employé pour homogénéiser les unités de section | N·mm |
| `sectionWidth` | `b` | entrée validée / USER | largeur `BeamGeometry.width`, sans substitution par une autre dimension | mm |
| `effectiveDepth` | `d` | résultat BEAM-FLEX-01 / DERIVED | profondeur utile réellement calculée ; `h` ne la remplace jamais dans cette formule | mm |
| `concreteDesignStrength` | `fcd` | résultat BEAM-FLEX-02 / DERIVED | résistance de calcul béton en MPa, numériquement `N/mm²` | MPa (`N/mm²`) |
| `normalizationTerm` | `b × d² × fcd` | valeur dérivée / DERIVED | terme de normalisation homogène à un moment | N·mm |
| `reducedDesignMoment` | `μEd` | valeur dérivée / DERIVED | moment réduit `MEd_Nmm / (b × d² × fcd)` | sans dimension |
| `BeamReducedMomentResult` | — | résultat traçable / DERIVED | conserve toutes les entrées normalisées et `μEd`, sans limite ni statut de domaine | unités explicites |

`BeamReducedMomentCalculator` accepte `MEd ≥ 0`, `b > 0`, `d > 0` et
`fcd > 0`; un moment négatif n'est pas converti en valeur absolue. Cette étape
n'emploie ni `λ`, ni `η`, ni axe neutre `x`, rapport `x/d`, bras de levier `z`,
armature ou résistance de section. Toute limite de `μEd` reste future.

## Axe neutre de flexion Poutre BEAM-FLEX-04

| Nom | Symbole | Type / origine | Rôle et limite | Unité |
|---|---|---|---|---|
| `ConcreteRectangularStressBlockParameters.lambda` | `λ` | règle normative / DERIVED | profondeur relative du bloc rectangulaire simplifié : `0,8` si `fck ≤ 50 MPa`, sinon `0,8 - (fck - 50) / 400` jusqu'à `90 MPa` | sans dimension |
| `ConcreteRectangularStressBlockParameters.eta` | `η` | règle normative / DERIVED | intensité relative du bloc rectangulaire simplifié : `1,0` si `fck ≤ 50 MPa`, sinon `1,0 - (fck - 50) / 200` jusqu'à `90 MPa` | sans dimension |
| `characteristicConcreteStrength` | `fck` | résultat BEAM-FLEX-02 / DERIVED | résistance caractéristique qui sélectionne `λ` et `η`; les valeurs supérieures à `90 MPa` sont refusées | MPa |
| `reducedDesignMoment` | `μEd` | résultat BEAM-FLEX-03 / DERIVED | moment réduit transmis sans limite de ductilité ni statut | sans dimension |
| `radicand` | `1 - 2μEd / η` | valeur dérivée / DERIVED | terme sous racine ; doit être fini et supérieur ou égal à zéro | sans dimension |
| `neutralAxisRatio` | `ξ = x/d` | valeur dérivée / DERIVED | petite racine physique `[1 - sqrt(1 - 2μEd / η)] / λ` | sans dimension |
| `neutralAxisDepth` | `x` | valeur dérivée / DERIVED | profondeur de l'axe neutre depuis la fibre comprimée : `ξ × d` | mm |
| `BeamNeutralAxisResult` | — | résultat traçable / DERIVED | conserve `μEd`, `fck`, `λ`, `η`, radicand, `ξ`, `d` et `x`, sans bras de levier ni armature | unités explicites |

Les paramètres proviennent du bloc rectangulaire simplifié d'EN 1992-1-1
§3.1.7 et sont centralisés dans
`ConcreteRectangularStressBlockParametersCalculator`. La petite racine est
retenue pour la branche physique faiblement sollicitée d'une section simplement
armée. Un radicand négatif est refusé comme impossibilité mathématique du
modèle; ce n'est pas une limite de ductilité. Aucun `ξlim`, `xlim`, `μlim`,
`z`, `As` ou `MRd` n'est introduit à cette étape.

## Bras de levier de flexion Poutre BEAM-FLEX-05

| Nom | Symbole | Type / origine | Rôle et limite | Unité |
|---|---|---|---|---|
| `effectiveDepth` | `d` | résultat BEAM-FLEX-01 / DERIVED | profondeur utile reprise sans recalcul | mm |
| `neutralAxisDepth` | `x` | résultat BEAM-FLEX-04 / DERIVED | axe neutre repris sans recalcul, avec `0 ≤ x ≤ d` | mm |
| `lambda` | `λ` | résultat BEAM-FLEX-04 / DERIVED | paramètre du bloc rectangulaire EC2, strictement positif | sans dimension |
| `compressionBlockDepth` | `λx` | valeur dérivée / DERIVED | profondeur du bloc rectangulaire équivalent de compression | mm |
| `compressionResultantDepth` | `λx / 2` | valeur dérivée / DERIVED | position de la résultante de compression depuis la fibre comprimée | mm |
| `leverArm` | `z` | valeur dérivée / DERIVED | bras de levier interne : `d - λx / 2`, équivalent à `d × (1 - λξ / 2)` | mm |
| `BeamLeverArmResult` | — | résultat traçable / DERIVED | conserve `d`, `x`, `ξ`, `λ`, `λx`, `λx/2` et `z`, sans armature ni résistance | mm |

Le calculateur ne plafonne pas `z` à une valeur pratique telle que `0,95d` :
il applique directement la géométrie du bloc rectangulaire. Il exige un bras de
levier strictement positif et des résultats d'axe neutre cohérents avec `d`.
Aucun `As_req`, `As_min`, `MRd`, limite de ductilité ou statut de conformité
n'est produit.

## Armature longitudinale requise Poutre BEAM-FLEX-06

| Nom | Symbole | Type / origine | Rôle et limite | Unité |
|---|---|---|---|---|
| `BeamBendingMoment.maximumMoment` | `MEd` | résultat BEAM-CALC-05 / DERIVED | moment ELU repris sans recalcul ; doit être positif ou nul | kN·m |
| `designMomentInNewtonMillimetres` | `MEd_Nmm` | conversion / DERIVED | `MEd × 10⁶`, obtenu uniquement via `MomentConverter` | N·mm |
| `BeamFlexuralSteelDesignStrength.fyd` | `fyd` | résultat BEAM-FLEX-02 / DERIVED | résistance de calcul de l'acier, numériquement en `N/mm²`; strictement positive | MPa (`N/mm²`) |
| `BeamLeverArmResult.leverArm` | `z` | résultat BEAM-FLEX-05 / DERIVED | bras de levier interne, strictement positif | mm |
| `steelLeverArmProduct` | `fyd × z` | valeur dérivée / DERIVED | terme d'équilibre acier–bras de levier | N/mm |
| `requiredReinforcementArea` | `As_req` | valeur dérivée / DERIVED | aire théorique d'armatures longitudinales tendues : `MEd_Nmm / (fyd × z)` | mm² |
| `BeamRequiredTensionReinforcementResult` | — | résultat traçable / DERIVED | conserve `MEd`, `MEd_Nmm`, `fyd`, `z`, leur produit et `As_req` | unités explicites |

`As_req` est l'aire requise par le seul équilibre ELU de flexion. Elle vaut
`0 mm²` si `MEd = 0` et ne comprend ni armature minimale réglementaire
(`As_min`, future BEAM-FLEX-07), ni choix de barres/diamètre, ni armature
fournie, ni résistance `MRd` ou statut de conformité.

## Armature longitudinale minimale Poutre BEAM-FLEX-07

| Nom | Symbole | Type / origine | Rôle et limite | Unité |
|---|---|---|---|---|
| `ConcreteProperties.fctm` | `fctm` | référentiel matériau / DERIVED | résistance moyenne en traction de la classe béton ; C30/37 vaut `2,9` | MPa (`N/mm²`) |
| `ReinforcementSteelProperties.fyk` | `fyk` | référentiel matériau / DERIVED | limite caractéristique de l'acier ; B500B vaut `500`. `fyd` n'est pas utilisé par cette règle | MPa (`N/mm²`) |
| `tensionZoneMeanWidth` | `bt` | géométrie dérivée / DERIVED | largeur moyenne de la zone tendue ; pour la seule section rectangulaire MVP en moment positif, `bt = b`, sans généralisation aux sections T/L | mm |
| `effectiveDepth` | `d` | résultat BEAM-FLEX-01 / DERIVED | profondeur utile reprise sans recalcul ; `h` ne la remplace jamais | mm |
| `BeamLongitudinalReinforcementRequirements.minimumReinforcementStrengthCoefficient` | `0,26` | profil normatif / PROFILE | coefficient du terme `fctm/fyk`, défini par EN 1992-1-1 §9.2.1.1(1) et fourni par le profil français | sans dimension |
| `BeamLongitudinalReinforcementRequirements.minimumReinforcementRatio` | `0,0013` | profil normatif / PROFILE | coefficient du minimum absolu, défini par EN 1992-1-1 §9.2.1.1(1) et fourni par le profil français | sans dimension |
| `strengthBasedMinimum` | `As_min,strength` | valeur dérivée / DERIVED | `0,26 × (fctm / fyk) × bt × d` | mm² |
| `absoluteMinimum` | `As_min,ratio` | valeur dérivée / DERIVED | `0,0013 × bt × d` | mm² |
| `requiredMinimum` | `As_min` | valeur dérivée / DERIVED | maximum des deux termes, sans fusion avec `As_req` | mm² |
| `MinimumTensionReinforcementGoverningCriterion` | — | résultat dérivé / DERIVED | `FCTM_FYK` si le premier terme gouverne, sinon `ABSOLUTE_RATIO` | — |
| `BeamMinimumTensionReinforcementResult` | — | résultat traçable / DERIVED | conserve matériaux, `bt`, `d`, les deux termes, `As_min` et le critère gouvernant | unités explicites |

Les paramètres sont centralisés dans le `DesignCodeProfile`, à travers
`BeamLongitudinalReinforcementRequirements`, afin qu'un autre profil national
puisse les remplacer. `As_min` est une exigence réglementaire indépendante de
`As_req` : BEAM-FLEX-07 ne calcule aucun `max(As_req, As_min)`, aucun choix de
barres, aucune comparaison avec `As_prov`, aucun `MRd` ni conformité.

## Domaine de validité simplement armé Poutre BEAM-FLEX-08

| Nom | Symbole | Type / origine | Rôle et limite | Unité |
|---|---|---|---|---|
| `ReinforcementSteelProperties.es` | `Es` | référentiel matériau / DERIVED | module d'élasticité de l'acier d'armature ; B500B vaut `200000` | MPa (`N/mm²`) |
| `ConcreteUltimateStrainParameters.ultimateStrain` | `εcu3` | règle normative / DERIVED | déformation ultime béton du diagramme EC2 : `0,0035` si `fck ≤ 50 MPa`, sinon `[2,6 + 35 × ((90 - fck) / 100)^4] × 0,001` jusqu'à `90 MPa` | sans dimension |
| `steelDesignYieldStrain` | `εyd` | valeur dérivée / DERIVED | déformation associée à `fyd` : `fyd / Es` | sans dimension |
| `tensionSteelStrain` | `εs` | valeur dérivée / DERIVED | compatibilité des déformations : `εcu3 × (1 - ξ) / ξ`; vaut `null` si `x = 0` | sans dimension |
| `yieldingNeutralAxisLimit` | `ξ_yield` | valeur dérivée / DERIVED | limite liée à l'atteinte de `fyd` : `εcu3 / (εcu3 + εyd)` | sans dimension |
| `tensionSteelReachesDesignYield` | — | état dérivé / DERIVED | `true` si `εs ≥ εyd`, `false` hors domaine, `null` si charge nulle et `x = 0` | — |
| `singlyReinforcedModelValid` | — | état dérivé / DERIVED | validité de l'hypothèse `σs = fyd` utilisée pour `As_req`; ne constitue pas une conformité globale | — |
| `BeamFlexuralDomainCheckCalculator::STRAIN_COMPARISON_TOLERANCE` | — | constante numérique / CONFIG | tolérance `1e-12` appliquée seulement à l'égalité de déformations à la frontière | sans dimension |
| `BeamFlexuralDomainCheckResult` | — | résultat traçable / DERIVED | conserve les déformations, `x`, `d`, `ξ`, les valeurs acier/béton et le statut de domaine | unités explicites |

Cette étape n'applique pas de limite universelle `ξ ≤ 0,45` : elle contrôle
uniquement `εs ≥ εyd`. Elle ne produit ni `MRd`, ni armature comprimée, ni
`max(As_req, As_min)`, ni choix de barres ou conformité globale.

## Aire cible de ferraillage Poutre BEAM-REBAR-01

| Nom | Symbole | Type / origine | Rôle et limite | Unité |
|---|---|---|---|---|
| `BeamRequiredTensionReinforcementResult.requiredReinforcementArea` | `As_req` | résultat BEAM-FLEX-06 / DERIVED | besoin théorique d'équilibre ELU, réutilisé sans recalcul | mm² |
| `BeamMinimumTensionReinforcementResult.requiredMinimum` | `As_min` | résultat BEAM-FLEX-07 / DERIVED | exigence minimale réglementaire, réutilisée sans recalcul | mm² |
| `targetArea` | `As_target` | valeur dérivée / DERIVED | aire continue minimale à fournir par le futur ferraillage : `max(As_req, As_min)` | mm² |
| `BeamReinforcementTargetGoverningRequirement` | — | état dérivé / DERIVED | `FLEXURAL_DEMAND`, `MINIMUM_REINFORCEMENT` ou `EQUAL_REQUIREMENTS`, pour expliquer le maximum retenu | — |
| `BeamRequiredReinforcementAreaResult` | — | résultat traçable / DERIVED | conserve les deux exigences d'entrée, `As_target` et le critère gouvernant | unités explicites |

`BeamReinforcementTargetCalculator` exige que le domaine BEAM-FLEX-08 soit
valide ; sinon il refuse de retourner une aire cible. Il ne calcule aucune
barre, aucun diamètre, aucune aire fournie `As_prov`, aucun logement
géométrique, `MRd` ou conformité. Aucune marge ni aucun arrondi n'est appliqué.

## Candidats de ferraillage Poutre BEAM-REBAR-02

| Nom | Symbole | Type / origine | Rôle et limite | Unité |
|---|---|---|---|---|
| `BeamReinforcementProposalConfiguration.minimumTensionBarCount` | `n_min` | configuration / CONFIG | nombre minimal de barres tendues proposé par le MVP : `2`; choix de detailing, non règle Eurocode générique | — |
| `BeamReinforcementProposalConfiguration.maximumTensionBarCount` | `n_max` | configuration / CONFIG | nombre maximal de barres tendues proposé par le MVP : `8`; le générateur ne l'augmente jamais | — |
| `barCount` | `n` | valeur énumérée / DERIVED | nombre de barres homogènes d'un candidat, entre `n_min` et `n_max` | — |
| `barDiameter` | `φ` | catalogue / CONFIG | diamètre provenant exclusivement de `ReinforcementBarDiameterCatalog` | mm |
| `barArea` | `Aφ` | valeur dérivée / DERIVED | aire d'une barre, réutilisant `BeamLongitudinalReinforcement` : `π × φ² / 4` | mm² |
| `providedArea` | `As_prov` | valeur dérivée / DERIVED | aire fournie par un candidat : `n × π × φ² / 4` ; seuls les candidats avec `As_prov ≥ As_target` sont conservés | mm² |
| `excessArea` | `As_excess` | valeur dérivée / DERIVED | surplus d'aire : `As_prov - As_target`, utilisé au classement | mm² |
| `utilizationRatio` | — | valeur dérivée / DERIVED | `As_target / As_prov`; ratio de classement, pas une conformité structurelle. Il vaut `0` pour une cible nulle | sans dimension |
| `BeamReinforcementCandidatesStatus` | — | état dérivé / DERIVED | `CANDIDATES_AVAILABLE` ou `NO_REINFORCEMENT_CANDIDATE` si le catalogue et les bornes configurées ne suffisent pas | — |
| `BeamReinforcementProposalCandidate` | — | résultat traçable / DERIVED | conserve une combinaison `n × φ`, ses aires et son ratio, sans géométrie | unités explicites |
| `BeamReinforcementCandidatesResult` | — | résultat traçable / DERIVED | conserve cible, catalogue, bornes, statut, candidats triés et `candidateCount` | unités explicites |

Le classement est déterministe : excès croissant, puis nombre de barres
croissant, puis diamètre croissant. Aucune vérification de logement, espacement,
enrobage, collision ou plusieurs lits n'est appliquée avant BEAM-REBAR-03.

## Géométrie d'un lit de ferraillage Poutre BEAM-REBAR-03

| Nom | Symbole | Type / origine | Rôle et limite | Unité |
|---|---|---|---|---|
| `BeamReinforcementDetailingAssumptions.maximumAggregateSize` | `d_g` | hypothèse de detailing / CONFIG | dimension nominale maximale des granulats du MVP : `20`; ce n'est pas une propriété de résistance du béton | mm |
| `ReinforcementSpacingRequirements.barDiameterFactor` | `k1` | profil normatif / PROFILE | facteur du terme lié au diamètre, valeur française recommandée `1,0` | sans dimension |
| `ReinforcementSpacingRequirements.aggregateSizeAllowance` | `k2` | profil normatif / PROFILE | majoration liée aux granulats, valeur française recommandée `5` | mm |
| `ReinforcementSpacingRequirements.absoluteMinimumClearSpacing` | — | profil normatif / PROFILE | minimum absolu de l'expression EC2 §8.2(2), valeur `20` | mm |
| `availableWidth` | `b_available` | valeur dérivée / DERIVED | largeur libre entre faces intérieures des branches d'étrier : `b - 2(c_nom + φ_st)` | mm |
| `minimumClearSpacing` | `a_min` | règle normative / DERIVED | espacement **libre** : `max(k1φ, d_g + k2, 20 mm)` ; ne pas confondre avec le pas axe-à-axe | mm |
| `requiredWidth` | `b_required` | valeur dérivée / DERIVED | largeur d'un lit régulier : `nφ + (n - 1)a_min` | mm |
| `remainingWidth` | — | valeur dérivée / DERIVED | marge géométrique `b_available - b_required`; négative en cas de déficit | mm |
| `BeamReinforcementSpacingGoverningCriterion` | — | état dérivé / DERIVED | `BAR_DIAMETER`, `AGGREGATE_SIZE`, `ABSOLUTE_MINIMUM` ou `TIE` | — |
| `BeamReinforcementGeometryRejectionReason` | — | état dérivé / DERIVED | `INSUFFICIENT_HORIZONTAL_SPACE` pour un candidat trop large | — |
| `BeamReinforcementGeometryCheckResult` | — | résultat traçable / DERIVED | conserve candidat, largeurs, espacement, critère et statut d'admissibilité | unités explicites |

Le modèle d'étrier est volontairement simplifié : il représente seulement ses
branches verticales par `c_nom + φ_st` depuis chaque parement. Aucun rayon de
cintrage, angle d'étrier, collision locale, second lit ou coordonnées de barres
n'est vérifié. Les listes acceptée et rejetée gardent l'ordre de BEAM-REBAR-02.

## Recalcul des candidats de ferraillage Poutre BEAM-REBAR-04

| Nom | Symbole | Type / origine | Rôle et limite | Unité |
|---|---|---|---|---|
| `LongitudinalBarDiameterSource::CANDIDATE` | `φ_long` | diamètre de candidat / DERIVED | identifie le diamètre fixe porté par `originalCandidate`, en remplacement de l'hypothèse initiale `CONFIG` dans le seul recalcul DESIGN | mm |
| `candidateEffectiveDepth` (`BeamEffectiveDepthResult.effectiveDepth`) | `d_candidate` | valeur dérivée / DERIVED | profondeur utile recalculée par BEAM-FLEX-01 avec `φ_long = candidate.barDiameter`, en réutilisant le `c_nom` EC2-05 existant | mm |
| `candidateReducedMoment` | `μEd_candidate` | valeur dérivée / DERIVED | BEAM-FLEX-03 réévalué avec le même `MEd`, `b`, `fcd` et `d_candidate` | sans dimension |
| `candidateNeutralAxis` | `ξ_candidate`, `x_candidate` | valeur dérivée / DERIVED | résultat BEAM-FLEX-04 associé au nouveau moment réduit | sans dimension, mm |
| `candidateLeverArm` | `z_candidate` | valeur dérivée / DERIVED | résultat BEAM-FLEX-05 associé au nouveau `d` et axe neutre | mm |
| `candidateAsReq` (`BeamRequiredTensionReinforcementResult.requiredReinforcementArea`) | `As_req,candidate` | valeur dérivée / DERIVED | besoin d'équilibre BEAM-FLEX-06 réévalué avec `z_candidate` | mm² |
| `candidateAsMin` (`BeamMinimumTensionReinforcementResult.requiredMinimum`) | `As_min,candidate` | valeur dérivée / DERIVED | minimum BEAM-FLEX-07 réévalué, car il dépend de `d_candidate` | mm² |
| `candidateAsTarget` (`BeamRequiredReinforcementAreaResult.targetArea`) | `As_target,candidate` | valeur dérivée / DERIVED | `max(As_req,candidate, As_min,candidate)` obtenu par BEAM-REBAR-01 seulement lorsque le domaine simplement armé est valide | mm² |
| `candidateSufficient` (`sufficientAfterRecalculation`) | — | état dérivé / DERIVED | vrai seulement si le domaine BEAM-FLEX-08 est valide et `As_prov ≥ As_target,candidate` | — |
| `BeamReinforcementCandidateRecalculationStatus` | — | enum / DERIVED | `VALID_AFTER_RECALCULATION`, `INSUFFICIENT_AFTER_RECALCULATION` ou `INVALID_SINGLY_REINFORCED_DOMAIN` | — |
| `BeamReinforcementCandidateRecalculationResult` | — | résultat traçable / DERIVED | conserve le candidat fixe, `d` et `As_req` initiaux, toute la chaîne recalculée, `As_prov` issu du candidat et le statut | unités explicites |

`As_prov` est créé par BEAM-REBAR-02 et n'est jamais recalculé ni modifié dans
cette étape. BEAM-REBAR-04 traite chaque candidat admissible de BEAM-REBAR-03
une seule fois et conserve l'ordre d'entrée dans les groupes valides et rejetés.
Il ne calcule ni `MRd`, ni conformité structurelle globale, ni candidat final.

## Résistance béton au cisaillement Poutre BEAM-SHEAR-01

| Nom | Symbole | Type / origine | Rôle et limite | Unité |
|---|---|---|---|---|
| `webWidth` | `bw` | géométrie / USER | largeur d'âme. Pour la seule section rectangulaire MVP, `bw = BeamGeometry.width = b`; ne pas généraliser aux sections T ou L | mm |
| `longitudinalReinforcementArea` | `Asl` | candidat ou ferraillage fourni / DERIVED | aire des armatures longitudinales tendues réellement évaluées et considérées ancrées au droit de la section; n'est jamais remplacée automatiquement par `As_req` ou `As_target` | mm² |
| `longitudinalReinforcementRatioRaw` | `ρl,raw` | valeur dérivée / DERIVED | `Asl / (bw × d)` avant application de la limite §6.2.2 | sans dimension |
| `longitudinalReinforcementRatioUsed` | `ρl` | valeur dérivée / DERIVED | `min(ρl,raw, 0,02)` utilisé dans l'expression principale ; le résultat conserve l'indicateur de plafond | sans dimension |
| `sizeEffectFactorRaw` | `k_raw` | valeur dérivée / DERIVED | `1 + sqrt(200 / d)` avec `d` en mm | sans dimension |
| `sizeEffectFactor` | `k` | valeur dérivée / DERIVED | `min(k_raw, 2,0)` ; le résultat conserve l'indicateur de plafond | sans dimension |
| `normalForce` | `NEd` | hypothèse MVP / FIXED_MVP | effort normal de calcul. Le MVP impose `0 kN` et refuse tout autre cas, sans supprimer la donnée du résultat | kN |
| `concreteArea` | `Ac` | valeur dérivée / DERIVED | aire de béton rectangulaire `b × h`, employée conceptuellement pour `σcp`; ce n'est pas `bw × d` | mm² |
| `meanCompressiveStress` | `σcp` | valeur dérivée / DERIVED | `NEd / Ac`, avec conversion N/mm². Vaut explicitement `0 MPa` dans le MVP | MPa |
| `BeamConcreteShearResistanceRequirements.concreteShearResistanceCoefficient` | `CRd,c` | paramètre national / PROFILE | coefficient français retenu pour §6.2.2 : `0,12` (`0,18 / γc` avec `γc = 1,50`) | sans dimension |
| `BeamConcreteShearResistanceRequirements.compressionStressCoefficient` | `k1` cisaillement | paramètre national / PROFILE | coefficient de `σcp`, valeur recommandée `0,15`; distinct de `k1` d'espacement EC2 §8.2 | sans dimension |
| `minimumShearStress` | `vmin` | règle de profil / PROFILE | `0,035 × k^(3/2) × sqrt(fck)`, avec `fck` en MPa | MPa |
| `mainShearResistanceStress` | `vRd,c,main` | valeur dérivée / DERIVED | `CRd,c × k × (100 × ρl × fck)^(1/3) + k1 × σcp` | MPa |
| `minimumShearResistanceStress` | `vRd,c,min` | valeur dérivée / DERIVED | `vmin + k1 × σcp` | MPa |
| `governingResistanceStress` | `vRd,c` | valeur dérivée / DERIVED | maximum de l'expression principale et de la borne minimale, avec critère gouvernant explicite | MPa |
| `concreteShearResistance` | `VRd,c` | valeur dérivée / DERIVED | `vRd,c × bw × d`, converti de N en kN via `ForceConverter` | kN |
| `utilizationConcreteShear` | `VEd / VRd,c` | valeur dérivée / DERIVED | taux sans dimension ; aucune multiplication par 100 dans le domaine | sans dimension |
| `BeamConcreteShearResistanceStatus` | — | état dérivé / DERIVED | indique seulement si le premier contrôle nécessite de poursuivre vers le dimensionnement d'armatures transversales ; n'est jamais une conformité globale | — |

BEAM-SHEAR-01 utilise le `VEd` déjà calculé par BEAM-CALC-06 et le `d` du
ferraillage réellement évalué. En DESIGN, `Asl` est donc l'`As_prov` du
candidat transmis; en VERIFICATION, c'est l'aire réellement saisie. L'ancrage
des armatures au droit de la section n'est pas encore modélisé : le MVP le
suppose satisfait. Aucun `Asw/s`, `VRd,s`, `VRd,max`, étrier ou conclusion de
conformité globale n'est produit.

## Dimensionnement théorique des étriers Poutre BEAM-SHEAR-02

| Nom | Symbole | Type / origine | Rôle et limite | Unité |
|---|---|---|---|---|
| `BeamShearDesignAssumptions.designCotTheta` | `cot θ_design` | stratégie de dimensionnement / CONFIG | valeur MVP `2,5`, injectée et distincte des bornes normatives du profil; ne constitue pas une constante EC2 universelle | sans dimension |
| `minimumCotTheta`, `maximumCotTheta` | `cot θ` | paramètres normatifs / PROFILE | domaine autorisé par le profil MVP : `[1,0 ; 2,5]` | sans dimension |
| `θ` | `θ` | paramètre du modèle / DERIVED | angle de la bielle comprimée par rapport à l'axe longitudinal; le moteur conserve `cot θ` et ne convertit pas cet angle en degrés | — |
| `stirrupSteelCharacteristicStrength` | `fyk` | matériau / DERIVED | limite caractéristique B500B, également utilisée par le minimum transversal | MPa |
| `stirrupSteelDesignStrength` | `fywd` | matériau et profil / DERIVED | résistance de calcul dérivée par `ReinforcementSteelDesignStrengthCalculator`, soit `fyk / γs`; le MVP utilise le même B500B pour barres longitudinales et étriers | MPa |
| `minimumShearReinforcementRatio` | `ρw,min` | règle normative / DERIVED | `0,08 × sqrt(fck) / fyk` pour étriers verticaux; `fyk`, non `fywd`, est imposé ici | sans dimension |
| `requiredShearReinforcementPerLength` | `Asw/s_req` | valeur dérivée / DERIVED | `VEd / (z × fywd × cot θ)` seulement si BEAM-SHEAR-01 requiert une armature de calcul; vaut explicitement `0` sinon | mm²/mm |
| `minimumShearReinforcementPerLength` | `Asw/s_min` | règle normative / DERIVED | `ρw,min × bw`, minimum applicable au MVP de poutre standard sans exemption modélisée | mm²/mm |
| `targetShearReinforcementPerLength` | `Asw/s_target` | valeur dérivée / DERIVED | maximum de `Asw/s_req` et `Asw/s_min`, avec critère gouvernant explicite | mm²/mm |
| `targetShearResistance` | `VRd,s_target` | valeur dérivée / DERIVED | résistance théorique liée à la quantité cible : `(Asw/s_target) × z × fywd × cot θ`, convertie en kN | kN |
| `BeamShearReinforcementGoverningRequirement` | — | enum / DERIVED | `SHEAR_DEMAND`, `MINIMUM_TRANSVERSE_REINFORCEMENT` ou `EQUAL_REQUIREMENTS` | — |

`z` est exclusivement le bras de levier réel BEAM-FLEX-05 / BEAM-REBAR-04,
sans remplacement par `0,9d`. La formule de demande utilise `VEd` entier : le
moteur ne calcule jamais `VEd - VRd,c` et n'additionne jamais `VRd,c + VRd,s`.
Le résultat est une densité théorique, sans diamètre, nombre de branches ou
espacement réel d'étrier. `VRd,max` et toute conformité globale restent hors
périmètre jusqu'à BEAM-SHEAR-03.

## Résistance maximale au cisaillement Poutre BEAM-SHEAR-03

| Nom | Symbole | Type / origine | Rôle et limite | Unité |
|---|---|---|---|---|
| `concreteShearStrengthReductionFactor` | `ν1` | règle de profil / PROFILE | réduction de résistance du béton fissuré : `0,6 × (1 - fck / 250)` | sans dimension |
| `alphaCw` | `αcw` | règle de profil / PROFILE | coefficient d'état de contrainte de la membrure comprimée; le MVP non précontraint avec `NEd = 0` utilise explicitement `1,0` | sans dimension |
| `tanTheta` | `tan θ` | valeur dérivée / DERIVED | `1 / cot θ`, calculé sans conversion en degrés | sans dimension |
| `maximumShearResistance` | `VRd,max` | règle normative / DERIVED | résistance limitée par l'écrasement des bielles : `αcw × bw × z × ν1 × fcd / (cot θ + tan θ)` puis conversion N → kN | kN |
| `utilizationMaximumShear` | `VEd / VRd,max` | valeur dérivée / DERIVED | taux sans dimension du seul contrôle de bielles comprimées | sans dimension |
| `BeamMaximumShearResistanceStatus` | — | enum / DERIVED | `MAXIMUM_SHEAR_RESISTANCE_OK` ou `MAXIMUM_SHEAR_RESISTANCE_EXCEEDED`; ne constitue jamais une conformité globale | — |

BEAM-SHEAR-03 reçoit `VEd`, `bw`, `z` et `cot θ` déjà portés par le résultat
BEAM-SHEAR-02. Il n'emploie ni `0,9d` ni une nouvelle stratégie d'angle. Une
valeur `VEd > VRd,max` ne déclenche aucune augmentation automatique de
`Asw/s`, car davantage d'étriers ne supprime pas la limitation des bielles
comprimées. Le moteur ne calcule pas `VRd,c + VRd,s`, `VRd,c + VRd,max`, ni une
résistance globale minimale.

## Proposition discrète d'étriers Poutre BEAM-SHEAR-04

| Nom | Symbole | Type / origine | Rôle et limite | Unité |
|---|---|---|---|---|
| `BeamStirrupProposalConfiguration.diameters` | `φ_st` | catalogue / CONFIG | diamètres applicatifs MVP `[6, 8, 10, 12]`; ce n'est pas une liste normative exhaustive | mm |
| `stirrupLegs` | `n_legs` | configuration / CONFIG | nombre fixe de branches efficaces MVP : `2`; aucune disposition 3/4 branches ou cadres multiples | — |
| `spacings` | `s` | catalogue / CONFIG | pas discrets supportés `[100, 125, 150, 175, 200, 225, 250, 300, 350, 400]`, sans préférence normative | mm |
| `providedArea` | `Asw` | valeur dérivée / DERIVED | aire de l'étrier : `n_legs × π × φ_st² / 4` | mm² |
| `providedAreaPerLength` | `Asw/s_prov` | valeur dérivée / DERIVED | `Asw / s`, comparé sans arrondi à la cible BEAM-SHEAR-02 | mm²/mm |
| `maximumLongitudinalSpacing` | `s_l,max` | règle de profil / DERIVED | `0,75 × d` pour étriers verticaux | mm |
| `transverseLegSpacing` | `s_t` | géométrie dérivée / DERIVED | distance représentative entre axes : `bw - 2(c_nom + φ_st/2)` | mm |
| `maximumTransverseLegSpacing` | `s_t,max` | règle de profil / DERIVED | `min(0,75 × d, 600 mm)` | mm |
| `reinforcementExcess` | — | valeur dérivée / DERIVED | `Asw/s_prov - Asw/s_target`, premier critère de classement | mm²/mm |
| `providedShearResistance` | `VRd,s_prov` | valeur dérivée / DERIVED | formule VRd,s réutilisée de BEAM-SHEAR-02 pour la disposition réelle | kN |

Une proposition est rejetée si le catalogue, la quantité, `s_l,max`, `s_t,max`,
`VRd,s` lorsque la demande gouverne, ou `VRd,max` échoue. Les candidats admis
sont classés par excès croissant, puis pas croissant, puis diamètre croissant;
la recommandation est une préférence applicative déterministe, pas une
conformité globale de poutre.

## Limitation locale des contraintes ELS Poutre BEAM-SLS-01

| Nom | Symbole | Type / origine | Rôle et limite | Unité |
|---|---|---|---|---|
| `modularRatio` | `αe` | valeur dérivée / DERIVED | rapport modulaire instantané de la section fissurée : `Es / Ecm`. Il ne remplace pas `Ecm` par un module effectif de long terme. | sans dimension |
| `crackedNeutralAxisDepth` | `x_sls` | valeur dérivée / DERIVED | profondeur de l'axe neutre de la section fissurée, racine physique de `(b / 2)x² + αe As x - αe As d = 0`. Distincte de l'axe neutre ELU. | mm |
| `crackedSecondMomentOfArea` | `I_cr` | valeur dérivée / DERIVED | inertie transformée de la section fissurée : `b x_sls³ / 3 + αe As(d - x_sls)²`. Le béton tendu est négligé. | mm⁴ |
| `concreteCharacteristic.stress` | `σc,char` | valeur dérivée / DERIVED | contrainte maximale de compression béton de la section fissurée sous `MCharacteristic` : `M x_sls / I_cr`. | MPa |
| `steelCharacteristic.stress` | `σs,char` | valeur dérivée / DERIVED | contrainte de traction de l'acier sous `MCharacteristic` : `αe M(d - x_sls) / I_cr`. | MPa |
| `concreteQuasiPermanent.stress` | `σc,qp` | valeur dérivée / DERIVED | contrainte maximale de compression béton sous `MQuasiPermanent`, calculée avec le même modèle instantané. | MPa |
| `BeamServiceStressRequirements.concreteCharacteristicStressLimitFactor` | `k1` | paramètre national / PROFILE | facteur de limite béton caractéristique : `0,60`; limite `k1 × fck`. | sans dimension |
| `BeamServiceStressRequirements.concreteQuasiPermanentStressLimitFactor` | `k2` | paramètre national / PROFILE | facteur de limite béton quasi-permanente : `0,45`; limite `k2 × fck`. Un dépassement signale localement la sortie du domaine associé au fluage linéaire. | sans dimension |
| `BeamServiceStressRequirements.reinforcementCharacteristicStressLimitFactor` | `k3` | paramètre national / PROFILE | facteur de limite acier caractéristique : `0,80`; limite `k3 × fyk`, jamais `fyd`. | sans dimension |
| `BeamServiceStressCheck.utilization` | — | valeur dérivée / DERIVED | taux local `stress / limit`; il est conservé non arrondi et n'est pas une conformité globale de la poutre. | sans dimension |
| `BeamServiceStressCheckStatus` | — | état dérivé / DERIVED | `COMPLIANT`, `NOT_COMPLIANT`, `NOT_APPLICABLE` ou `NOT_CHECKED`. La vérification fréquente est `NOT_APPLICABLE` dans cette étape : aucune limite n'est inventée. | — |

BEAM-SLS-01 impose `sectionModel = CRACKED_ELASTIC`, en flexion simple avec
`NEd = 0`, adhérence parfaite et acier tendu seul. Une aire d'armature comprimée
non nulle est explicitement refusée. Aucun modèle de fluage (`φ`, `Eceff`), de
fissuration ou de flèche n'est produit par cette étape.

## Fissuration directe Poutre BEAM-SLS-02

| Nom | Symbole | Type / origine | Rôle et limite | Unité |
|---|---|---|---|---|
| `coverToLongitudinalBar` | `c` | valeur dérivée / DERIVED | enrobage jusqu'à la **surface** de la barre longitudinale : `c_nom + φ_st`. `φ/2` n'est pas ajouté ici ; il intervient seulement pour l'axe de barre. | mm |
| `barSpacing` | `s_bar` | disposition réelle / DERIVED_FROM_REINFORCEMENT_LAYOUT | espacement axe-à-axe du lit régulier : `[b - 2(c_nom + φ_st + φ/2)] / (n - 1)`. Il est distinct de l'espacement libre minimal EC2 §8.2. | mm |
| `clearBarSpacing` | — | valeur dérivée / DERIVED | espacement libre du lit : `s_bar - φ`. | mm |
| `effectiveTensionHeight` | `hc,eff` | valeur dérivée / DERIVED | `min(2,5(h-d), (h-x_sls)/3, h/2)`, avec `x_sls` réutilisé de BEAM-SLS-01. | mm |
| `effectiveTensionArea` | `Ac,eff` | valeur dérivée / DERIVED | aire efficace tendue de la section rectangulaire : `b × hc,eff`, jamais `b × h`. | mm² |
| `effectiveReinforcementRatio` | `ρp,eff` | valeur dérivée / DERIVED | `As_prov / Ac,eff`, utilisant l'armature réellement fournie, non `As_req` ni `As_target`. | sans dimension |
| `effectiveConcreteTensileStrength` | `fct,eff` | hypothèse MVP / DERIVED | `fctm` du référentiel béton. Cette équivalence ne couvre pas les fissures précoces. | MPa |
| `kt` | `kt` | paramètre national / PROFILE | `0,6` court terme ; `0,4` long terme. La combinaison quasi-permanente MVP dérive explicitement `LONG_TERM`, donc `0,4`. | sans dimension |
| `maximumCrackSpacing` | `sr,max` | valeur dérivée / DERIVED | si `s_bar ≤ 5(c + φ/2)` : `k3c + k1k2k4φ/ρp,eff`; sinon `1,3(h-x_sls)`. | mm |
| `strainDifference` | `εsm - εcm` | valeur dérivée / DERIVED | maximum de l'expression EC2 §7.3.4 et de `0,6σs/Es`, avec critère gouvernant conservé. | sans dimension |
| `crackWidth` | `wk` | valeur dérivée / DERIVED | largeur caractéristique : `sr,max × (εsm - εcm)`, sans arrondi intermédiaire. | mm |
| `crackWidthLimit` | `wmax` | paramètre national / PROFILE | limite de fissuration par classe d'exposition. Seule `XC1 → 0,4 mm` est validée et supportée par BEAM-SLS-02 ; les autres classes sont refusées explicitement. | mm |
| `BeamCrackVerificationStatus` | — | état dérivé / DERIVED | statut local `COMPLIANT`, `NOT_COMPLIANT` ou `NOT_CHECKED`. Il ne constitue pas le statut ELS global. | — |

Les coefficients de fissuration ont des noms non ambigus dans
`BeamCrackWidthRequirements` : `crackBondCoefficient` (`k1 = 0,8`, HA),
`crackStrainDistributionCoefficient` (`k2 = 0,5`, flexion),
`crackSpacingCoefficient3` (`k3 = 3,4`) et
`crackSpacingCoefficient4` (`k4 = 0,425`). Ils sont indépendants des autres
coefficients homonymes du cisaillement et de l'espacement.

## Vérification simplifiée de déformation Poutre BEAM-SLS-03

| Nom | Symbole | Type / origine | Rôle et limite | Unité |
|---|---|---|---|---|
| `actualSpanDepthRatio` | `l_eff / d` | valeur dérivée / DERIVED | rapport réel de portée efficace sur hauteur utile réelle du candidat. Ce n'est pas une flèche. | sans dimension |
| `reinforcementRatio` | `ρ` | valeur dérivée / DERIVED | `As_req / (b × d)`, avec `As_req` recalculé pour le candidat réel. `As_prov` ne doit jamais le remplacer. | sans dimension |
| `referenceReinforcementRatio` | `ρ0` | valeur dérivée / DERIVED | `sqrt(fck) × 10^-3`, avec `fck` en MPa. | sans dimension |
| `compressionReinforcementRatio` | `ρ'` | hypothèse MVP / FIXED_MVP | vaut explicitement `0` : seules les sections simplement armées sont supportées. | sans dimension |
| `structuralFactor` | `K` | paramètre national / PROFILE | facteur lié au système statique ; seul `SIMPLY_SUPPORTED → 1,0` est supporté. | sans dimension |
| `baseAllowableSpanDepthRatio` | `(l/d)_0` | valeur dérivée / DERIVED | rapport limite EC2 §7.4.2, obtenu par 7.16a si `ρ ≤ ρ0` ou 7.16b si `ρ > ρ0`. `K` est inclus. | sans dimension |
| `steelStressCorrectionFactor` | — | valeur dérivée / DERIVED | correction simplifiée : `(500 / fyk) × (As_prov / As_req)`. Le `500 MPa` est centralisé dans le profil. | sans dimension |
| `allowableSpanDepthRatio` | `l/d_adm` | valeur dérivée / DERIVED | `baseAllowableSpanDepthRatio × steelStressCorrectionFactor`; aucun facteur supplémentaire n'est inventé. | sans dimension |
| `utilization` | — | valeur dérivée / DERIVED | `actualSpanDepthRatio / allowableSpanDepthRatio`. | sans dimension |
| `BeamDeflectionMethod` | — | méthode / PROFILE | `SIMPLIFIED_SPAN_DEPTH`, méthode de dispense du calcul explicite de déformation. | — |
| `BeamDeflectionVerificationStatus` | — | état dérivé / DERIVED | `COMPLIANT`, `NOT_COMPLIANT`, `CALCULATION_METHOD_NOT_SUPPORTED` ou `NOT_CHECKED`. Ce n'est pas un statut ELS global. | — |

BEAM-SLS-03 ne retourne volontairement aucune flèche en millimètres. Ses
warnings indiquent que la méthode est simplifiée, que le fluage/retrait ne sont
pas modélisés explicitement et que le contrôle lié aux cloisons fragiles reste
à renseigner dans un futur périmètre.

## Agrégation des vérifications Poutre BEAM-RESULT-01

| Nom | Type / origine | Rôle |
|---|---|---|
| `ulsStatus` | DERIVED | agrégation de la flexion ELU et du cisaillement ELU. |
| `slsStatus` | DERIVED | agrégation des contraintes, de la fissuration et de la déformation. |
| `overallStatus` | DERIVED | `COMPLIANT` seulement si toutes les vérifications obligatoires applicables sont conformes. |
| `NOT_CHECKED` | état | absence de conclusion suffisante ; ne signifie jamais `COMPLIANT`. |
| `NOT_APPLICABLE` | état | contrôle explicitement non applicable, ignoré pour la décision sans être converti en contrôle fait. |

L'ordre de priorité est centralisé : `NOT_COMPLIANT`, puis `NOT_CHECKED` ou
`CALCULATION_METHOD_NOT_SUPPORTED`, puis `COMPLIANT`. BEAM-RESULT-01 lit
uniquement les résultats en amont : il ne calcule aucune formule Eurocode et
ne désigne aucune vérification gouvernante.

## Résumé et indicateur de conformité Poutre BEAM-RESULT-03 / UI-RESULT-01

| Nom | Type / origine | Rôle | Unité / remarque |
|---|---|---|---|
| `summary.utilization` | `number \| null`, DERIVED | taux brut de la vérification gouvernante, sélectionné par BEAM-RESULT-02 et transmis par BEAM-RESULT-03 | sans dimension ; l'interface le convertit uniquement pour l'afficher en pourcentage, sans l'arrondir dans le domaine métier. |
| `summary.status` | `BeamVerificationStatus`, DERIVED | statut global de BEAM-RESULT-01, source de vérité de la conformité affichée | — ; le frontend ne déduit jamais ce statut du taux. |
| `summary.governingVerificationType` | `string \| null`, DERIVED | identifiant de la vérification gouvernante déterminée par BEAM-RESULT-02 | — ; `null` lorsque aucune vérification gouvernante ne peut être établie. |
| `ResultComplianceIndicator` | composant Angular, DERIVED | présente ces trois valeurs sous forme de donut, nombre, texte et symbole d'état | aucun calcul structurel, aucune agrégation ou décision de conformité. |
| `visualProgress` | `number`, DERIVED (UI) | progression graphique du donut, bornée entre `0` et `1` | sans dimension ; ne modifie jamais `summary.utilization`, notamment lorsque le taux dépasse 1. |

L'indicateur conserve le ratio brut provenant du backend. Son pourcentage et
son arc SVG sont exclusivement des choix de présentation ; une couleur ne
constitue jamais le seul signal de conformité.

Les identifiants gouvernants `FLEXURE`, `SHEAR`, `STRESS`, `CRACK` et
`DEFLECTION` sont rendus respectivement par `Flexion`, `Cisaillement`,
`Contraintes ELS`, `Fissuration` et `Déformation`. Ces libellés ne sont ni une
nouvelle classification ni une sélection de vérification côté frontend.

## Cartes de résultats Poutre UI-RESULT-02

| Nom | Type / origine | Rôle | Unité / remarque |
|---|---|---|---|
| `summary.designBendingMoment` | `number \| null`, DERIVED | moment de calcul transmis par BEAM-RESULT-03 | kN·m ; affiché avec deux décimales françaises. |
| `summary.effectiveDepth` | `number \| null`, DERIVED | hauteur utile transmise par BEAM-RESULT-03 | mm ; affichée sans conversion en cm. |
| `summary.requiredLongitudinalReinforcementArea` | `number \| null`, DERIVED | aire d'armature longitudinale requise du candidat évalué | mm² ; affichée avec deux décimales françaises. |
| `summary.longitudinalReinforcement` | objet ou `null`, DERIVED | ferraillage longitudinal retenu ou fourni : `source`, `barCount`, `barDiameter`, `providedArea` | `source` vaut `PROPOSED` ou `PROVIDED`; `providedArea` est en mm². |
| `ResultSummaryCards` | composant Angular, DERIVED | organise ces quatre données en cartes de synthèse | lit exclusivement BEAM-RESULT-03, sans lire `BeamCalculationDetails`. |

Le format `4 HA12` et les séparateurs français sont des présentations UI de
`barCount` et `barDiameter`; ils ne sont jamais réinterprétés pour reconstruire
le ferraillage. Une donnée absente est affichée `—`, jamais comme une valeur
zéro.

## Message synthétique Poutre UI-RESULT-03

| Nom | Type / origine | Rôle | Remarque |
|---|---|---|---|
| `ResultSummaryMessage.status` | `BeamVerificationStatus`, DERIVED | sélectionne exclusivement le message principal | vient de BEAM-RESULT-01 via BEAM-RESULT-03 ; aucun statut n'est déduit du taux. |
| `ResultSummaryMessage.utilization` | `number \| null`, DERIVED | taux gouvernant affiché seulement dans la phrase secondaire | sans dimension ; conversion en pourcentage et arrondi exclusivement UI. |
| `ResultSummaryMessage.governingVerificationType` | identifiant ou `null`, DERIVED | vérification la plus sollicitée, affichée seulement si elle existe | vient de BEAM-RESULT-02 ; le frontend ne la sélectionne pas. |
| `ResultSummaryMessage.presentation.message` | texte UI, DERIVED | synthèse courte affichée à l'utilisateur | ne contient ni formule, ni recommandation de redimensionnement, ni promesse de certification. |

Pour `COMPLIANT`, le message reste limité aux vérifications réalisées « dans
le périmètre actuel »; pour `NOT_CHECKED` et
`CALCULATION_METHOD_NOT_SUPPORTED`, il ne conclut jamais à une conformité.

## Accordéons du détail Poutre UI-RESULT-04

| Section affichée | Source `BeamCalculationDetails` | Rôle UI |
|---|---|---|
| Hypothèses et paramètres | `assumptions` | présente la configuration et les données structurantes disponibles. |
| Calcul des sollicitations | `combinations`, `internalForces` | présente séparément les combinaisons déjà constituées et les efforts déjà calculés. |
| Flexion | `flexure` | présente les résultats de la chaîne de flexion. |
| Armatures | `reinforcement` | présente le ferraillage `PROPOSED` ou `PROVIDED` et les aires associées. |
| Cisaillement | `shear` | présente les résistances et étriers déjà déterminés. |
| ELS | `serviceability` | présente les sous-sections Contraintes, Fissuration et Déformation. |

`CalculationDetailsAccordion` ne transforme les nombres qu’en texte de
présentation (locale française, unités et statuts lisibles). Il ne recompose ni
combinaison, ni effort, ni résistance, ni statut. Avec la méthode
`SIMPLIFIED_SPAN_DEPTH`, il explique explicitement qu’aucune flèche physique en
millimètres n’est affichée; la clé éventuelle `deflectionMm` est exclue de la
présentation.

## Étapes de formule UI-RESULT-05

| Nom | Type / origine | Rôle | Remarque |
|---|---|---|---|
| `CalculationFormulaStep` | contrat de présentation / DERIVED | étape textuelle : `name`, `formula`, `substitution`, `result`, unité, référence, warning et statut facultatifs | le frontend l'affiche sans l'évaluer ni la reconstruire. |
| `calculationSteps` | liste optionnelle / DERIVED | étapes éventuellement regroupées par `actions`, `flexure`, `reinforcement`, `shear` ou `serviceability` | prévue dans le contrat frontend pour une future exposition structurée de BEAM-RESULT-04. |

Le `BeamCalculationDetails` backend actuel ne contient pas encore de propriété
`calculationSteps`, ni de triplet explicite `formula` / `substitution` /
`result`. Bien que certains DTO internes portent une formule, ils ne sont pas
exposés par BEAM-RESULT-04. UI-RESULT-05 masque donc toute sous-section de
formules en l'absence de ces données, plutôt que de dupliquer les expressions
normatives dans Angular.

## Orchestration complète Poutre BEAM-INTEGRATION-01

| Nom | Type / origine | Rôle | Remarque |
|---|---|---|---|
| `BeamCalculationOrchestrator` | service applicatif | enchaîne la validation du payload, les actions, efforts, vérifications ULS/SLS puis BEAM-RESULT-01 à 04 | ne porte aucune formule ni coefficient propre : ceux-ci restent dans les calculateurs et profils existants. |
| `POST /api/beam/calculations` | API | reçoit le `BeamCalculationPayload` déjà employé par le formulaire Poutre | répond avec `summary`, `verifications` et `details`; les erreurs de domaine sont renvoyées en `422`. |
| `summary` | DERIVED | projection BEAM-RESULT-03 pour l'indicateur, les cartes et le message | `utilization`, `status` et `governingVerificationType` restent la source de vérité backend. |
| `verifications` | DERIVED | agrégation BEAM-RESULT-01 de Flexion, Cisaillement, Contraintes, Fissuration et Déformation | aucune agrégation n'est faite par Angular. |
| `details` | DERIVED | détail BEAM-RESULT-04 structuré pour les accordéons | reprend les DTO calculés sans recomposer de résultats côté contrôleur ou frontend. |

Le formulaire convertit ses unités de saisie vers le payload interne en mm
avant l'appel HTTP. Pendant la requête, l'interface empêche un deuxième envoi;
une réponse reçue remplace le résultat affiché, et une erreur backend est
présentée explicitement sans produire de résultat local.

## Matériaux et durabilité du calcul Poutre

| Entrée | Origine | Valeurs actuellement proposées | Rôle |
|---|---|---|---|
| `concreteClass` | USER | `C20/25`, `C25/30`, `C30/37` | la classe est envoyée au backend ; `fck`, `fcm`, `fctm`, `Ecm` et `fcd` restent dérivés du référentiel et du profil. |
| `steelGrade` | USER | `B500B` | la nuance reste explicite dans le payload ; `fyk`, `Es` et `fyd` sont dérivés côté backend. |
| `exposureClass` | USER (sélecteur unique) | `XC1` | le formulaire le transforme seulement en la liste domaine `exposureClasses: ['XC1']`; `c_min,dur`, `c_min` et `c_nom` restent calculés par EC2-05. |

`BeamCalculationCapabilities` est la source backend des options exposées par
`GET /api/beam/material-catalog` et de leur validation lors du calcul. Les
classes béton du référentiel passent la chaîne Poutre actuelle ; `B500B` est la
seule nuance du référentiel MVP. Bien que le référentiel d'exposition contienne
de nombreuses classes, `XC1` est la seule exposition proposée : le profil
français actuel ne fournit une limite de fissuration BEAM-SLS-02 que pour elle.
Une exposition connue telle que `XC4` est donc refusée avec
`UNSUPPORTED_EXPOSURE_CLASS`, sans repli silencieux ni règle normative ajoutée.

Angular ne contient aucune table de propriétés mécaniques, d'enrobage ou de
fissuration. Une modification de matériau efface le résultat précédemment
affiché : le prochain résultat ne peut ainsi pas être confondu avec l'entrée
modifiée.

## Armatures secondaires de dalle SLAB-09

| Nom | Symbole | Type / origine | Rôle | Unité |
|---|---|---|---|---|
| `mainProvidedAreaPerMeter` | `As_main,provided` | DERIVED / SLAB-08 | aire réellement proposée pour la nappe principale ; c'est l'unique entrée d'armature du calcul secondaire. | mm²/m |
| `secondaryReinforcementRatio` | — | PROFILE | ratio minimal d'armature secondaire, égal à `0,20`. | sans dimension |
| `minimumRequiredAreaPerMeter` | `As_secondary,min` | DERIVED | `0,20 × As_main,provided`. Il ne dépend ni de `As_req` ni de `As_design`. | mm²/m |
| `maximumAllowedSpacing` | `s_secondary,max` | DERIVED / PROFILE | `min(3,5 × h, 450 mm)` avec `h` en mm. | mm |
| `secondaryBarDiameter` | `φ_secondary` | DERIVED / CONFIG | diamètre issu du catalogue commun de barres ; il reste distinct de celui de la nappe principale. | mm |
| `secondarySpacing` | `s_secondary` | DERIVED / CONFIG | espacement issu du catalogue commun SLAB-08 et retenu seulement s'il respecte `s_secondary,max`. | mm |
| `secondaryBarArea` | `Aφ_secondary` | DERIVED | aire d'une barre : `π × φ² / 4`. | mm² |
| `providedAreaPerMeter` | `As_secondary,provided` | DERIVED | aire réellement fournie par une proposition : `Aφ × 1000 / s`. | mm²/m |
| `secondaryOverProvision` | — | DERIVED | `As_secondary,provided − As_secondary,min`, utilisée uniquement pour classer les propositions recevables. | mm²/m |

Les paramètres `0,20`, `3,5` et `450 mm` appartiennent au profil normatif
dans `SlabReinforcementRequirements`; ils ne sont ni des constantes du
calculateur ni des propriétés intrinsèques de l'acier. Le résultat SLAB-09 est
un résultat local de proposition, sans conclusion de conformité globale.

La règle d'espacement appliquée est la règle générale de l'EN 1992-1-1:2004,
§9.3.1.1(3). Les zones localisées de moment maximal ou de charge concentrée,
où une limite plus stricte est prévue, ne sont pas modélisées : le MVP ne porte
pas de position de charge ni de zonage de dalle. Cette limite doit être levée
avant d'étendre le calcul à ces cas. La confirmation exhaustive de l'incidence
de l'amendement national français 2026 reste à effectuer à partir de son texte
normatif exploitable.

## Vérifications ELS Dalle SLAB-10

SLAB-10 produit deux résultats locaux (`crackVerification` et
`deflectionVerification`) dans `SlabServiceabilityResult`. Il ne produit ni
`slsStatus`, ni `overallStatus`, ni une conformité globale Dalle.

| Nom | Origine | Rôle | Unité |
|---|---|---|---|
| `M_sls` | DERIVED / SLAB-06 | moment de la combinaison `QUASI_PERMANENT`, consommé sans refaire l'analyse statique. | kN·m |
| `αe`, `x_sls`, `Icr`, `σs` | DERIVED | résultats de la section fissurée élastique commune, avec `As_main,provided`, `d` et les modules matériaux. | —, mm, mm⁴, MPa |
| `Ac,eff`, `ρp,eff`, `sr,max`, `εsm − εcm` | DERIVED | grandeurs du calcul direct de fissuration commun EC2 §7.3.4. | mm², —, mm, — |
| `wk`, `wk,max` | DERIVED / PROFILE | largeur calculée et limite issue de `BeamCrackWidthRequirements` du profil français. | mm |
| `actualSpanDepthRatio` | DERIVED | `L / d`, avec la portée SLAB-02 et la hauteur utile réelle SLAB-08. | sans dimension |
| `reinforcementRatio`, `referenceReinforcementRatio`, `structuralFactor` | DERIVED / PROFILE | paramètres du contrôle simplifié EC2 §7.4.2 ; `K` est lu du profil pour le système simplement appuyé. | sans dimension |
| `allowableSpanDepthRatio`, `utilization` | DERIVED | limite `l/d` et ratio `actual / allowable`; aucune flèche en mm n'est déduite. | sans dimension |

Le ferraillage de fissuration est exclusivement celui réellement proposé par
SLAB-08 : diamètre, espacement, `As,provided/m`, `d` et `c_nom`. XC1 est la
seule exposition actuellement dotée d'une limite `wk,max` validée dans le
profil; une exposition telle que XC2 retourne
`CALCULATION_METHOD_NOT_SUPPORTED`, sans valeur de fissure spéculative.

Les noyaux communs `CrackedElasticSectionCalculator`,
`DirectCrackWidthCalculator` et `SimplifiedSpanDepthCalculator` sont utilisés
par Poutre et Dalle. Les références restent EN 1992-1-1:2004 §§7.3.4 et 7.4.2;
la validation d'une éventuelle incidence de l'Annexe Nationale française 2026
reste en attente d'un texte normatif exploitable.

## Résultat final Dalle SLAB-11

`SlabCalculationResult` expose le contrat final `status`, `summary`,
`verifications` et `details`. Ces éléments sont des projections des résultats
SLAB-01 à SLAB-10 : aucune charge, sollicitation, résistance ou armature n'y
est recalculée.

| Nom | Origine | Rôle |
|---|---|---|
| `overallStatus` / `status` | DERIVED | agrégation des statuts locaux par la même priorité que Beam. |
| `ulsStatus` | DERIVED | agrégation de `FLEXURE`, `MAIN_REINFORCEMENT` et `SECONDARY_REINFORCEMENT`. |
| `slsStatus` | DERIVED | agrégation de `CRACK` et `DEFLECTION`. |
| `governingVerification` | DERIVED | vérification ayant l'utilisation existante finie la plus élevée. |
| `governingUtilization` | DERIVED | utilisation brute de cette vérification ; aucune valeur n'est créée pour un contrôle qui n'en porte pas. |

La priorité est commune : `NOT_COMPLIANT`, puis `NOT_CHECKED` ou
`CALCULATION_METHOD_NOT_SUPPORTED` (agrégés en `NOT_CHECKED`), puis
`COMPLIANT`. `NOT_APPLICABLE` est neutre. Les contrôles sans utilisation
fiable, notamment les propositions de ferraillage, ne participent pas au choix
gouvernant.
