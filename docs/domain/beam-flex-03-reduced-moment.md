# Moment réduit ELU Poutre — BEAM-FLEX-03

## Définition

Pour la section rectangulaire et le moment positif du MVP, WorkerTools calcule
le paramètre interne adimensionnel :

`μEd = MEd_Nmm / (b × d² × fcd)`

où `MEd_Nmm = MEd_kNm × 10⁶`. La conversion est centralisée dans
`MomentConverter`, car `b` et `d` sont en `mm`, tandis que `fcd` en `MPa` est
numériquement exprimé en `N/mm²`. Le dénominateur est donc en `N·mm`, ce qui
rend `μEd` sans dimension.

Le calculateur réutilise exclusivement le moment ELU produit par
BEAM-CALC-05, la largeur `b` de `BeamGeometry`, la profondeur utile `d` de
BEAM-FLEX-01 et `fcd` de BEAM-FLEX-02. La hauteur totale `h` ne remplace pas
la profondeur utile.

## Portée normative et limites

`μEd` est une grandeur interne utile pour les étapes d'équilibre de section.
Cette note ne lui attribue pas de numéro d'équation EC2. Le bloc de contraintes
rectangulaire d'EN 1992-1-1:2004 / NF EN 1992-1-1:2005, §3.1.7 est désormais
traité par BEAM-FLEX-04; ses paramètres et la résolution de l'axe neutre sont
documentés dans `docs/domain/beam-flex-04-neutral-axis.md`.

BEAM-FLEX-03 lui-même n'applique ni `λ`, ni `η`, ni limite de `μEd`; il ne
calcule ni `x`, ni `z`, ni armature, ni résistance `MRd`, et ne conclut à
aucune conformité. Le modèle refuse les moments négatifs plutôt que de les
transformer silencieusement en moments positifs.
