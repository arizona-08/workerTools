# QA-01 — Cas de référence Poutre

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
