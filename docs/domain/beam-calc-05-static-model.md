# Modèle statique Poutre MVP — BEAM-CALC-05

## Hypothèses applicables

Le calculateur d'analyse statique est limité à une poutre à une travée,
simplement appuyée, de section constante et soumise à une charge linéaire
uniformément répartie sur toute sa portée efficace `l_eff`. La portée fournie
par le domaine est en millimètres, puis convertie explicitement en mètres avant
l'utilisation avec une charge en `kN/m`.

Pour ce modèle, le moment fléchissant maximal est :

`Mmax = w × l_eff² / 8`

Le coefficient `1/8` est représenté par
`SimplySupportedBeamBendingMomentCalculator::SIMPLY_SUPPORTED_UNIFORMLY_DISTRIBUTED_MAX_MOMENT_COEFFICIENT`.
Il provient de l'analyse mécanique de cette poutre et n'est pas un paramètre de
l'Eurocode. Le maximum est situé à `x = l_eff / 2`.

## Combinaisons analysées

La formule est appliquée indépendamment aux charges déjà obtenues par le moteur
de combinaisons :

- `wEd` donne `MEd` ;
- `wSlsCharacteristic` donne `MCharacteristic` ;
- `wSlsFrequent` donne `MFrequent` ;
- `wSlsQuasiPermanent` donne `MQuasiPermanent`.

Les résultats conservent la charge, la formule, la référence de combinaison et
l'unité `kN·m`, sans arrondi de calcul.

## Effort tranchant

Pour le même modèle statique, l'effort tranchant maximal en valeur absolue est
calculé par `Vmax = w × l_eff / 2`. Le coefficient `1/2` est centralisé dans
`SimplySupportedBeamShearForceCalculator::SIMPLY_SUPPORTED_UNIFORMLY_DISTRIBUTED_MAX_SHEAR_COEFFICIENT`;
il relève de l'analyse mécanique, pas de l'Eurocode.

Sous charge gravitaire uniforme, l'effort maximal est positif à l'appui gauche
et négatif à l'appui droit, avec la même valeur absolue. Le résultat métier
retourne cette valeur absolue maximale ainsi que les deux valeurs signées ; il
ne produit pas de diagramme détaillé. La formule est appliquée aux quatre
charges combinées pour former `VEd`, `VCharacteristic`, `VFrequent` et
`VQuasiPermanent`.

## Convention de signe et limites

Dans le périmètre gravitaire actuel, le moment en travée est positif et `MEd`
est retourné comme une valeur positive de dimensionnement. Il ne s'agit pas
d'une convention générale pour les poutres continues ou les consoles.

Les charges ponctuelles, moments imposés, porte-à-faux, poutres continues,
extrémités encastrées, sections variables et diagrammes complets sont exclus.
Le calculateur refuse explicitement un système d'appui ou un modèle de charge
autre que `SIMPLY_SUPPORTED` et `UNIFORMLY_DISTRIBUTED`. Aucun effort tranchant
de résistance, calcul d'armatures, hauteur utile, résistance `MRd` ou
vérification de section n'est réalisé. En particulier, aucune résistance
`VRd,c`, `VRd,s` ou `VRd,max` et aucun dimensionnement d'étrier ne sont traités.
