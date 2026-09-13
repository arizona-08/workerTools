# EC2-03 — Profil français du MVP

## Version ciblée

Le profil `NF_EN_1992_1_1_2005_FR` vise EN 1992-1-1:2004,
NF EN 1992-1-1:2005 et les documents français suivants :

- NF EN 1992-1-1/NA:2016-03-24 ;
- NF EN 1992-1-1/NA/A1:2026-04-14 (amendement d'avril 2026 ciblé par ce
  profil).

Le catalogue AFNOR consulté en septembre 2026 liste également une nouvelle
NF EN 1992-1-1/NA, publiée le 19 août 2026. Cette nouvelle Annexe Nationale
postérieure ne fait pas partie du profil actuellement implémenté : aucune de
ses règles ne doit être déduite sans son texte normatif exploitable.

## Statut de `alphaCc`

La valeur `alphaCc = 1,00` provient de la valeur recommandée par
EN 1992-1-1:2004, 3.1.6(1), que l'Annexe Nationale de 2016 retient.

Le catalogue AFNOR confirme le statut en vigueur de l'amendement d'avril
2026, mais son texte n'est pas accessible publiquement. Son impact éventuel
sur 3.1.6(1) et le domaine d'application de `alphaCc` n'a donc pas pu être
vérifié directement. La valeur n'est pas modifiée tant que le texte de cet
amendement ou un extrait officiel de cette clause n'est pas disponible.

## Valeurs centralisées

| Paramètre | Valeur | Origine / domaine |
|---|---:|---|
| `gammaC` | 1,50 | coefficient partiel béton, profil français |
| `gammaS` | 1,15 | coefficient partiel acier, profil français |
| `alphaCc` | 1,00 | paramètre français utilisé pour `fcd` |
| `k1` contrainte béton caractéristique | 0,60 | valeur recommandée EC2 §7.2 retenue dans le profil, limite `σc,char ≤ 0,60 fck` |
| `k2` contrainte béton quasi-permanente | 0,45 | valeur recommandée EC2 §7.2 retenue dans le profil, limite `σc,qp ≤ 0,45 fck` |
| `k3` contrainte acier caractéristique | 0,80 | valeur recommandée EC2 §7.2 retenue dans le profil, limite `σs,char ≤ 0,80 fyk` |
| `k1` fissuration (barres HA) | 0,80 | valeur recommandée EC2 §7.3.4, coefficient d'adhérence ; distinct des `k1` de §6.2.2 et §8.2 |
| `k2` fissuration (flexion) | 0,50 | valeur recommandée EC2 §7.3.4, distribution des déformations ; distinct du `k2` d'espacement §8.2 |
| `k3` fissuration | 3,40 | valeur recommandée EC2 §7.3.4 pour `sr,max` |
| `k4` fissuration | 0,425 | valeur recommandée EC2 §7.3.4 pour `sr,max` |
| `kt` court / long terme | 0,60 / 0,40 | valeurs recommandées EC2 §7.3.4 ; la combinaison quasi-permanente MVP est associée à `kt = 0,40` |
| `wmax` XC1 | 0,40 mm | seule limite de fissuration explicitement validée dans le profil MVP pour BEAM-SLS-02 ; aucune valeur par défaut pour les autres expositions |
| constante base `l/d` | 11,0 | EC2 §7.4.2, équations 7.16a/b |
| coefficients 7.16a | 1,5 / 3,2 | EC2 §7.4.2, équation 7.16a |
| coefficient compression 7.16b | 1/12 | EC2 §7.4.2, conservé dans le profil ; `ρ' = 0` dans le MVP simplement armé |
| facteur de référence `ρ0` | 0,001 | `ρ0 = sqrt(fck) × 10^-3`, EC2 §7.4.2 |
| référence correction acier | 500 MPa | coefficient de l'expression simplifiée `500/fyk × As_prov/As_req` |
| `K` simplement appuyé | 1,00 | seul facteur structural supporté pour BEAM-SLS-03 |
| coefficient `As_min` lié à `fctm/fyk` | 0,26 | valeur recommandée de §9.2.1.1(1), retenue par l'Annexe Nationale française 2016 |
| ratio minimal `As_min` | 0,0013 | valeur recommandée de §9.2.1.1(1), retenue par l'Annexe Nationale française 2016 |
| `CRd,c` cisaillement | 0,12 | `0,18 / γc` avec `γc = 1,50`, valeur recommandée §6.2.2 retenue pour le MVP faute de divergence française accessible |
| `k1` cisaillement | 0,15 | valeur recommandée de §6.2.2, distincte du `k1` d'espacement §8.2 |
| coefficient de `vmin` | 0,035 | règle §6.2.2 : `vmin = 0,035 × k^(3/2) × sqrt(fck)` |
| profondeur de référence `k` | 200 mm | règle §6.2.2 : `k = min(1 + sqrt(200/d), 2,0)` |
| plafond `ρl` cisaillement | 0,02 | règle §6.2.2 : `ρl = min(Asl/(bw d), 0,02)` |
| borne basse `cot θ` | 1,00 | domaine de modèle retenu pour §6.2.3 |
| borne haute `cot θ` | 2,50 | domaine de modèle retenu pour §6.2.3 |
| coefficient `ρw,min` | 0,08 | règle §9.2.2 : `ρw,min = 0,08 sqrt(fck) / fyk` |
| coefficient de `ν1` | 0,60 | règle §6.2.3 : `ν1 = 0,6 × (1 - fck / 250)` |
| référence de `ν1` | 250 MPa | même règle §6.2.3 |
| `αcw` non précontraint, `NEd = 0` | 1,00 | règle de profil MVP pour §6.2.3 |
| coefficient `s_l,max` | 0,75 | §9.2.2, étriers verticaux : `s_l,max = 0,75d` |
| coefficient `s_t,max` | 0,75 | §9.2.2 : `s_t,max = min(0,75d, 600 mm)` |
| plafond absolu `s_t,max` | 600 mm | §9.2.2 |
| `k1` espacement libre | 1,00 | valeur recommandée de §8.2(2), retenue par l'Annexe Nationale française 2016 |
| `k2` espacement libre | 5 mm | valeur recommandée de §8.2(2), retenue par l'Annexe Nationale française 2016 |
| `gammaGUnfavourable` | 1,35 | EN 1990/NF EN 1990/NA, ELU fondamental bâtiment |
| `gammaGFavourable` | 1,00 | même domaine |
| `gammaQ` | 1,50 | même domaine |
| `psi0`, `psi1`, `psi2` catégorie A | 0,70 / 0,50 / 0,30 | EN 1990, catégorie A d'EN 1991-1-1 |

## Expression ELU fondamentale retenue

Le profil français MVP retient explicitement la procédure française `a` :
l'expression fondamentale **EN 1990 6.10** pour les situations
persistantes/transitoires de bâtiment.

Dans le cas MVP — action permanente gravitaire défavorable et une seule action
variable principale A — la formule est :

```text
wEd = γG,sup × Gk_total + γQ × Qk
```

avec `γG,sup = 1,35`, `γG,inf = 1,00` conservé pour un futur cas favorable,
et `γQ = 1,50`. Aucun coefficient `ψ` ne s'applique à l'action variable
principale. Les expressions 6.10a / 6.10b, le facteur `ξ` et les actions
variables accompagnatrices ne sont pas implémentés dans ce profil MVP.

## Références normatives

- EN 1992-1-1:2004, 2.4.2.4 et 3.1.6 ;
- EN 1992-1-1:2004, §7.2 (limitation des contraintes en service) ;
- EN 1992-1-1:2004, §7.3.2 et §7.3.4 (aire efficace tendue et calcul direct de `wk`) ;
- EN 1992-1-1:2004, §7.4.2, équations 7.16a et 7.16b (dispense de calcul explicite de flèche) ;
- NF EN 1992-1-1:2005 et NF EN 1992-1-1/NA:2016-03-24, qui retient la
  valeur recommandée pour `alphaCc` ;
- NF EN 1992-1-1/NA/A1:2026-04-14, dont le contenu doit être consulté avant
  d'affirmer qu'il ne modifie pas `alphaCc` ;
- NF EN 1992-1-1/NA, publiée le 19 août 2026, hors périmètre du profil actuel
  tant que son contenu n'est pas analysé ;
- EN 1990, 6.4.3.3 et annexe A1, avec NF EN 1990/NA ;
- EN 1991-1-1, catégorie A d'actions variables.

## Limites explicites

- Les facteurs d'actions ne couvrent que la combinaison ELU fondamentale en
  situation persistante ou transitoire pour bâtiment.
- Cette combinaison est explicitement EN 1990 6.10 ; 6.10a / 6.10b et `ξ`
  sont hors périmètre.
- Seule la catégorie d'action variable A est disponible. Toute autre catégorie
  doit être ajoutée avec ses propres facteurs, sans valeur par défaut.
- Les situations accidentelles, sismiques, les actions climatiques et les
  autres catégories d'EN 1991 restent hors périmètre.
- `fcd` et `fyd` sont calculés à la demande depuis le matériau et ce profil ;
  ils ne sont pas des propriétés intrinsèques et ne sont jamais stockés. Le
  module Poutre les réutilise pour BEAM-FLEX-02 via
  `ConcreteDesignStrengthCalculator` et
  `ReinforcementSteelDesignStrengthCalculator`, sans redéfinir leurs formules.
- Les paramètres d'armature minimale de poutre sont conservés dans
  `BeamLongitudinalReinforcementRequirements` du profil. BEAM-FLEX-07 emploie
  `fctm` et `fyk` issus des matériaux, jamais `fyd`; le texte accessible de
  l'amendement A1:2026-04-14 ne permet pas d'établir qu'il modifie ce choix de
  l'Annexe 2016.
- Les paramètres d'espacement libre `k1`, `k2` et le minimum absolu de 20 mm
  sont conservés dans `ReinforcementSpacingRequirements` du profil.
- Les paramètres de résistance béton au cisaillement sont conservés séparément
  dans `BeamConcreteShearResistanceRequirements`. Ils concernent uniquement
  EN 1992-1-1 §6.2.2 : ils ne doivent pas être confondus avec les paramètres
  d'espacement. L'amendement A1:2026-04-14 est en vigueur, mais son texte
  complet n'étant pas publiquement accessible, une validation exhaustive de
  son impact sur ces paramètres reste à confirmer.
- Le profil ne prétend pas couvrir la NF EN 1992-1-1/NA publiée en août 2026;
  sa prise en compte demandera une analyse normative dédiée avant toute mise à
  jour des paramètres.
- Les facteurs ELS `0,60`, `0,45` et `0,80` sont les valeurs recommandées de
  l'EN 1992-1-1 §7.2, reprises dans les documents JRC. Aucune divergence de
  l'Annexe Nationale française 2016/A1:2026 n'a été établie par une source
  normative exploitable ; ils restent donc centralisés dans le profil et non
  dans le calculateur. Cette réserve devra être levée lors de l'analyse du
  texte intégral de l'Annexe Nationale concernée.
- Les paramètres de fissuration BEAM-SLS-02 sont limités à des barres HA en
  flexion simple et à la classe d'exposition XC1. Ils reprennent les valeurs
  recommandées première génération présentées par le JRC ; aucune limite
  `wmax` pour une autre exposition n'est interpolée. La confirmation d'une
  éventuelle divergence de NF EN 1992-1-1/NA:2016 ou A1:2026 nécessite le
  texte normatif français exploitable.
- Les paramètres BEAM-SLS-03 de la méthode `SIMPLIFIED_SPAN_DEPTH` sont
  centralisés dans `BeamDeflectionRequirements`. Le modèle est limité à la
  poutre rectangulaire simplement appuyée et aux sections simplement armées;
  aucun facteur pour systèmes continus, consoles, dalles, cloisons fragiles ou
  acier comprimé n'est interpolé. La même réserve nationale 2016/A1:2026 reste
  applicable faute de texte français exploitable établissant une divergence.
