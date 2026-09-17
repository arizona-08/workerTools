# QA-04 — Cas limites

Les bornes géométriques d'entrée sont des invariants techniques : longueurs et dimensions doivent être finies et strictement positives. Les charges optionnelles sont finies et positives ou nulles. Il n'existe actuellement aucun maximum métier explicite pour les portées, dimensions ou charges.

| Module | Frontière | Valeur | Comportement vérifié | Origine |
|---|---|---:|---|---|
| Poutre | portée | 0 | HTTP 422, `INVALID_EFFECTIVE_SPAN` | DTO / invariant |
| Poutre | largeur | -1 mm | HTTP 422, `INVALID_WIDTH` | DTO / invariant |
| Poutre | hauteur | 0 | HTTP 422, `INVALID_HEIGHT` | DTO / invariant |
| Dalle | portée | 0 puis -1 mm | HTTP 422, `INVALID_EFFECTIVE_SPAN` | DTO / invariant |
| Dalle | épaisseur | 0 mm | HTTP 422, `INVALID_THICKNESS` | DTO / invariant |
| Poutre | charges ajoutées | `Gadd=0`, `Qk=0`, poids propre actif | calcul valide, `Gk=4,5 kN/m` | sémantique métier |
| Dalle | charges ajoutées | toutes à 0 | calcul valide, poids propre `Gk=5 kN/m²` | sémantique métier |
| Poutre | aucune action | poids propre désactivé, `Gadd=Qk=0` | HTTP 422, `INVALID_REQUIRED_REINFORCEMENT` | limite actuelle de chaîne |
| Poutre | largeur techniquement très faible | 1 mm | HTTP 422, `INVALID_NEUTRAL_AXIS_RADICAND` | garde-fou numérique |
| Dalle | épaisseur techniquement très faible | 1 mm | HTTP 422, `NON_POSITIVE_EFFECTIVE_DEPTH` | invariant de profondeur utile |
| Commun | valeur non finie | `INF` / `NAN` via factory publique | exception domaine explicite | sécurité numérique |
| Dalle | charge élevée mais finie | `Qk=20 kN/m²` | résultat `NOT_COMPLIANT`, sorties majeures finies | stabilité numérique |
| Poutre | `VEd / VRd,max` | limite - 0,001 / limite / limite + 0,001 kN | OK / OK / dépassée ; `VEd=VRd,max` est inclusif | règle existante de cisaillement |

Les contrôles de fissuration, flèche, armatures exactes et catalogues discrets restent déjà couverts à leur niveau de calcul par les tests unitaires existants. QA-04 ne crée pas de matrice de conversion d'unités : ce travail est réservé à QA-05. Aucune limite Eurocode nouvelle ou borne maximale arbitraire n'est ajoutée.
