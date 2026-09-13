# BEAM-SLS-03 — Vérification simplifiée de déformation

## Méthode et domaine

BEAM-SLS-03 applique EN 1992-1-1:2004 / NF EN 1992-1-1:2005 §7.4.2 et les
équations 7.16a/b afin de déterminer si le calcul explicite de déformation peut
être omis. La méthode retournée est `SIMPLIFIED_SPAN_DEPTH` : elle ne calcule
**aucune flèche en mm**, courbure, retrait, fluage `φ(t,t0)` ou module effectif.

Elle est strictement limitée au MVP : poutre en béton armé, section
rectangulaire, simplement appuyée, flexion simple et section simplement armée.
Un candidat insuffisant ou hors domaine est refusé explicitement, sans statut
de conformité.

## Grandeurs et formules

La portée `l_eff` est réutilisée de la géométrie et la hauteur utile `d`, ainsi
que `As_req` et `As_prov`, proviennent du recalcul du **candidat longitudinal
réel**. La méthode utilise :

```text
ρ  = As_req / (b d)
ρ0 = sqrt(fck) × 10^-3
ρ' = 0  (MVP, pas d'armature comprimée)
```

Pour `ρ ≤ ρ0`, elle applique l'équation 7.16a :

```text
(l/d)_0 = K[11 + 1,5sqrt(fck)(ρ0/ρ)
             + 3,2sqrt(fck)(ρ0/ρ - 1)^(3/2)]
```

Pour `ρ > ρ0`, l'équation 7.16b est utilisée. Puisque `ρ'=0`, elle se réduit
dans le MVP à `K[11 + 1,5sqrt(fck)ρ0/ρ]`. La frontière `ρ = ρ0` utilise de
façon déterministe la branche 7.16a.

Le seul facteur structural intégré est `K=1,0` pour une poutre simplement
appuyée. Le résultat est corrigé par
`(500/fyk) × (As_prov/As_req)` puis comparé par `l_eff/d ≤ (l/d)_adm`.

## Interprétation et limites

Un statut `COMPLIANT` signifie seulement que le critère simplifié `l/d` est
satisfait dans ce domaine. Il ne constitue pas une valeur de flèche calculée.
Les warnings exposent les limites : méthode simplifiée uniquement, effets long
terme non modélisés explicitement et absence de contrôle des cloisons
susceptibles d'être endommagées.

Les coefficients sont ceux de la première génération EC2 et sont centralisés
dans le profil français. Les supports continus, consoles, dalles, acier
comprimé et toute correction nécessitant des données produit absentes restent
hors périmètre. La réserve documentaire sur NF EN 1992-1-1/NA:2016 et
A1:2026 demeure inchangée.
