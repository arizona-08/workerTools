# Combinaisons ELS Poutre — BEAM-CALC-04

## Périmètre

Cette note documente les combinaisons d'états limites de service du MVP pour
une poutre soumise à des charges linéaires uniformément réparties. Le moteur
reçoit les actions caractéristiques déjà constituées : `Gk_total` et une seule
action variable principale `Qk`, de catégorie A.

Les expressions sont conservées sous forme typée dans
`ServiceabilityCombinationExpression`, afin d'éviter toute référence libre
dispersée dans les calculateurs.

## Expressions retenues

| Combinaison | Référence | Expression MVP |
|---|---|---|
| Caractéristique | EN 1990 6.14 | `wSlsCharacteristic = Gk_total + Qk` |
| Fréquente | EN 1990 6.15 | `wSlsFrequent = Gk_total + ψ1 × Qk` |
| Quasi-permanente | EN 1990 6.16 | `wSlsQuasiPermanent = Gk_total + ψ2 × Qk` |

Dans le cas d'une seule action variable principale, `ψ0` n'est pas appliqué à
`Qk` dans la combinaison caractéristique. Il demeure une donnée du profil pour
les futures actions variables accompagnatrices, hors périmètre du MVP.

`ψ1` et `ψ2` sont demandés au `DesignCodeProfile` par
`combinationFactorsFor(category)`. Pour la catégorie A actuellement couverte
par le profil français, celui-ci fournit `ψ0 = 0,70`, `ψ1 = 0,50` et
`ψ2 = 0,30`; le calculateur ne recopie pas ces valeurs.

## Coefficients partiels et limites

Les actions restent à leur valeur caractéristique en ELS : aucune majoration
ELU `γG = 1,35` ou `γQ = 1,50` n'est appliquée. Le facteur 1,0 visible dans la
combinaison caractéristique exprime simplement EN 1990 6.14 pour l'action
variable principale ; ce n'est pas un coefficient partiel de sécurité ajouté
au profil.

Le périmètre exclut les variables accompagnatrices, plusieurs actions
variables indépendantes, les actions climatiques, ainsi que les vérifications
de fissuration, flèche et contraintes. Les charges demeurent en `kN/m` : aucun
moment `MEd` ni effort tranchant `VEd` n'est calculé par cette étape.
