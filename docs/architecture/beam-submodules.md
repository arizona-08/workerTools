# BEAM-SUB-08 — Extension des sous-modules Poutre

Ce document décrit l’architecture réellement disponible dans WorkerTools V1
pour le module `BEAM`. Il sert de guide développeur : il ne rend aucun cas
supplémentaire disponible.

## Sous-modules actuels

```text
BEAM
├── BEAM_SIMPLE_RECTANGULAR      → SIMPLY_SUPPORTED
└── BEAM_CANTILEVER_RECTANGULAR  → CANTILEVER
```

Les identifiants sont déclarés dans
`backend/app/StructuralCalculation/Beams/BeamSubmodule.php`. Leur système
statique et leur face tendue sont portés par les méthodes `supportSystem()` et
`tensionFace()` de cet enum. La validation de configuration dans
`BeamCalculationConfigurationValidator` refuse une association incohérente
entre `submodule` et `supportSystem`.

## Catalogue et sélecteur

Le catalogue métier est `BeamSubmoduleCatalog`. Chaque
`BeamSubmoduleCatalogEntry` expose :

- `id` : l’identifiant stable `BeamSubmodule` ;
- `label` : le libellé produit ;
- `status` : `AVAILABLE`, `COMING_SOON` ou `UNAVAILABLE` ;
- `supportSystem` : dérivé de l’identifiant métier, jamais du libellé.

`BeamMaterialCatalogController` publie ces entrées via
`GET /api/beam/material-catalog`. Côté Angular,
`BeamMaterialCatalogService` consomme ce catalogue et `BeamForm` l’utilise pour
rendre le sélecteur. Une option non `AVAILABLE` est désactivée ; elle ne peut
pas être sélectionnée. Une modification de sous-module émet `formChanged`, ce
qui invalide le résultat précédent dans `Calculator`.

Un sous-module ne doit être marqué `AVAILABLE` que lorsque son parcours complet
est réellement fonctionnel : validation, calcul, résultat commun, interface,
export PDF et tests de référence. `COMING_SOON` et `UNAVAILABLE` restent des
états d’affichage produit : ils ne doivent pas servir de substitution à une
validation métier backend.

Le type TypeScript `BeamSubmodule` dans
`frontend/src/app/features/calculator/forms/beam-form/beam-calculation-configuration.ts`
reste un contrat de compilation. Lors de l’ajout d’un identifiant backend, ce
contrat doit être mis à jour dans le même changement ; les options visibles ne
doivent toutefois jamais être codées dans une liste frontend parallèle.

## Chemin d’un calcul

```text
Sélecteur Angular / catalogue API
             │  id + supportSystem
             ▼
BeamCalculationInputFactory + BeamCalculationConfigurationValidator
             │
             ▼
BeamCalculationOrchestrator::calculate()
             │  match (BeamSubmodule)
             ▼
analyse propre au sous-module
             │
             ▼
pipeline Beam commun lorsque son domaine est compatible
  charges → combinaisons → flexion → ferraillage
  → cisaillement → ELS → agrégation → résultat
             │
             ▼
BeamCalculationResponse
  status · summary · verifications · details · warnings
             ├──────────────► composants Angular de résultat
             └──────────────► BeamCalculationNoteMapper → PDF commun
```

Le routage est actuellement un `match` explicite dans
`BeamCalculationOrchestrator` :

- `BEAM_SIMPLE_RECTANGULAR` appelle `calculateSimplySupported()` ;
- `BEAM_CANTILEVER_RECTANGULAR` appelle `calculateCantileverAnalysis()`.

Le `match` est volontairement explicite : l’ajout d’une valeur sans branche ne
doit pas retomber silencieusement sur le calcul simplement appuyé.

## Commun et variable

Les référentiels matériaux, le poids propre, les actions caractéristiques, les
combinaisons, les services de flexion réutilisables, le ferraillage, l’agrégation
des vérifications, `BeamCalculationResponse`, les composants de résultat et le
renderer PDF restent communs.

Les parties qui dépendent effectivement du sous-module sont :

- le système statique et l’analyse des efforts internes ;
- la convention de signe et la localisation des efforts ;
- la face tendue et la position des armatures principales ;
- les vérifications dont le domaine d’application doit être réévalué ;
- les libellés de résultat ou de PDF, alimentés par les métadonnées structurées.

Exemple actuel : la console réutilise la flexion, mais son cisaillement à
l’encastrement, sa fissuration dépendante des étriers et sa déformation
simplifiée renvoient explicitement `CALCULATION_METHOD_NOT_SUPPORTED`. Cette
limitation ne doit jamais être convertie en conformité par une nouvelle branche.

## Procédure pour ajouter un sous-module

1. Ajouter l’identifiant dans `BeamSubmodule`, avec son `supportSystem()` et sa
   `tensionFace()` uniquement s’ils sont normativement définis.
2. Ajouter son entrée dans `BeamSubmoduleCatalog`, avec un statut non disponible
   tant que le parcours n’est pas complet.
3. Étendre les contrats TypeScript correspondants et vérifier que le sélecteur
   consomme toujours le catalogue API.
4. Adapter `BeamCalculationConfigurationValidator` seulement si le nouveau cas
   requiert une configuration réellement différente ; conserver le contrôle de
   cohérence entre identifiant et système statique.
5. Ajouter une branche explicite dans `BeamCalculationOrchestrator` et
   implémenter uniquement l’analyse structurelle spécifique.
6. Réutiliser les services communs seulement après avoir vérifié leur domaine
   d’application ; documenter et retourner une limitation structurée sinon.
7. Conserver le contrat `status`, `summary`, `verifications`, `details`,
   `warnings`. Les différences sont portées par les données, non par une réponse
   parallèle.
8. Étendre `BeamCalculationNoteMapper` avec les métadonnées nécessaires, sans
   créer un générateur ou un template PDF complet distinct.
9. Ajouter les tests unitaires de la mécanique spécifique, un test de pipeline,
   un cas de référence indépendant et les non-régressions des deux sous-modules
   existants.
10. Mettre à jour `docs/domain/variables-and-constants.md`, les notes normatives
    concernées et `docs/qa/beam-reference-cases.md`.

## Règles de réutilisation et de sûreté normative

Ne pas dupliquer l’orchestrateur complet, les référentiels matériaux, les
combinaisons, les services Eurocode communs, le contrat de résultat ni le
générateur PDF. Un nouveau sous-module ne modifie que ce qui relève réellement
de sa mécanique.

Une règle existante ne peut être réutilisée que si son domaine normatif couvre le
nouveau système. Lorsqu’une donnée, une position de section critique ou une
formule n’est pas validée, retourner `CALCULATION_METHOD_NOT_SUPPORTED` avec une
limitation explicite. Il est interdit de compléter une règle par analogie.

## Tests attendus

Pour chaque sous-module :

- tests unitaires de l’analyse et des branches qui lui sont propres ;
- tests des invariants de configuration ;
- test du contrat de résultat et de la conformité agrégée ;
- test de pipeline sans mock des services métier ;
- cas de référence indépendant, avec valeurs chiffrées et tolérance explicite ;
- non-régression des sous-modules existants ;
- test PDF si l’export est disponible.

Les valeurs attendues ne doivent pas être calculées en rappelant le service de
production testé. Les références actuelles vivent dans
`backend/tests/Feature/StructuralCalculation/Beams/BeamSubmoduleReferenceCasesTest.php`
et `docs/qa/beam-reference-cases.md`.

## Résultat, PDF et historique

Tous les sous-modules Beam tendent vers le même contrat de réponse. Le frontend
affiche ce contrat avec les composants de résultat existants ; le PDF utilise
`BeamCalculationNoteMapper` puis `CalculationNoteRenderer`. Les métadonnées de
sous-module enrichissent ces projections sans recréer leur structure.

`BEAM-SUB-05 — Historique compatible` reste reporté. Lorsqu’un calcul sera
persisté, son identifiant `submodule` devra être enregistré avec les autres
entrées afin de reconstituer exactement le cas calculé.

## Limites V1 explicites

Seuls `BEAM_SIMPLE_RECTANGULAR` et `BEAM_CANTILEVER_RECTANGULAR` sont supportés.
Restent hors périmètre : poutres continues, sections en T, sections en L,
sections circulaires ou variables, charges ponctuelles ou triangulaires,
moments appliqués, multi-travées et encastrement-encastrement.

Les poutres continues exigeront une évolution de la couche d’analyse statique.
Les sections T/L exigeront une géométrie et des hypothèses de compression
adaptées. Les charges ponctuelles exigeront une évolution du modèle de charge et
de l’analyse. Aucun de ces éléments n’est préparé par du code spéculatif dans la
V1 actuelle.
