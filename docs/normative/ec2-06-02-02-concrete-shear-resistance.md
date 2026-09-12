# EC2 §6.2.2 — Résistance au cisaillement sans armatures transversales

## Périmètre BEAM-SHEAR-01

BEAM-SHEAR-01 applique la vérification de résistance béton `VRd,c` des
éléments ne nécessitant pas d'armatures transversales de calcul, selon
EN 1992-1-1:2004 / NF EN 1992-1-1:2005 §6.2.2. Elle répond seulement à la
question : `VEd <= VRd,c` ? Une réponse positive ne constitue pas une
conformité globale au cisaillement : les armatures minimales, `VRd,s`,
`VRd,max` et les dispositions constructives restent hors de cette étape.

## Expressions employées

Avec `d` et `bw` en mm, `Asl` en mm² et les contraintes en MPa :

```text
k = min(1 + sqrt(200 / d), 2,0)
ρl = min(Asl / (bw × d), 0,02)
σcp = NEd / Ac

vRd,c,main = CRd,c × k × (100 × ρl × fck)^(1/3) + k1 × σcp
vmin = 0,035 × k^(3/2) × sqrt(fck)
vRd,c,min = vmin + k1 × σcp
vRd,c = max(vRd,c,main, vRd,c,min)
VRd,c = vRd,c × bw × d
```

Le produit final est obtenu en N puis converti explicitement en kN. `fck`, et
non `fcd`, est utilisé dans l'expression principale et dans `vmin`.

## Paramètres du profil français MVP

`BeamConcreteShearResistanceRequirements` centralise les paramètres du profil
`NF_EN_1992_1_1_2005_FR` : `CRd,c = 0,12` (`0,18 / 1,50`), coefficient de
compression `k1 = 0,15`, coefficient de `vmin = 0,035`, profondeur de
référence `200 mm`, plafond `k = 2,0` et plafond `ρl = 0,02`. Le `k1` de
cisaillement est distinct de celui utilisé pour l'espacement des barres (§8.2).

Ces valeurs recommandées sont retenues faute de divergence française explicite
dans les sources accessibles. Le profil cible NF EN 1992-1-1/NA:2016 et son
amendement A1 d'avril 2026; le texte complet de cet amendement n'étant pas
accessible publiquement, son impact exhaustif sur §6.2.2 reste à confirmer.
Une nouvelle NF EN 1992-1-1/NA publiée en août 2026 est hors périmètre du
profil actuel et devra être analysée avant toute affirmation de compatibilité.

## Hypothèses et limites MVP

La section est rectangulaire, donc `bw = b`. Le moteur reçoit le `d` associé
aux armatures réellement évaluées et ne le recalcule jamais. `Asl` est l'aire
longitudinale fournie : `As_prov` du candidat en DESIGN ou aire existante en
VERIFICATION; elle n'est pas remplacée par `As_req`.

Le MVP fixe `NEd = 0 kN`, donc conserve explicitement `Ac = b × h` et
`σcp = 0 MPa`, mais refuse tout cas avec effort normal non nul. Il suppose que
les armatures `Asl` sont pertinentes et correctement ancrées au droit de la
section étudiée; aucune vérification d'ancrage ou de position n'est réalisée.

## Dimensionnement théorique des étriers — BEAM-SHEAR-02

Pour les étriers verticaux MVP, `α = 90°`, donc `sin α = 1` et `cot α = 0`.
BEAM-SHEAR-02 applique EN 1992-1-1 §6.2.3 avec le modèle à inclinaison
variable :

```text
VRd,s = (Asw/s) × z × fywd × cot θ
Asw/s_req = VEd / (z × fywd × cot θ)
```

Le profil détermine la plage `1,0 ≤ cot θ ≤ 2,5`. La valeur de départ
`cot θ = 2,5` appartient à `BeamShearDesignAssumptions` : c'est une stratégie
MVP de dimensionnement et non une valeur imposée universellement par EC2. Le
choix devra être contrôlé contre `VRd,max` dans BEAM-SHEAR-03, ce que cette
étape ne calcule pas.

Le minimum transversal des poutres standard est traité séparément selon
EN 1992-1-1 §9.2.2 :

```text
ρw,min = 0,08 × sqrt(fck) / fyk
Asw/s_min = ρw,min × bw
Asw/s_target = max(Asw/s_req, Asw/s_min)
```

La formule de minimum utilise `fyk`; le modèle de treillis utilise `fywd`,
dérivé du même acier B500B et du profil. `z` provient du calcul de flexion
réel, pas de `0,9d`. Lorsque `VEd ≤ VRd,c`, `Asw/s_req = 0`, mais le minimum
reste la cible pour la poutre MVP : aucune exemption n'est inventée.

La demande des étriers est calculée contre `VEd` entier, jamais contre
`VEd - VRd,c`; le moteur ne cumule jamais `VRd,c + VRd,s`. Aucune proposition
discrète (diamètre, branches, espacement) ni `VRd,max` ne relève de cette
étape.

## Résistance maximale des bielles — BEAM-SHEAR-03

BEAM-SHEAR-03 complète le contrôle local EN 1992-1-1 §6.2.3, sans transformer
son résultat en conformité globale. À partir du même `cot θ` que
BEAM-SHEAR-02 :

```text
tan θ = 1 / cot θ
ν1 = 0,6 × (1 - fck / 250)
VRd,max = αcw × bw × z × ν1 × fcd / (cot θ + tan θ)
```

Le calcul interne utilise `bw` et `z` en mm, `fcd` en MPa (`N/mm²`), puis
convertit le résultat N en kN. Il utilise le `z` mécanique réel fourni par
BEAM-FLEX-05 / BEAM-REBAR-04, jamais `0,9d`; `fcd` est la résistance de calcul
existante, non une nouvelle dérivation de `αcc` et `γc`.

Pour le MVP non précontraint sans effort normal (`NEd = 0`), `αcw = 1,0` est
conservé explicitement dans le profil. Les branches liées à la précontrainte ou
à la compression normale ne sont pas implémentées. Un dépassement de
`VRd,max` est retourné comme tel : augmenter `Asw/s` ne corrige pas la
compression des bielles. `VRd,s` et `VRd,max` restent deux résistances
distinctes et ne sont ni additionnées entre elles ni avec `VRd,c`.

La même réserve de validation exhaustive du profil français 2016 + A1:2026,
ainsi que de la nouvelle Annexe Nationale publiée en août 2026, s'applique aux
paramètres de ce contrôle.
