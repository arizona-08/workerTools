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
| `gammaGUnfavourable` | 1,35 | EN 1990/NF EN 1990/NA, ELU fondamental bâtiment |
| `gammaGFavourable` | 1,00 | même domaine |
| `gammaQ` | 1,50 | même domaine |
| `psi0`, `psi1`, `psi2` catégorie A | 0,70 / 0,50 / 0,30 | EN 1990, catégorie A d'EN 1991-1-1 |

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
- Seule la catégorie d'action variable A est disponible. Toute autre catégorie
  doit être ajoutée avec ses propres facteurs, sans valeur par défaut.
- Les situations accidentelles, sismiques, les actions climatiques et les
  autres catégories d'EN 1991 restent hors périmètre.
- `fcd` et `fyd` sont calculés à la demande depuis le matériau et ce profil ;
  ils ne sont pas des propriétés intrinsèques et ne sont jamais stockés.
