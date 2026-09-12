# Recalcul avec le diamètre réel d'un candidat — BEAM-REBAR-04

## Processus DESIGN

Le diamètre `Ø16` fourni par
`BeamFlexuralDetailingAssumptions::mvp()` est une hypothèse de départ pour
établir le premier `d`, puis le premier `As_target`. Il ne représente pas le
diamètre finalement retenu.

```text
Ø16 supposé (CONFIG)
        ↓
pré-dimensionnement de flexion et As_target initial
        ↓
génération des candidats (BEAM-REBAR-02)
        ↓
filtrage géométrique d'un lit (BEAM-REBAR-03)
        ↓
diamètre fixe du candidat (CANDIDATE)
        ↓
d → μEd → ξ/x → z → As_req → As_min → domaine simplement armé → As_target
        ↓
As_prov ≥ As_target recalculé
```

`BeamReinforcementCandidateRecalculator` orchestre exclusivement les
calculateurs existants BEAM-FLEX-01 à 08 et BEAM-REBAR-01. Le
`CoverCalculationResult` déjà produit par EC2-05 est repris tel quel : aucune
règle d'enrobage ni sélection de l'armature extérieure n'est reconstituée.

## Une passe, pas une optimisation

Chaque candidat reçu de BEAM-REBAR-03 conserve son nombre de barres, son
diamètre et son `As_prov`. Il est recalculé exactement une fois, puis rangé dans
`validCandidates` ou `rejectedCandidates`. Le moteur ne modifie pas un
candidat, ne génère pas de remplacement et ne boucle pas jusqu'à convergence.

Un domaine simplement armé invalide donne le statut
`INVALID_SINGLY_REINFORCED_DOMAIN`, sans aire cible. Sinon le statut est
`VALID_AFTER_RECALCULATION` ou `INSUFFICIENT_AFTER_RECALCULATION` selon la
comparaison `As_prov ≥ As_target`.

## Limites

Ce recalcul n'est pas une vérification de résistance finale : il ne produit pas
`MRd`, ne conclut pas à la conformité globale, ne traite ni plusieurs lits ni
armatures comprimées, et ne sélectionne pas un candidat final. La convention
MVP d'enrobage autour de l'étrier, documentée dans BEAM-FLEX-01, reste celle
utilisée ici.
