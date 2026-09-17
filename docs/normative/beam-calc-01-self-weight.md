# Poids propre Poutre — BEAM-CALC-01

Le V1 utilise `γ_RC = 25 kN/m³` pour le béton armé de masse volumique
normale. Cette référence relève du cadre des actions permanentes d'EN 1991-1-1
(annexe A, valeurs indicatives de poids volumiques) et est centralisée dans
`ReinforcedConcreteUnitWeightRepository`.

Cette valeur est un poids volumique en `kN/m³`, non une masse volumique en
`kg/m³`. Elle ne constitue pas une propriété intrinsèque d'une classe de béton
EC2 : elle n'est donc pas stockée dans `ConcreteClass` ou `ConcreteProperties`.

Le périmètre ne couvre que le béton armé de masse volumique normale. Béton
léger, béton lourd et ajustements de composition sont hors V1 et devront
disposer de références explicites avant d'être calculés.
