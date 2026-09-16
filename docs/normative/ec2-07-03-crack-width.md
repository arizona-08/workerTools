# BEAM-SLS-02 — Fissuration directe

## Référence et domaine

BEAM-SLS-02 applique la méthode directe de EN 1992-1-1:2004 / NF EN
1992-1-1:2005 §7.3.4, sur une poutre rectangulaire non précontrainte, en
flexion simple, avec section fissurée élastique. La combinaison utilisée est
explicitement la combinaison ELS **quasi-permanente** ; `σs,qp`, `αe` et
`x_sls` sont réutilisés depuis BEAM-SLS-01.

```text
wk = sr,max × (εsm - εcm)
hc,eff = min(2,5(h-d), (h-x_sls)/3, h/2)
Ac,eff = b × hc,eff
ρp,eff = As_prov / Ac,eff
```

Dans le V1, `fct,eff = fctm` du matériau à 28 jours. Cette hypothèse ne
modélise pas la fissuration au jeune âge.

## Géométrie réelle du lit

`c_nom` désigne l'enrobage jusqu'à la surface de l'étrier. L'enrobage exigé
par la formule de fissuration est donc celui jusqu'à la surface de la barre
longitudinale : `c = c_nom + φ_st`. Le centre de cette barre est à
`c + φ/2`; aucun `φ/2` n'est ajouté à `c`.

Le diamètre réel de l'étrier recommandé par BEAM-SHEAR-04 est obligatoire :
le calcul refuse l'absence de proposition recommandée. Pour un lit unique de
`n` barres régulières, `s_bar = [b - 2(c_nom + φ_st + φ/2)]/(n-1)`.

## Calcul direct

Si `s_bar ≤ 5(c + φ/2)`, la branche « barres rapprochées » est utilisée :

```text
sr,max = k3 c + k1 k2 k4 φ / ρp,eff
```

Sinon, BEAM-SLS-02 applique explicitement `sr,max = 1,3(h-x_sls)`. Les
coefficients centralisés dans le profil sont `k1=0,8` (HA), `k2=0,5`
(flexion), `k3=3,4`, `k4=0,425`. La différence moyenne de déformation est :

```text
εsm - εcm = [σs - kt(fct,eff/ρp,eff)(1 + αeρp,eff)] / Es
             ≥ 0,6σs / Es
```

La combinaison quasi-permanente est associée à `LONG_TERM`, donc `kt=0,4`.
La voie courte durée (`kt=0,6`) reste un paramètre testable du profil, sans
être exposée au formulaire V1.

## Limites et réserves

Le profil supporte uniquement `XC1 → wmax = 0,4 mm`. Toute autre classe
d'exposition est refusée explicitement : aucune limite n'est inventée. Cette
décision réduit volontairement le périmètre en attendant la validation du
mapping français par classe d'exposition.

Les valeurs recommandées de §7.3.4 sont documentées dans les exemples de
calcul du JRC. La réserve existante sur NF EN 1992-1-1/NA:2016 et A1:2026
reste valable : faute de texte national français exploitable, le profil ne
prétend pas démontrer l'absence de divergence. BEAM-SLS-02 ne calcule ni
flèche, ni statut ELS global, ni conformité globale de poutre, et ne modifie
jamais le ferraillage.
