# QA-03 — Cas non conformes

Les scénarios emploient uniquement le V1 : C30/37, B500B, XC1, poutres rectangulaires ou dalles pleines unidirectionnelles simplement appuyées, sous charges uniformément réparties. Les entrées sont valides ; l'échec est structurel ou relève d'une capacité de catalogue existante.

| Cas | Entrées principales | Cause indépendante | Résultat attendu |
|---|---|---|---|
| Poutre — fissuration | L=6,5 m, 300×600 mm, Gadd=5, Qk=3,5 kN/m | `wk=0,579870 mm > wk,max=0,400 mm`, soit 1,449675 | `CRACK` et global `NOT_COMPLIANT`, gouvernante `CRACK` |
| Poutre — acier fourni insuffisant | même poutre, mode vérification, 2 HA8 | `As,prov=2π8²/4=100,53 mm²`, contre une demande voisine de 400 mm² | rejet `NO_VALID_LONGITUDINAL_REINFORCEMENT_CANDIDATE` |
| Poutre — cisaillement | L=2 m, 200×600 mm, Gadd=5, Qk=250 kN/m | `Gk=8`, `wEd=385,8 kN/m`, `VEd=385,8 kN`; aucun étrier catalogue n'est admissible | rejet `NO_VALID_STIRRUP_CANDIDATE` |
| Dalle — flèche | L=5 m, h=200 mm, finitions/cloisons/autres/Qk=1,5/1/0,5/5 kN/m² | `L/d=29,585799 > 22,526807`, soit 1,313360 | `DEFLECTION` et global `NOT_COMPLIANT` |
| Dalle — aucun ferraillage principal | L=10 m, h=200 mm, finitions/cloisons/autres/Qk=1,5/1/0,5/3 kN/m² | `As,design=3412,258 mm²/m`; aucun couple diamètre/espacement V1 n'est admissible après recalcul | `NO_VALID_REINFORCEMENT_PROPOSAL`, `MAIN_REINFORCEMENT` et global `NOT_COMPLIANT` |

Le premier cas Poutre et les deux cas Dalle traversent le contrat final et contrôlent les statuts locaux, le statut global et la vérification gouvernante lorsqu'elle est disponible. Les deux chaînes Poutre qui s'arrêtent avant l'agrégation exposent aujourd'hui une `LogicException` de capacité, au lieu d'un `BeamCalculationResponse` avec statut métier : cette limitation reste distincte de `NOT_COMPLIANT` et de `CALCULATION_METHOD_NOT_SUPPORTED`.

Une correction minimale est apportée côté Dalle : l'absence de proposition automatique valide passe de `NOT_CHECKED` à `NOT_COMPLIANT` pour `MAIN_REINFORCEMENT`. Aucune formule, limite normative, classe de matériau ou système statique n'a été ajouté.
