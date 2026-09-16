# PDF-01 — Modèle commun de note de calcul

## Objectif

`CalculationNoteDocument` est le contrat immuable qui portera les données d’une future note de calcul. Il est une projection des résultats du moteur : il ne recalcule aucune valeur, ne décide aucun statut, ne contient ni HTML ni dépendance à une bibliothèque PDF.

```text
CalculationResult
       ↓
mapper spécifique au module (PDF-03 / PDF-04)
       ↓
CalculationNoteDocument
       ↓
renderer PDF (PDF-02)
```

## Structure

```text
CalculationNoteDocument
├── metadata: CalculationNoteMetadata
├── assumptions: CalculationNoteSection
├── geometry: CalculationNoteSection
├── materials: CalculationNoteSection
├── loads: CalculationNoteSection
├── combinations: ?CalculationNoteSection
├── internalForces: ?CalculationNoteSection
├── verifications: CalculationNoteVerification[]
├── reinforcement: CalculationNoteReinforcement[]
├── finalStatus: CalculationNoteFinalStatus
├── warnings: string[]
└── limitations: string[]
```

`CalculationNoteMetadata` porte le titre, `ElementType` existant (`BEAM` ou `SLAB`), une `DateTimeImmutable`, le profil normatif et la version de schéma. `CalculationNoteValue` conserve séparément la valeur brute, l’unité, un éventuel affichage et sa source. `CalculationNoteSection` ordonne des valeurs, sous-sections et étapes (`formula`, `substitution`, `result`) déjà disponibles.

Les vérifications réutilisent `BeamVerificationStatus`, statut commun déjà employé par les résultats Poutre et Dalle. Une utilisation, une valeur gouvernante ou une limite peuvent être nulles : elles ne deviennent jamais artificiellement zéro. Les renforts restent structurés (`count`, `diameter`, `spacing`, aires) même lorsqu’une désignation prête à afficher est présente.

## Responsabilités et limites

- Les sections non applicables sont `null`, jamais représentées par une résistance ou un effort nul fictif.
- `warnings` et `limitations` restent distincts du statut final.
- Les mappers réels Beam et Slab ne font pas partie de PDF-01 et seront traités par PDF-03 / PDF-04.
- Aucun PDF, endpoint, persistance, bouton frontend ou dépendance de rendu n’est ajouté ici.
