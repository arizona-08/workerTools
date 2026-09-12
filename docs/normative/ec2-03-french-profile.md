# EC2-03 — Profil français du MVP

## Version ciblée

Le profil `NF_EN_1992_1_1_2005_FR` vise EN 1992-1-1:2004,
NF EN 1992-1-1:2005 et les documents français suivants :

- NF EN 1992-1-1/NA:2016-03-24 ;
- NF EN 1992-1-1/NA/A1:2026-04-14 (amendement d'avril 2026 en vigueur).

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
| coefficient `As_min` lié à `fctm/fyk` | 0,26 | valeur recommandée de §9.2.1.1(1), retenue par l'Annexe Nationale française 2016 |
| ratio minimal `As_min` | 0,0013 | valeur recommandée de §9.2.1.1(1), retenue par l'Annexe Nationale française 2016 |
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
- NF EN 1992-1-1:2005 et NF EN 1992-1-1/NA:2016-03-24, qui retient la
  valeur recommandée pour `alphaCc` ;
- NF EN 1992-1-1/NA/A1:2026-04-14, document en vigueur dont le contenu
  doit être consulté avant d'affirmer qu'il ne modifie pas `alphaCc` ;
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
