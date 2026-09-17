# BEAM-RESULT-01 — Agrégation des vérifications

```text
EPIC 5/6  → flexure
EPIC 7    → shear
SLS-01    → stress
SLS-02    → crack
SLS-03    → deflection
                 ↓
        ulsStatus / slsStatus / overallStatus
```

L'agrégateur interprète exclusivement les statuts et valeurs déjà présentes.
Il ne recalcule aucune sollicitation, résistance, armature, fissure, contrainte
ou rapport portée/hauteur. Un échec est prioritaire; une vérification manquante
ou une méthode non supportée empêche toute conclusion `COMPLIANT`; un contrôle
explicitement `NOT_APPLICABLE` n'empêche pas une conclusion.

## Vérification gouvernante — BEAM-RESULT-02

`governingVerification` est le composant avec la plus forte `utilization`
finie, parmi les seuls statuts `COMPLIANT` et `NOT_COMPLIANT`. Les composants
`NOT_CHECKED`, `NOT_APPLICABLE`, `CALCULATION_METHOD_NOT_SUPPORTED` et ceux
sans ratio sont exclus avec leur raison. À égalité exacte, l'ordre stable est
`FLEXURE`, `SHEAR`, `STRESS`, `CRACK`, `DEFLECTION`; il est applicatif, sans
signification normative. Le ratio reste brut et sans dimension ; l'affichage
en pourcentage appartient au futur résumé.

## Résumé compact — BEAM-RESULT-03

`BeamResultSummary` copie `utilization` et le type gouvernant depuis
BEAM-RESULT-02, ainsi que le statut global depuis BEAM-RESULT-01. `MEd`, `d`,
`As_req` et le ferraillage longitudinal viennent respectivement du moment ELU,
du candidat réellement évalué et de ses résultats associés. Les valeurs restent
brutes : kN·m, mm, mm² et ratio sans dimension. L'origine du ferraillage est
`PROPOSED` en DESIGN et `PROVIDED` en VERIFICATION. Aucun détail de calcul ou
formatage UI n'est inclus.

## Détail structuré — BEAM-RESULT-04

`BeamCalculationDetails` organise les sections `assumptions`, `combinations`,
`internalForces`, `flexure`, `reinforcement`, `shear` et `serviceability` dans
un ordre stable. Les statuts viennent de RESULT-01 et le gouvernant de
RESULT-02. Le builder copie les DTO déjà calculés, conserve les warnings et
garantit la cohérence avec RESULT-03 pour `MEd`, `d`, `As_req`, ferraillage et
statut. Il n'a aucune dépendance à un calculateur normatif.

Chaque section est une donnée structurée et brute, destinée aux accordéons du
frontend : `assumptions` vient de l'entrée/configuration Poutre;
`combinations` et `internalForces` des EPIC actions et efforts;
`flexure` de BEAM-FLEX; `reinforcement` de BEAM-REBAR; `shear` de BEAM-SHEAR;
et `serviceability.stress`, `.crack`, `.deflection` respectivement de SLS-01,
SLS-02 et SLS-03. Les valeurs non applicables restent `null` ou portent leur
statut explicite : le builder ne les remplace jamais par zéro et ne crée jamais
de fausse flèche en millimètres. Les données doivent inclure les unités déjà
portées par leurs résultats source; aucun arrondi ni libellé d'interface n'est
produit ici.
