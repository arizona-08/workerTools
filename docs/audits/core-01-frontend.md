# CORE-01 — Audit frontend

## Éléments conservés

- Angular 21 standalone, Angular Router, Tailwind CSS et Lucide Angular sont déjà en place.
- Les routes applicatives sont regroupées sous `/app` avec `MainAppLayout` et une route dédiée au calculateur (`/app/calculator`).
- Le projet est configuré pour le rendu côté serveur et les tests unitaires Vitest.

## Éléments complétés par l'EPIC 1

- Le sélecteur de modules existant devient une liste de boutons accessible et extensible.
- La page Calculateur reçoit le squelette responsive formulaire, résultat et détails.
- Les formulaires Poutre et Dalle sont isolés dans des composants distincts.

## À ne pas traiter avant les prochaines EPIC

- Les champs métier et leur validation relèvent de l'EPIC 3.
- L'appel API, le moteur de calcul et les résultats réels ne sont pas encore présents.
