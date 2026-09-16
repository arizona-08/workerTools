# BEAM-SLS-03 — Vérification simplifiée de déformation

## Méthode et domaine

BEAM-SLS-03 applique EN 1992-1-1:2004 / NF EN 1992-1-1:2005 §7.4.2 et les
équations 7.16a/b afin de déterminer si le calcul explicite de déformation peut
être omis. La méthode retournée est `SIMPLIFIED_SPAN_DEPTH` : elle ne calcule
**aucune flèche en mm**, courbure, retrait, fluage `φ(t,t0)` ou module effectif.

Elle est strictement limitée au V1 : poutre en béton armé, section
rectangulaire, simplement appuyée ou en console, flexion simple et section
simplement armée. Un candidat insuffisant ou hors domaine est refusé
explicitement, sans statut de conformité.

## Grandeurs et formules

La portée `l_eff` est réutilisée de la géométrie et la hauteur utile `d`, ainsi
que `As_req` et `As_prov`, proviennent du recalcul du **candidat longitudinal
réel**. La méthode utilise :

```text
ρ  = As_req / (b d)
ρ0 = sqrt(fck) × 10^-3
ρ' = 0  (V1, pas d'armature comprimée)
```

Pour `ρ ≤ ρ0`, elle applique l'équation 7.16a :

```text
(l/d)_0 = K[11 + 1,5sqrt(fck)(ρ0/ρ)
             + 3,2sqrt(fck)(ρ0/ρ - 1)^(3/2)]
```

Pour `ρ > ρ0`, l'équation 7.16b est utilisée. Puisque `ρ'=0`, elle se réduit
dans le V1 à `K[11 + 1,5sqrt(fck)ρ0/ρ]`. La frontière `ρ = ρ0` utilise de
façon déterministe la branche 7.16a.

Le facteur structural est résolu exclusivement depuis le profil : `K=1,0`
pour une poutre simplement appuyée et `K=0,4` pour une console. Le résultat est corrigé par
`(500/fyk) × (As_prov/As_req)` puis comparé par `l_eff/d ≤ (l/d)_adm`.

## Interprétation et limites

Un statut `COMPLIANT` signifie seulement que le critère simplifié `l/d` est
satisfait dans ce domaine. Il ne constitue pas une valeur de flèche calculée.
Les warnings exposent les limites : méthode simplifiée uniquement, effets long
terme non modélisés explicitement et absence de contrôle des cloisons
susceptibles d'être endommagées.

Les coefficients sont ceux de la première génération EC2 et sont centralisés
dans le profil français. Les supports continus, dalles, acier comprimé et toute
correction nécessitant des données produit absentes restent hors périmètre. La
réserve documentaire sur NF EN 1992-1-1/NA:2016 et A1:2026 demeure inchangée.

## Console — BEAM-CANT-05B

EN 1992-1-1:2004 §7.4.2, tableau 7.4N, définit `K = 0,4` pour une console.
`FrenchEurocodeProfileRepository` porte cette valeur dans
`BeamDeflectionRequirements`, séparée de `K = 1,0` du cas simplement appuyé.
Le calculateur commun applique donc les expressions 7.16a/b sans branche
spécifique : `L` est la longueur efficace entre l'encastrement et l'extrémité
libre, et `d` est la hauteur utile du lit longitudinal `TOP` déterminée par la
chaîne de flexion. Les détails conservent `L`, `d`, `K`, le rapport réel, la
limite admissible et le taux.

Ce contrôle reste une dispense de calcul explicite de flèche. Il ne traite ni
la flèche en millimètres, ni le fluage, le retrait, l'acier comprimé ou les
éléments sensibles aux déformations.
