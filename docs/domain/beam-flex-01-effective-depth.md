# Géométrie de flexion Poutre — BEAM-FLEX-01

## Définition et convention

La profondeur utile `d` est la distance entre la fibre comprimée extrême du
béton et le centre de gravité des armatures longitudinales tendues. Dans le
cas MVP de moment positif en travée, la face supérieure est comprimée et la
face inférieure est tendue : `d` est mesuré depuis la face supérieure.

Pour un seul lit de barres longitudinales de diamètre identique, placé à
l'intérieur d'un étrier, le moteur applique :

`a_s = c_nom + φ_st + φ_long / 2`

`d = h - a_s = h - c_nom - φ_st - φ_long / 2`

Toutes ces longueurs restent en millimètres. L'étrier est inclus car
l'enrobage nominal atteint l'armature extérieure, qui est l'étrier dans cette
configuration.

## Hypothèses configurables MVP

`BeamFlexuralDetailingAssumptions::mvp()` centralise les hypothèses de
detailing suivantes :

- `φ_st = 8 mm`, diamètre d'étrier préliminaire ;
- `φ_long,design = 16 mm`, diamètre longitudinal supposé au premier passage de
  dimensionnement.

Ces valeurs sont de type `CONFIG`, ne sont pas des paramètres Eurocode et ne
sont pas inscrites dans le calculateur. Elles sont injectables et devront être
remplacées ou recalculées lorsque le ferraillage transversal et le choix réel
des barres seront disponibles.

En mode DESIGN, le premier passage utilise le diamètre longitudinal supposé et
sa source est `CONFIG`. Après BEAM-REBAR-03, BEAM-REBAR-04 exécute une unique
passe de recalcul pour chaque candidat géométriquement admissible : son
diamètre devient la source `CANDIDATE`. En mode VERIFICATION, le diamètre est
celui du seul lit d'armatures saisi et validé par BEAM-07, avec la source
`USER`. Le recalcul DESIGN ne modifie jamais le candidat et ne relance pas une
génération : il ne constitue donc pas une itération ouverte.

## Relation avec EC2-05 et limite actuelle

Le calculateur de profondeur utile reçoit un `CoverCalculationResult` et
réutilise exclusivement son `cNom`; il ne recalcule aucune règle d'enrobage.
Dans le test d'intégration, EC2-05 est appelé avec le diamètre de l'étrier,
armature extérieure au regard du couvert de béton.

EC2-05 accepte actuellement un seul `reinforcementDiameter` et son calcul MVP
de `c_min,b` l'assimile au diamètre d'une barre passive individuelle. Cela ne
modélise pas encore explicitement le choix du diamètre gouvernant lorsque
étrier et barres longitudinales ont des exigences d'adhérence différentes. La
profondeur utile reste correcte à partir du `c_nom` fourni, mais une évolution
ultérieure devra rendre cette sélection de diamètre explicite avant de
prétendre couvrir tous les cas d'enrobage multicouches.

## Limites

Le calcul refuse une profondeur utile nulle ou négative. Plusieurs lits,
diamètres mixtes, espacement, logement géométrique des barres, collisions et
proposition d'armatures restent hors périmètre. Cette étape ne calcule ni
`As_req`, ni `As_min`, ni `x`, ni `z`, ni `MRd`, et ne conclut à aucune
conformité en flexion.
