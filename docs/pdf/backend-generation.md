# PDF-02 — Génération backend

## Chaîne de rendu

Le rendu backend est volontairement limité à la présentation du contrat
`CalculationNoteDocument` introduit par PDF-01 :

```text
CalculationNoteDocument → CalculationNoteRenderer → Blade → octets PDF
```

`DompdfCalculationNoteRenderer` implémente `CalculationNoteRenderer` et
retourne `RenderedCalculationNote` (`content`, `mimeType`, `filename`). Les
octets restent en mémoire : cette étape ne crée aucun fichier, stockage,
endpoint HTTP ni téléchargement frontend.

Les adaptateurs des résultats Poutre et Dalle vers ce contrat restent
explicitement hors périmètre (PDF-03 et PDF-04). Le renderer ne connaît donc
ni les calculateurs, ni les formules Eurocode, ni les règles d'agrégation de
statut ; il affiche strictement les valeurs, statuts, avertissements et limites
déjà transportés par le document.

## Bibliothèque retenue

- `barryvdh/laravel-dompdf` `v3.1.2`
- moteur `dompdf/dompdf` `v3.1.6`

Ce choix fournit une intégration Laravel légère, un rendu HTML/Blade et les
polices DejaVu nécessaires aux caractères UTF-8 techniques. Le rendu utilise
`DejaVu Sans`, A4 portrait et des marges d'impression définies dans le
template. Dompdf est adapté à une note technique sobre (tableaux, texte et
formules simples) ; il n'est pas destiné à reproduire un navigateur moderne
pour des mises en page CSS avancées ou des graphiques complexes.

Il ne dépend d'aucun binaire Chromium, wkhtmltopdf ou paquet système
supplémentaire : `backend/Dockerfile.dev` reste donc inchangé. Le conteneur PHP
Alpine actuel est compatible avec cette dépendance Composer.

## Template et robustesse

Le template commun est `resources/views/pdf/calculation-note.blade.php`, avec
une partial récursive pour les sections. Il affiche seulement les sections qui
portent des données. Une valeur absente est rendue `—`, jamais `0`.

Les unités restent explicites. Les caractères tels que `²`, `³`, `φ`, `γ`,
`ψ`, `μ`, `ξ`, `ρ`, `σ`, `ε`, `≤`, `≥` et `·` sont passés au HTML UTF-8 puis à
la police DejaVu. Les avertissements, limitations et statuts sont écrits en
toutes lettres ; les couleurs éventuelles ne portent jamais seules une
information métier.

Toute erreur de compilation Blade ou de rendu est journalisée avec le type de
calcul et remontée sous `CalculationNoteRenderingException`. Cette exception
pourra être transformée par le futur endpoint PDF-05, sans divulguer une erreur
technique au client.

## Vérification et CI

Les tests unitaires couvrent la signature PDF, le MIME et le nom de fichier,
le contenu UTF-8, les sections optionnelles, les avertissements/limitations,
les statuts `NOT_COMPLIANT` et `CALCULATION_METHOD_NOT_SUPPORTED`, ainsi qu'un
document multi-page. Ils sont inclus dans la commande backend standard
`php artisan test`, déjà exécutée par la pipeline CI.

## Mapping Poutre (PDF-03)

`BeamCalculationNoteMapper::map(BeamCalculationSetup $input,
BeamCalculationResponse $result, DateTimeImmutable $generatedAt)` transforme
uniquement les données déjà produites par le pipeline Poutre. Il ne dépend pas
des repositories de matériaux et ne réexécute aucune formule, combinaison ou
agrégation de statut.

| Source Beam réelle | Cible `CalculationNoteDocument` |
|---|---|
| `input.configuration` | métadonnées, hypothèses de modèle et profil normatif |
| `input.geometry` + `details.assumptions.cover` + profondeur finale sélectionnée | géométrie (`l_eff`, `b`, `h`, `c_nom`, `d`) |
| `input.materials` + `details.flexure` + candidat sélectionné | matériaux (`fck`, `fctm`, `fcd`, `fyk`, `fyd`, `Es`) |
| `details.combinations.characteristicActions` | charges `Gk,self`, `Gk,additional`, `Gk,total`, `Qk` |
| `details.combinations` | ELU et trois combinaisons ELS avec leurs formules existantes |
| `details.internalForces` | `MEd`, `VEd` et moments ELS |
| `verifications` + `details.flexure/shear/serviceability` | cinq vérifications détaillées |
| `summary.longitudinalReinforcement` + étrier recommandé | ferraillage longitudinal et étriers |
| `summary.status`, `summary.governingVerificationType`, `summary.utilization` et cartes disponibles | statut final et résumé sans recalcul |
| `details.warnings` | avertissements, sans filtrage |

En `DESIGN`, le libellé est « Ferraillage longitudinal proposé ». En
`VERIFICATION`, la source `PROVIDED` du résumé devient « Ferraillage
longitudinal fourni » : une armature saisie n'est jamais présentée comme une
proposition WorkerTools.

`Ecm` n'est pas exposé dans `BeamCalculationResponse` ni ses détails actuels :
le mapper ne le reconstruit donc pas. De même, aucune flèche physique en mm
n'est créée ; seule la méthode réellement calculée `SIMPLIFIED_SPAN_DEPTH` et
les rapports `l/d` sont documentés.

## Mapping Dalle (PDF-04)

`SlabCalculationNoteMapper::map(SlabCalculationInput $input,
SlabCalculationResult $result, DateTimeImmutable $generatedAt)` utilise le
même `CalculationNoteDocument` et le même renderer que Poutre. Ses parties
spécifiques sont la bande de calcul de 1 m, les charges surfaciques et les deux
directions d'armatures.

| Source Dalle réelle | Cible `CalculationNoteDocument` |
|---|---|
| `input.configuration` | métadonnées et hypothèses (dalle pleine, unidirectionnelle, système statique) |
| `input.geometry` + `details.internalForces.linearLoads` + flexion finale | `L`, `h`, bande `b = 1 m`, `c_nom`, `d` |
| `input.materials` + `details.flexure.final` | classes et résistances réellement exposées |
| `details.characteristicActions` | poids propre, finitions, cloisons, autres permanentes, `Gk,total`, `Qk` |
| `details.combinations` + `details.internalForces.linearLoads` | charges ELU/ELS surfaciques et charge de bande existante |
| `details.internalForces.internalForces` | moments et efforts tranchants de la bande de 1 m |
| `result.verifications` + détails flexion/ferraillage/ELS | vérifications réellement présentes, sans cisaillement ou poinçonnement fictif |
| propositions principale et secondaire | deux entrées génériques de ferraillage en `mm²/m` |
| `result.status` et `result.summary` | statut final, vérification gouvernante et résumé, sans recalcul |
| `details.warnings` | avertissements sans filtrage |

`details.characteristicActions` a été ajouté à `SlabCalculationDetails` : il
était déjà produit par `SlabCharacteristicActionsCalculator`, mais était perdu
avant le contrat final. Cette projection structurelle expose les charges déjà
calculées sans ajouter de formule. Elle est spécifique au résultat Dalle ; le
modèle PDF commun reste inchangé.

Les résultats Dalle actuels n'exposent pas `fck`, `Ecm` ou `Es` dans leur
contrat final. Le mapper ne les relit donc pas depuis les référentiels. La
flèche reste une vérification `SIMPLIFIED_SPAN_DEPTH` fondée sur `L/d` : aucune
flèche en millimètres n'est générée.

## Téléchargement frontend (PDF-05)

Les routes `POST /api/beam/calculations/pdf` et
`POST /api/slab/calculations/pdf` reçoivent les mêmes *inputs* métier que les
routes de calcul correspondantes. Elles reconstruisent le calcul et sa note
exclusivement côté backend, puis renvoient un flux `application/pdf` avec un
`Content-Disposition: attachment` et respectivement les noms
`note-calcul-poutre.pdf` et `note-calcul-dalle.pdf`.

Angular conserve le snapshot exact ayant produit le dernier résultat affiché
et envoie ce snapshot, jamais le résumé ou des valeurs de résultat reçues du
backend. La modification d'un formulaire invalide déjà le résultat dans
l'interface : elle masque donc également l'action d'export. Le client reçoit
un `Blob`, utilise le nom de fichier fourni lorsqu'il est sûr, puis crée et
révoque une Object URL temporaire. Il ne génère ni n'analyse de contenu PDF,
ne propose ni aperçu, ni impression, ni stockage permanent.
