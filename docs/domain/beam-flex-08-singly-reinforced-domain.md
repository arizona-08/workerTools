# Domaine de validité en flexion Poutre — BEAM-FLEX-08

## Compatibilité des déformations

BEAM-FLEX-06 utilise `As_req = MEd / (fyd × z)`. Cette relation suppose que
l'acier tendu peut atteindre `σs = fyd`. Sous l'hypothèse de sections planes,
BEAM-FLEX-08 vérifie cette hypothèse à partir du diagramme de déformations :

`εs = εcu3 × (d - x) / x = εcu3 × (1 - ξ) / ξ`

La déformation de calcul d'atteinte de `fyd` est :

`εyd = fyd / Es`

L'hypothèse est valide si `εs ≥ εyd`, ce qui est équivalent à :

`ξ ≤ ξ_yield = εcu3 / (εcu3 + εyd)`

`Es` provient du référentiel d'acier (B500B : `200000 MPa`) ; `fyd` est le
résultat BEAM-FLEX-02. `εcu3` est fourni par
`ConcreteUltimateStrainParametersCalculator`, selon EN 1992-1-1:2004 /
NF EN 1992-1-1:2005 §3.1.7 : `3,5 ‰` (`0,0035`) jusqu'à `fck = 50 MPa`, puis
`[2,6 + 35 × ((90 - fck) / 100)^4] ‰` pour `50 < fck ≤ 90 MPa`. Au-delà de
`90 MPa`, ce modèle MVP est refusé sans extrapolation.

La présentation JRC du modèle bilinéaire reprend cette expression de `εcu3`
pour EN 1992-1-1 §3.1.7 ; elle est utilisée ici comme source publique de
traçabilité complémentaire au texte normatif applicable.

## Domaine et limites

La condition mathématique du radicand de BEAM-FLEX-04 permet seulement de
résoudre le bloc comprimé. La condition mécanique de cette étape vérifie
séparément la possibilité d'utiliser `σs = fyd` dans BEAM-FLEX-06.

Aucune limite arbitraire `ξ ≤ 0,45`, règle de redistribution ou limite de
ductilité supplémentaire n'est appliquée. Si `εs < εyd`, le résultat retourne
explicitement `singlyReinforcedModelValid = false`; il ne recalcule pas une
contrainte acier élastique, ne crée pas d'armatures comprimées et ne produit
ni `MRd` ni conformité globale.

Pour `x = 0`, la déformation acier n'est pas calculée afin d'éviter une
division par zéro. Le résultat conserve alors `tensionSteelStrain = null` et
`tensionSteelReachesDesignYield = null`, ce qui signifie « non applicable »
pour le cas de charge nulle, tandis que le modèle est explicitement valide.

La comparaison de déformations inclut uniquement une tolérance numérique
nommée de `1e-12`, afin que l'égalité à `ξ_yield` soit considérée valide.
