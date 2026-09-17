# BEAM-SLS-01 — Limitation des contraintes ELS

## Références et profil

BEAM-SLS-01 applique EN 1992-1-1:2004 / NF EN 1992-1-1:2005, §7.2, à travers
le profil `NF_EN_1992_1_1_2005_FR`. Les valeurs recommandées reprises par les
documents JRC sont portées par le profil, jamais par le calculateur :

| Contrôle | Combinaison | Limite retenue |
|---|---|---|
| compression béton | caractéristique | `σc,char ≤ 0,60 fck` |
| traction acier | caractéristique | `σs,char ≤ 0,80 fyk` |
| compression béton | quasi-permanente | `σc,qp ≤ 0,45 fck` |

La limite acier utilise la résistance caractéristique `fyk`, et non la
résistance de calcul `fyd`. La combinaison fréquente est conservée dans la
chaîne des actions, mais BEAM-SLS-01 retourne explicitement
`NOT_APPLICABLE` : aucune limite de contrainte supplémentaire n'est inventée.

## Modèle mécanique V1

La vérification est menée en flexion simple avec `NEd = 0`, sur une section
fissurée élastique instantanée (`CRACKED_ELASTIC`) : béton tendu négligé,
adhérence parfaite et acier tendu transformé. Avec une seule nappe tendue,
elle calcule :

```text
αe = Es / Ecm
(b / 2)x_sls² + αe As x_sls - αe As d = 0
I_cr = b x_sls³ / 3 + αe As(d - x_sls)²
σc = M x_sls / I_cr
σs = αe M(d - x_sls) / I_cr
```

Les moments sont convertis de kN·m en N·mm avec l'infrastructure d'unités
existante. L'armature comprimée n'est pas encore modélisée : toute aire non
nulle est refusée explicitement.

## Limites du résultat

`αe = Es / Ecm` est un rapport modulaire **instantané**. Le ticket ne calcule
ni coefficient de fluage `φ(t,t0)`, ni module effectif `Eceff`, ni une
redistribution complète à long terme. Le contrôle quasi-permanent `0,45 fck`
est donc une limitation et un indicateur local du besoin éventuel d'une analyse
de fluage non linéaire ; il ne prétend pas fournir une contrainte long terme
complète.

La vérification de fissuration (`wk`, `sr,max`), la flèche, un statut ELS
global et une conformité globale de poutre restent hors périmètre.

Le projet vise toujours NF EN 1992-1-1/NA:2016 et son amendement A1 d'avril
2026 tels que documentés dans le profil. La nouvelle Annexe Nationale publiée
en août 2026 est hors périmètre. Faute d'une source normative exploitable
établissant une divergence nationale sur §7.2, les valeurs recommandées sont
conservées avec cette réserve explicite.
