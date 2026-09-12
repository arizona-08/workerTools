# Armature longitudinale minimale Poutre — BEAM-FLEX-07

## Règle de l'Eurocode 2

Pour les poutres, EN 1992-1-1:2004 / NF EN 1992-1-1:2005 §9.2.1.1(1) prescrit :

`As_min = max(0.26 × fctm / fyk × bt × d, 0.0013 × bt × d)`

`fctm` est la résistance moyenne en traction du béton et `fyk` est la limite
caractéristique d'élasticité de l'acier; tous deux sont en `MPa`, donc leur
rapport est sans dimension. `bt` et `d` sont en `mm`, d'où une aire en `mm²`.
La règle utilise expressément `fyk`, et non la résistance de calcul `fyd`.

Les valeurs `0,26` et `0,0013` sont des paramètres normatifs portés par
`BeamLongitudinalReinforcementRequirements` dans le profil français. Elles
restent donc surchargeables par un futur profil national. Le profil
`NF_EN_1992_1_1_2005_FR`, fondé sur NF EN 1992-1-1/NA:2016-03-24 et son
amendement A1:2026-04-14, retient les valeurs recommandées de §9.2.1.1(1).

## Hypothèse géométrique MVP

Dans le seul cas actuellement couvert — section `RECTANGULAR`, moment positif
en travée — la largeur moyenne de la zone tendue est assimilée à la largeur de
la section : `bt = b`. Cette égalité ne doit pas être réutilisée telle quelle
pour les futures sections en T ou en L.

## Portée et limites

Le résultat conserve les deux termes et l'identifiant du terme gouvernant. Il
constitue une exigence minimale, distincte de l'aire théorique d'équilibre
`As_req` de BEAM-FLEX-06. Cette étape ne calcule pas `max(As_req, As_min)`, ne
choisit pas de barres, ne compare aucune armature fournie et ne calcule ni
`MRd` ni conformité.

La validité mécanique de l'hypothèse `σs = fyd` utilisée pour calculer
`As_req` est désormais contrôlée séparément par BEAM-FLEX-08. Ce contrôle ne
combine toujours pas `As_req` et `As_min`.
