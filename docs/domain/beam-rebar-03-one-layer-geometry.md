# Vérification géométrique d'un lit Poutre — BEAM-REBAR-03

## Largeur entre étriers

Pour la section rectangulaire V1, la largeur libre entre les faces intérieures
des branches verticales d'étrier est :

`b_available = b - 2 × (c_nom + φ_st)`

`b` vient de la géométrie, `c_nom` du résultat EC2-05 et `φ_st` des hypothèses
de flexion existantes. Pour le cas de référence, `b = 300 mm`, `c_nom = 40 mm`
et `φ_st = 8 mm`, donc `b_available = 204 mm`.

## Espacement libre et largeur du lit

Suivant EN 1992-1-1 §8.2(2), le filtre utilise l'espacement **libre** :

`a_min = max(k1 × φ, d_g + k2, 20 mm)`

Le profil français retient les valeurs recommandées `k1 = 1,0` et `k2 = 5 mm`.
`d_g = 20 mm` est une hypothèse `CONFIG` du V1, portée par
`BeamReinforcementDetailingAssumptions`; elle n'est pas une propriété de la
classe de béton ni une valeur universelle de l'Eurocode.

Pour `n` barres identiques dans un seul lit :

`b_required = n × φ + (n - 1) × a_min`

Un candidat est admissible si `b_required ≤ b_available`. Le résultat conserve
la marge `b_available - b_required`, le critère gouvernant de l'espacement et,
en cas d'échec, `INSUFFICIENT_HORIZONTAL_SPACE`. En cas d'égalité entre les
termes de l'espacement maximal, le critère `TIE` est explicite.

## Limites

Ce filtre ne place pas de coordonnées de barres et n'impose pas de répartition
du surplus de largeur. Il ne traite qu'un lit homogène et ne modélise pas le
rayon de cintrage ou les angles des étriers, les interactions locales avec leur
courbure, plusieurs lits, diamètres mixtes, faisceaux, ni sections T/L.

Il ne recalcule ni `d`, `As_req`, `As_target`, `MRd` ou une conformité globale.
Les candidats acceptés conservent l'ordre de BEAM-REBAR-02; aucun ne devient
une solution finale à cette étape.
