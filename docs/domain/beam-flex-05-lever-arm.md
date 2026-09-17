# Bras de levier interne Poutre — BEAM-FLEX-05

## Définition

BEAM-FLEX-05 dérive le bras de levier interne `z` à partir de la profondeur
utile `d` produite par BEAM-FLEX-01 et de l'axe neutre `x` produit par
BEAM-FLEX-04. Pour le bloc rectangulaire simplifié du béton d'EN 1992-1-1:2004
/ NF EN 1992-1-1:2005 §3.1.7, la profondeur du bloc comprimé vaut `λx` et sa
résultante est située à `λx / 2` depuis la fibre comprimée.

La formule calculée est donc :

`z = d - λ × x / 2`

Avec `ξ = x / d`, elle est équivalente à :

`z = d × (1 - λ × ξ / 2)`

Le résultat conserve `d`, `x`, `ξ`, `λ`, `λx`, `λx / 2` et `z`, sans arrondi
intermédiaire. Cette traçabilité permet de présenter l'une ou l'autre forme de
l'équation sans recalculer les données en amont.

## Domaine et limites

Le calculateur exige `d > 0`, `x ≥ 0`, `x ≤ d`, `λ > 0` et un résultat `z > 0`.
Il refuse également des résultats de BEAM-FLEX-01 et BEAM-FLEX-04 qui ne
portent pas la même profondeur utile. Le cas `x = 0` est admis et donne `z = d`.

Aucun plafonnement pratique de type `z ≤ 0,95d` n'est appliqué dans cette
étape : `z` est strictement celui résultant de la géométrie du bloc comprimé.
Le bras de levier produit ici est utilisé par BEAM-FLEX-06 pour déterminer
l'aire théorique `As_req`, sans que BEAM-FLEX-05 ne fasse lui-même de calcul
d'armature. Aucune résistance `MRd`, limite de ductilité ou statut de
conformité n'est déterminé à cette étape.
