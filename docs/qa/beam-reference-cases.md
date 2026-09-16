# BEAM-SUB-07 — Cas de référence Poutre

Les valeurs attendues sont établies indépendamment du code de production avec
`γRC=25 kN/m³`, `γG=1,35`, `γQ=1,50`, `ψ1=0,5`, `ψ2=0,3`,
`M=wL²/8` et `V=wL/2` (w en kN/m, L en m). Les trois cas sont C30/37,
B500B, XC1, poutre rectangulaire simplement appuyée sous charge uniforme.

| Cas | L (m) | b×h (mm) | Gk add. | Qk | poids propre | Gk total | wEd | MEd | VEd |
|---|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| A | 6,5 | 300×600 | 5 | 3,5 | 4,5 | 9,5 | 18,075 | 95,45859375 | 58,74375 |
| B | 5 | 250×500 | 3 | 4 | 3,125 | 6,125 | 14,26875 | 44,58984375 | 35,671875 |
| C | 4 | 350×550 | 9 | 1,5 | 4,8125 | 13,8125 | 20,896875 | 41,79375 | 41,79375 |

Les comparaisons utilisent une tolérance absolue de `1e-9` (et `1e-10` pour
les charges). XC1 est la classe actuellement supportée de bout en bout par la
vérification de fissuration du V1.

## Console — cas D

Le cas console utilise C30/37, B500B, XC1, une section de `300 × 600 mm`,
`L = 4,00 m`, le poids propre activé, `Gk_add = 5,0 kN/m` et `Qk = 3,5 kN/m`.
Les charges sont les mêmes que le cas A : `Gk,self = 4,50 kN/m`,
`Gk,total = 9,50 kN/m`, `wEd = 18,075 kN/m`, `wELS,car = 13,00 kN/m`,
`wELS,fréq = 11,25 kN/m` et `wELS,qp = 10,55 kN/m`.

La convention du moteur est négative pour le moment à l’encastrement :

```text
MEd = −wEd × L² / 2 = −144,60 kN·m
VEd =  wEd × L     =   72,30 kN
```

Les sollicitations ELS associées sont `Mcar = −104,00 kN·m`,
`Mfréq = −90,00 kN·m`, `Mqp = −84,40 kN·m`, `Vcar = 52,00 kN`,
`Vfréq = 45,00 kN` et `Vqp = 42,20 kN`.

La référence de flexion sélectionne `4 HA14` en partie supérieure :
`d = 565 mm`, `μ = 0,075495340277234`, `ξ = 0,098228728595107`,
`z = 542,80030733751 mm`, `As,req = 612,71151748484 mm²`,
`As,min = 255,606 mm²` et `As,prov = 615,7521601036 mm²`.

La contrainte ELS acier caractéristique est vérifiée (`319,03308960606 MPa`).
Pour le cisaillement, la section critique est à `x = d = 565 mm` de
l’encastrement. La valeur de contrôle est `VEd(d) = 18,075 × (4,000 - 0,565) =
62,087625 kN`, tandis que `VEd,enc = 72,30 kN` reste tracé et sert au contrôle
`VRd,max`. Avec l'armature réelle TOP (`As,prov = 615,7521601036 mm²`),
`VRd,c = 71,926296279318 kN` : la chaîne cisaillement est conforme et les
étriers minimums/proposés restent disponibles selon le moteur commun.

La fissuration réutilise le lit supérieur réel sans exiger une proposition
d'étriers : `c = 20 + 8 = 28 mm`, `s_bar = 76,666666666667 mm`,
`ρp,eff = 0,023457225146804`, `sr,max = 196,66127622108 mm`,
`εsm - εcm = 0,0010121280261388` et `wk = 0,19904638931959 mm`. Avec
`wk,max = 0,4 mm`, le taux vaut `0,49761597329897` : la fissuration est
conforme. La déformation simplifiée emploie maintenant le facteur de console
`K = 0,4` du profil français, avec `L = 4000 mm` et `d = 565 mm` :
`L/d = 7,079646017699115`, `(L/d)_adm = 12,031625285980292` et le taux vaut
`0,588419756219352`. Elle est donc conforme et le statut global attendu est
`COMPLIANT` pour ce cas de référence.
