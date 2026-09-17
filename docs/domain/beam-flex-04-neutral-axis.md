# Axe neutre ELU Poutre — BEAM-FLEX-04

## Bloc rectangulaire simplifié

Pour le bloc rectangulaire simplifié du béton, conformément à
EN 1992-1-1:2004 / NF EN 1992-1-1:2005 §3.1.7, le composant normatif
`ConcreteRectangularStressBlockParametersCalculator` fournit :

| Domaine | `λ` | `η` |
|---|---|---|
| `fck ≤ 50 MPa` | `0,8` | `1,0` |
| `50 < fck ≤ 90 MPa` | `0,8 - (fck - 50) / 400` | `1,0 - (fck - 50) / 200` |

Les résistances supérieures à `90 MPa` sont refusées : aucune extrapolation
du modèle V1 n'est faite. Le bloc a une profondeur `λx` et une contrainte
uniforme `ηfcd`.

## Résolution de l'axe neutre

À partir de `μEd` et de la profondeur utile `d`, l'équilibre du moment est :

`μEd = η × λ × ξ × (1 - λξ / 2)`

avec `ξ = x / d`. La résolution retenue est la petite racine :

`ξ = [1 - sqrt(1 - 2 × μEd / η)] / λ`

`x = ξ × d`

Cette racine correspond à la branche physique usuelle faiblement sollicitée
d'une section simplement armée. La seconde racine ne représente pas la branche
utilisée par le V1.

Le terme `1 - 2μEd / η` doit être supérieur ou égal à zéro. Cette condition
est seulement une condition mathématique d'existence de la racine : elle ne
constitue ni une limite de ductilité, ni `ξlim`, `xlim` ou `μlim`.

## Limites de l'étape

BEAM-FLEX-04 fournit `λ`, `η`, `ξ` et `x` sans arrondi intermédiaire. Le bras
de levier dérivé de ce bloc est désormais calculé par BEAM-FLEX-05 et documenté
dans `docs/domain/beam-flex-05-lever-arm.md`. Cette étape ne calcule toujours
ni armature requise ou minimale, ni résistance `MRd`, ni conformité de section.
