# QA-05 — Matrice des unités

La conversion UI → backend est réalisée une seule fois dans Angular : Poutre `L` en m et `b/h` en cm, Dalle `L` en m et `h` en cm. Les payloads transmis au backend sont en mm ; les factories backend ne reconvertissent pas et Poutre exige explicitement `geometry.unit = 'mm'`.

| Grandeur | Module | UI / entrée API | Interne | Sortie | Conversion / note |
|---|---|---|---|---|---|
| Portée `L` | Poutre, Dalle | m / mm | mm, puis m pour statique | m dans les substitutions, mm dans détails | `6,5 m → 6500 mm → 6,5 m` |
| Largeur `b`, hauteur `h` | Poutre | cm / mm | mm | mm | `30 cm → 300 mm`, `60 cm → 600 mm` |
| Épaisseur `h` | Dalle | cm / mm | mm | mm | `20 cm → 200 mm` |
| Bande | Dalle | — | 1000 mm puis 1 m | mm / m | hypothèse fixe V1 |
| Poids volumique `γRC` | commun | — | kN/m³ | kN/m³ | 25 kN/m³ |
| Charges | Poutre | kN/m | kN/m | kN/m | aucune conversion N/mm dans la statique |
| Charges surfaciques `q` | Dalle | kN/m² | kN/m² | kN/m² | poids propre inclus |
| Charge de bande `w` | Dalle | — | kN/m | kN/m | `w=q×1 m`, jamais `q×1000` |
| Moment `MEd` | commun | kN·m | kN·m puis N·mm pour section | kN·m | `1 kN·m = 1 000 000 N·mm` |
| Effort `VEd` | commun | kN | kN / N selon résistance | kN | `1 kN = 1000 N` |
| Résistances, modules, contraintes | commun | MPa | MPa = N/mm² | MPa | aucun facteur supplémentaire |
| Armatures Poutre | Poutre | diamètre mm | aire mm² | mm² | `Aφ=πφ²/4` |
| Armatures Dalle | Dalle | diamètre/espacement mm | mm²/m | mm²/m | `As/m=Aφ×1000/s` |
| Enrobage `cnom`, hauteur utile `d` | commun | — | mm | mm | toutes les longueurs de section en mm |
| Fissuration `wk`, `wk,max` | commun | — | mm | mm | comparaison dans une unité homogène |
| `μ`, `ξ`, `ρ`, utilisation, `L/d` | commun | — | sans dimension | sans dimension | longueurs homogènes avant division |

Les tests QA-05 vérifient les convertisseurs centralisés, les valeurs de référence Poutre et Dalle sensibles aux facteurs 10/100/1000/1 000 000, ainsi que le refus d'une unité de géométrie backend ambiguë. Aucune conversion inverse N·mm → kN·m n'est actuellement implémentée dans le moteur ; elle n'est donc pas inventée pour les tests.
