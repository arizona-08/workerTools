# Aire cible de ferraillage Poutre — BEAM-REBAR-01

## Chaîne de dimensionnement

BEAM-FLEX-06 fournit l'aire théorique nécessaire à l'équilibre ELU :

`As_req = MEd / (fyd × z)`

BEAM-FLEX-07 fournit séparément l'armature minimale réglementaire `As_min`.
BEAM-REBAR-01 produit l'aire continue que le futur ferraillage devra atteindre :

`As_target = max(As_req, As_min)`

Le résultat indique explicitement l'exigence gouvernante :

- `FLEXURAL_DEMAND` si `As_req > As_min` ;
- `MINIMUM_REINFORCEMENT` si `As_min > As_req` ;
- `EQUAL_REQUIREMENTS` si les deux aires sont égales.

## Domaine et limites

Le calculateur reçoit le résultat de BEAM-FLEX-08. Si le modèle simplement
armé n'est pas valide — l'acier tendu n'atteint pas `fyd` — il refuse de
produire `As_target`; une aire issue de BEAM-FLEX-06 ne serait alors pas
utilisable par une proposition de ferraillage.

`As_target` reste une aire théorique en `mm²`. Cette étape ne sélectionne ni
diamètre, ni nombre de barres, ne calcule aucune aire fournie `As_prov`, ne
vérifie aucun logement géométrique et ne produit ni `MRd` ni conformité. La
discrétisation future devra choisir un ferraillage dont `As_prov ≥ As_target`.
