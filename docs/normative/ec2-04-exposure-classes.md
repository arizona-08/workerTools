# EC2-04 — Classes d'exposition

## Références et périmètre

Le référentiel suit la classification du tableau 4.1 de EN 1992-1-1:2004,
transposée par NF EN 1992-1-1:2005 et précisée pour la France par
NF EN 1992-1-1/NA:2016-03-24. Il ne met pas en œuvre les Eurocodes de
deuxième génération. L'amendement NF EN 1992-1-1/NA/A1:2026-04-14 est
en vigueur, mais son texte n'étant pas accessible publiquement, son impact
sur les précisions françaises du tableau 4.1 reste à vérifier avant toute
règle de durabilité ou d'enrobage.

## Classes supportées

| Famille | Classes | Signification |
|---|---|---|
| Aucun risque | X0 | Aucun risque de corrosion ou d'attaque |
| Carbonatation | XC1, XC2, XC3, XC4 | Corrosion des armatures induite par carbonatation |
| Chlorures hors eau de mer | XD1, XD2, XD3 | Corrosion des armatures induite par chlorures non marins |
| Chlorures d'eau de mer | XS1, XS2, XS3 | Corrosion des armatures induite par chlorures d'eau de mer |
| Gel/dégel | XF1, XF2, XF3, XF4 | Dégradation par gel/dégel selon saturation et salage |
| Attaque chimique | XA1, XA2, XA3 | Attaque du béton par sol ou liquide agressif |

## Limites explicites

- Une classe d'exposition décrit un environnement ; elle n'est pas une valeur
  d'enrobage. Le référentiel ne contient ni `c_min,dur`, ni `c_nom`, ni classe
  structurale, ni tolérance d'exécution.
- Plusieurs classes peuvent être représentées simultanément par
  `ExposureConditions`. La sélection de l'exigence gouvernante sera faite dans
  EC2-05 selon le profil normatif et les autres paramètres nécessaires.
- En France, les classes XF sont bien retenues mais leur enrobage est traité
  par référence à une classe XC ou XD ; ce lien n'est volontairement pas codé
  ici. Les classes XA exigent une caractérisation de l'agent agressif et ne
  reçoivent aucune valeur générique dans ce référentiel.
