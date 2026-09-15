# QA-06 — Checklist UX MVP

Cette checklist couvre le parcours `Renseigner → Calculer → Comprendre le résultat` des modules Poutre et Dalle. Elle ne modifie aucune règle de calcul ni donnée normative.

| Scénario | Viewport / interaction | Attendu | Couverture actuelle |
|---|---|---|---|
| Poutre | Desktop : 1280, 1440, 1920 px | Formulaire et résultat côte à côte ; détails sous les deux panneaux | CSS audité ; checklist manuelle à exécuter |
| Poutre | Tablette : 768–1023 px | Panneaux empilés, aucune largeur imposée | CSS audité ; checklist manuelle à exécuter |
| Poutre | Mobile : 320–767 px | Une colonne, champs et cartes lisibles, aucun scroll horizontal global | CSS audité ; checklist manuelle à exécuter |
| Dalle | Desktop : 1280, 1440, 1920 px | Même structure ; valeurs de dalle formatées | CSS audité ; checklist manuelle à exécuter |
| Dalle | Tablette : 768–1023 px | Formulaire puis résultat, sans chevauchement | CSS audité ; checklist manuelle à exécuter |
| Dalle | Mobile : 320–767 px | Hypothèses, champs et résultat en une colonne | CSS audité ; checklist manuelle à exécuter |
| Clavier | Tab, Entrée, Espace | Focus visible ; sélecteurs et accordéons activables au clavier | Test composant + audit du HTML |
| Chargement | Poutre et Dalle | CTA désactivé, libellé « Calcul en cours… », résultat précédent supprimé | Tests d’intégration Angular |
| Erreurs API | 400 / 422 | Message métier de l’API affiché | Tests d’intégration Angular |
| Erreurs techniques | 500 / réseau | Message générique, aucune trace technique | Tests d’intégration Angular |
| Validation | Champs invalides | Erreur près du champ, label et unité visibles | Tests de formulaires Angular |
| Résultat | `NOT_COMPLIANT` | Texte, icône, taux et vérification gouvernante explicites | Tests de composants |
| Résultat | `CALCULATION_METHOD_NOT_SUPPORTED` | Absence de conclusion « Conforme » et explication explicite | Tests de composants |
| Avertissements | Détails Poutre / Dalle | Bloc identifié « Informations » distinct d’une erreur | Tests composants / rendu Dalle |

## Checklist manuelle de clôture

Il n’y a pas de framework E2E dans le dépôt. Exécuter dans un navigateur les six lignes de viewport ci-dessus et confirmer que `document.documentElement.scrollWidth === window.innerWidth` hors débordement intentionnel du menu horizontal. Vérifier aussi le parcours clavier, l’état focus, les chargements lents, une erreur réseau et les retours 400/422/500 pour les deux modules.

Le menu de l’en-tête devient vertical sur mobile ; sa navigation garde un défilement **interne** si nécessaire, sans élargir la page. Le calculateur utilise une grille à une colonne sous `lg` et ses zones de grille acceptent de rétrécir (`min-w-0`).
