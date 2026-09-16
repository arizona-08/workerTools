# QA-02 — Cas de référence Dalle

Les trois cas couvrent le V1 : dalle pleine en béton armé, unidirectionnelle,
une travée, simplement appuyée sur deux côtés opposés, sous charge verticale
uniforme. La bande de calcul vaut 1 m. Tous utilisent C30/37, B500B et XC1,
seule classe d'exposition actuellement prise en charge de bout en bout pour la
fissuration.

Les résultats attendus sont des fixtures établies par calcul analytique
indépendant : `gk,self = 25 × h(m)`, `Gk = gk,self + finitions + cloisons +
autres`, `qEd = 1,35 Gk + 1,50 Qk`, `qchar = Gk + Qk`, `qfreq = Gk + 0,5 Qk`,
`qqp = Gk + 0,3 Qk`, `w = q × 1 m`, `M = wL²/8`, `V = wL/2`. Les valeurs
matériau figées sont `fck=30 MPa`, `fctm=2,9 MPa`, `Ecm=33 000 MPa`,
`fcd=20 MPa`, `fyk=500 MPa`, `fyd=500/1,15 MPa` et `Es=200 000 MPa`.

| Cas | L | h | finitions / cloisons / autres / Qk (kN/m²) | gk,self | Gk | qEd | MEd | VEd |
|---|---:|---:|---:|---:|---:|---:|---:|---:|
| A nominal | 5 m | 200 mm | 1,5 / 1 / 0,5 / 2 | 5 | 8 | 13,8 | 43,125 | 34,5 |
| B portée/épaisseur | 6 m | 280 mm | 1 / 0,5 / 0,5 / 3 | 7 | 9 | 16,65 | 74,925 | 49,95 |
| C permanentes dominantes | 4 m | 250 mm | 3 / 1,5 / 1 / 1 | 6,25 | 11,75 | 17,3625 | 34,725 | 34,725 |

`q` est en kN/m², `w` en kN/m (numériquement identique sur la bande de 1 m),
`L` en m, `M` en kN·m et `V` en kN. Les tests contrôlent aussi le recalcul
après le diamètre choisi : A `HA14/250`, B `HA12/150`, C `HA12/300`; les
armatures secondaires sont respectivement `HA8/300`, `HA8/300`, `HA8/300`.

Les `cnom` finaux sont respectivement 24, 22 et 22 mm : le critère gouvernant
est l'adhérence (`φ + Δcdev`, avec `Δcdev=10 mm`) pour XC1. Les ELS utilisent
la combinaison quasi-permanente pour la fissuration et la méthode simplifiée
`L/d` pour la flèche. Les trois cas sont conformes; le contrôle gouvernant est
la flèche pour A et la fissuration pour B et C.

Les comparaisons flottantes utilisent une tolérance absolue `1e-9` (`1e-10`
pour les charges). Les fixtures ne sont pas générées par les services de
production; aucune vérification volontairement non conforme, limite ou de
matrice d'unités n'est introduite ici.
