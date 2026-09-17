# WorkerTools — Instructions pour les agents

## 1. Objet du projet

WorkerTools est une application web de calcul de structures destinée à proposer des outils simples, fiables et lisibles pour le dimensionnement et la vérification d'éléments en béton armé.

Le périmètre V1 repose sur deux modules :

- **Calcul de poutres en béton armé**
- **Calcul de dalles en béton armé**

Les calculs doivent être basés sur **l'Eurocode 2** et, lorsque nécessaire, sur les normes Eurocodes complémentaires applicables aux actions, combinaisons et hypothèses de calcul.

L'objectif produit n'est pas de créer une interface de calcul scientifique complexe. L'application doit permettre à un ingénieur ou technicien de :

1. choisir un module ;
2. saisir uniquement les données nécessaires à son cas ;
3. lancer le calcul ;
4. identifier immédiatement si l'élément est conforme ;
5. consulter les valeurs principales ;
6. accéder au détail du calcul et aux vérifications lorsqu'il le souhaite.

Le parcours principal doit rester :

**Choisir → Renseigner → Calculer → Comprendre le résultat.**

---

## 2. Stack technique actuelle

Respecter la stack et l'architecture déjà présentes dans le repository.

### Frontend

- Angular 21
- TypeScript
- Angular Router
- Angular Forms
- Tailwind CSS 4
- Lucide Angular
- RxJS
- Vitest
- SSR Angular déjà configuré

### Backend

- PHP 8.3+
- Laravel 13
- Laravel Sanctum
- Pest
- Laravel Pint

### Infrastructure

- frontend et backend séparés ;
- Docker pour l'environnement de développement ;
- Nginx présent dans le repository.

Ne pas remplacer ces technologies sans demande explicite.

---

## 3. Règles générales de travail pour l'agent

Avant toute implémentation :

1. **Analyser le code existant.**
2. Identifier les composants, services, modèles, routes, styles et conventions déjà présents.
3. Réutiliser l'existant lorsqu'il répond correctement au besoin.
4. Ne pas réécrire une feature fonctionnelle uniquement pour imposer une autre architecture.
5. Limiter les modifications au périmètre demandé.
6. Ne pas développer les prochaines étapes du backlog par anticipation.
7. Ne pas modifier une partie sans rapport avec la tâche sauf si cela est strictement nécessaire.

Chaque prompt de feature doit être considéré comme une **étape indépendante du backlog**.

Une tâche doit être terminée, testable et propre avant de commencer implicitement une autre feature.

---

## 4. Principes d'architecture

Le projet doit privilégier :

- simplicité ;
- séparation des responsabilités ;
- code explicite ;
- faible couplage ;
- composants et classes de taille raisonnable ;
- testabilité ;
- ajout facile de futurs modules de calcul.

Éviter :

- les abstractions prématurées ;
- les "god components" ;
- les contrôleurs contenant toute la logique métier ;
- les fichiers contenant des centaines de lignes de formules sans structure ;
- la duplication importante ;
- les dépendances supplémentaires sans nécessité réelle ;
- les helpers génériques créés uniquement pour éviter quelques lignes de code.

Préférer une abstraction lorsqu'un besoin concret est partagé par plusieurs fonctionnalités.

---

## 5. Séparation frontend / backend

### Frontend

Le frontend est responsable de :

- l'expérience utilisateur ;
- la sélection du module ;
- les formulaires ;
- la validation de premier niveau ;
- les unités affichées ;
- les états `loading`, `success`, `error`, `empty` ;
- l'envoi des données à l'API ;
- la présentation des résultats ;
- la visualisation de la conformité ;
- l'affichage du détail des calculs.

Le frontend **ne doit pas être la source de vérité des calculs normatifs**.

Ne pas dupliquer les formules Eurocode dans Angular uniquement pour obtenir un résultat côté client.

### Backend

Le backend est responsable de :

- la validation métier ;
- les règles normatives ;
- les propriétés des matériaux ;
- les coefficients ;
- les conversions nécessaires au moteur ;
- les sollicitations couvertes par le périmètre ;
- les calculs ELU ;
- les calculs ELS ;
- le dimensionnement ;
- les vérifications ;
- la conformité ;
- la génération d'un résultat structuré et explicable.

Le contrôleur HTTP doit rester léger :

`Request → validation → service/use case → moteur de calcul → response`

La logique mathématique ne doit pas être placée directement dans les controllers.

---

## 6. Architecture du domaine de calcul

La structure exacte peut évoluer selon l'existant, mais la séparation conceptuelle suivante doit être conservée :

```text
StructuralCalculation
├── Materials
├── Eurocode
│   ├── Concrete
│   ├── Steel
│   ├── Beams
│   ├── Slabs
│   ├── Flexure
│   ├── Shear
│   └── Serviceability
├── Units
├── Inputs
├── Results
└── Verifications
```

Ne pas créer tous ces dossiers à l'avance s'ils ne sont pas nécessaires à la tâche courante.

Le but est de conserver cette séparation conceptuelle, pas d'imposer artificiellement une arborescence.

---

## 7. Fiabilité des calculs — règle critique

WorkerTools traite des calculs de structure.

Une implémentation mathématique plausible n'est pas suffisante.

### Ne jamais :

- inventer une formule Eurocode ;
- extrapoler une règle normative incertaine ;
- utiliser une formule trouvée dans une source non fiable sans vérification ;
- modifier silencieusement une hypothèse de calcul ;
- masquer une limitation du moteur ;
- présenter un résultat comme conforme si une vérification nécessaire n'a pas été effectuée.

### Lorsqu'une tâche fournit les formules et règles

Les implémenter exactement en respectant :

- les unités ;
- les coefficients ;
- les domaines de validité ;
- les bornes ;
- les hypothèses ;
- les arrondis seulement au niveau de présentation lorsque possible.

### Lorsqu'une règle métier ou normative manque

Ne pas l'inventer.

Signaler clairement dans le compte rendu :

- l'information manquante ;
- l'endroit concerné ;
- la décision qui doit être prise avant une implémentation fiable.

---

## 8. Versionnement des règles normatives

Ne pas disperser dans le code des constantes telles que :

- coefficients partiels ;
- propriétés béton ;
- propriétés acier ;
- facteurs nationaux ;
- limites réglementaires.

Centraliser ces valeurs dans des classes/configurations métier cohérentes.

L'architecture doit permettre, à terme, de distinguer :

- une version d'Eurocode ;
- une Annexe Nationale ;
- éventuellement différents ensembles de paramètres.

Ne pas implémenter plusieurs versions normatives tant que cela n'est pas demandé.

---

## 9. Unités

Les unités constituent une partie du domaine métier.

Chaque valeur doit avoir une unité clairement connue.

Exemples :

- longueurs : `mm`, `cm`, `m`
- efforts : `kN`
- moments : `kN·m`
- contraintes/résistances : `MPa`
- armatures : `mm²`, `cm²`, `mm²/m`
- charges linéaires : `kN/m`
- charges surfaciques : `kN/m²`

Éviter les nombres dont l'unité est implicite et inconnue.

Le moteur doit utiliser une convention interne cohérente et les conversions doivent être explicites.

Ne pas arrondir les valeurs intermédiaires uniquement pour reproduire l'affichage UI.

---

## Documentation des variables, constantes et concepts métier

La documentation des variables et constantes métier fait partie intégrante de l’implémentation.

Créer et maintenir le fichier :

`docs/domain/variables-and-constants.md`

Ce document doit servir de référentiel commun pour comprendre les données manipulées par WorkerTools, notamment les variables de calcul, constantes normatives, paramètres nationaux, enums métier et hypothèses de périmètre.

À chaque feature ajoutant, modifiant ou utilisant un concept métier important, mettre à jour cette documentation.

Pour chaque élément pertinent, documenter au minimum :

- nom utilisé dans le code ;
- symbole métier ou mathématique s’il est différent ;
- type ;
- signification ;
- unité interne ;
- unité affichée si elle est différente ;
- origine de la valeur ;
- valeur ou valeurs possibles lorsqu’elles sont finies ;
- contexte d’utilisation ;
- dépendances éventuelles ;
- référence normative lorsqu’elle existe ;
- remarques ou limitations importantes.

Utiliser les catégories d’origine suivantes lorsque pertinent :

- `USER` : donnée saisie ou choisie par l’utilisateur ;
- `DERIVED` : valeur calculée à partir d’autres données ;
- `PROFILE` : valeur provenant du profil normatif / Annexe Nationale ;
- `FIXED_SCOPE` : hypothèse volontairement figée dans le périmètre supporté ;
- `CONFIG` : valeur provenant d’une configuration technique ou métier.

Exemple :

`fck`

- Symbole : `fck`
- Signification : résistance caractéristique du béton en compression à 28 jours
- Type : nombre
- Unité interne : MPa
- Origine : `DERIVED`
- Dépend de : classe de béton
- Utilisé pour : résistance de calcul du béton, cisaillement, armatures minimales, vérifications ELS
- Référence : Eurocode 2
- Remarque : propriété intrinsèque du béton ; ne dépend pas du profil national

Exemple :

`γc`

- Symbole : `γc`
- Signification : coefficient partiel de sécurité du béton
- Type : nombre
- Origine : `PROFILE`
- Utilisé pour : calcul de `fcd`
- Référence : profil normatif français
- Remarque : ne doit pas être stocké dans `ConcreteClass`

Ne pas limiter cette documentation aux seules nouvelles variables de la tâche en cours.

Lors de la première mise en place du document, inventorier également les variables, constantes, enums et paramètres métier déjà présents dans le projet lorsque leur rôle peut être établi avec certitude.

Ne pas inventer une signification, une unité ou une origine pour compléter le document.

Si le rôle d’un élément existant est ambigu, le signaler explicitement comme tel.

Les constantes normatives importantes doivent avoir une source identifiable dans le code ou dans la documentation.

La documentation doit rester synchronisée avec le code : une modification de sens, d’unité, de valeur, d’origine ou de domaine d’utilisation doit entraîner la mise à jour du document.

---

## 10. Types de calcul

Lorsque cela est prévu dans la feature, distinguer clairement :

### Dimensionnement

L'utilisateur décrit l'élément, les matériaux et les actions.

WorkerTools détermine notamment les besoins de dimensionnement et peut proposer un ferraillage compatible avec le périmètre couvert.

### Vérification

L'utilisateur décrit également le ferraillage existant.

WorkerTools détermine si l'élément satisfait les vérifications couvertes.

Ne pas mélanger silencieusement les deux workflows.

---

# UX / UI

## 11. Direction visuelle générale

L'interface doit avoir un aspect :

- professionnel ;
- moderne ;
- technique sans être austère ;
- minimaliste ;
- clair ;
- peu chargé visuellement ;
- cohérent sur toute l'application.

Référence visuelle générale fournie dans les prompts : application SaaS métier avec navigation latérale, barre supérieure, formulaires regroupés en cartes et résultats structurés.

La référence visuelle exacte fournie dans un prompt de feature est prioritaire sur les indications génériques de ce fichier.

---

## 12. Palette et identité visuelle

La couleur principale de WorkerTools est un **violet vif**, proche de :

`#6C12F3`

L'utiliser principalement pour :

- action principale ;
- module actif ;
- focus ;
- état sélectionné ;
- accents ;
- graphiques de conformité ;
- petites icônes fonctionnelles.

Le reste de l'interface doit être majoritairement :

- blanc ;
- gris très clair ;
- gris neutre ;
- texte presque noir.

Éviter de saturer l'écran de violet.

Les couleurs vert / orange / rouge sont réservées aux états métier :

- conforme / succès ;
- avertissement ;
- non conforme / erreur.

La couleur ne doit jamais être le seul moyen de communiquer un état.

---

## 13. Structure générale de l'application

Sur desktop, privilégier :

```text
┌───────────────┬─────────────────────────────────────┐
│               │ Barre supérieure                    │
│ Navigation    ├─────────────────────────────────────┤
│ latérale      │                                     │
│               │ Page / contenu métier               │
│               │                                     │
└───────────────┴─────────────────────────────────────┘
```

La navigation latérale doit permettre d'accéder facilement aux modules de calcul et futures fonctionnalités.

Le contenu principal doit disposer de suffisamment d'espace blanc.

Ne pas enfermer chaque élément dans une carte si une simple séparation visuelle suffit.

---

## 14. Pages de calcul

Une page de calcul doit hiérarchiser l'information dans cet ordre :

1. titre et description courte ;
2. action principale `Calculer` ;
3. saisie des paramètres ;
4. résultat synthétique ;
5. indicateurs principaux ;
6. détail des vérifications ;
7. détail des formules si disponible.

Sur desktop, lorsqu'il y a suffisamment d'espace :

- formulaire à gauche ;
- synthèse des résultats à droite ;
- détails de calcul en pleine largeur en dessous.

Sur écrans plus étroits, le layout doit naturellement passer sur une colonne.

---

## 15. Formulaires

Les formulaires constituent le cœur de l'application.

Ils doivent être :

- faciles à scanner ;
- divisés en sections métier ;
- suffisamment compacts ;
- jamais intimidants malgré le nombre de paramètres.

Exemples de sections :

- Géométrie
- Matériaux
- Actions & charges
- Ferraillage
- Durabilité
- Coefficients
- Hypothèses

Utiliser des sections repliables lorsqu'elles améliorent réellement la lisibilité.

Les paramètres rarement modifiés ou avancés peuvent être repliés par défaut.

Les données essentielles doivent rester immédiatement visibles.

---

## 16. Champs

Chaque champ technique doit disposer de :

- label lisible ;
- symbole lorsque pertinent, par exemple `Portée (L)` ;
- unité visible ;
- valeur par défaut uniquement si elle a un sens métier ;
- validation explicite ;
- message d'erreur proche du champ.

Exemple :

```text
Portée (L)
[ 6.50                         ] m
```

Éviter de demander à l'utilisateur une donnée que l'application peut calculer de manière fiable à partir des autres paramètres.

Exemple : préférer une sélection de classe de béton à la saisie manuelle de toutes ses propriétés.

---

## 17. Progressive disclosure

Ne jamais afficher simultanément tous les paramètres de tous les cas possibles.

Les formulaires doivent s'adapter à la configuration choisie.

Exemple :

`Poutre → section rectangulaire`

n'affiche pas les paramètres spécifiques à une section en T.

De même :

`Dimensionnement`

n'affiche pas nécessairement tous les champs exigés par :

`Vérification d'un ferraillage existant`.

Le principe UX est :

**ne demander que ce qui est utile dans le contexte courant.**

---

## 18. Action principale

Sur une page de calcul, il doit exister une action principale évidente :

**Calculer la structure**

ou un libellé plus spécifique lorsque nécessaire.

Ne pas mettre plusieurs CTA primaires concurrents.

Pendant le calcul :

- désactiver les doubles soumissions ;
- afficher un état de chargement discret ;
- conserver les valeurs saisies.

---

## 19. Présentation des résultats

La première question à laquelle le résultat doit répondre est :

**« Est-ce conforme ? »**

Puis :

**« Quelle vérification dimensionne l'élément ? »**

Puis :

**« Comment ce résultat a-t-il été obtenu ? »**

Le résumé peut inclure :

- taux d'utilisation ;
- conformité globale ;
- moment utile/résistant ;
- hauteur utile ;
- section d'acier ;
- ferraillage proposé ;
- vérification gouvernante.

Les résultats importants peuvent être présentés en cartes courtes.

---

## 20. Taux d'utilisation

Lorsqu'un taux d'utilisation est disponible :

- le rendre immédiatement lisible ;
- afficher sa valeur ;
- indiquer son interprétation ;
- ne pas se contenter d'un graphique.

Exemple :

`75 % — Conforme`

La définition exacte du taux dépend de la vérification et doit provenir du moteur métier.

Ne pas inventer un taux générique uniquement pour remplir l'interface.

---

## 21. Détail des calculs

Le détail du calcul doit être accessible mais ne doit pas dominer la page.

Utiliser une structure progressive, par exemple :

```text
1. Hypothèses et paramètres
2. Sollicitations
3. Flexion
4. Armatures longitudinales
5. Cisaillement
6. ELS
7. Synthèse des vérifications
```

Chaque étape peut être un accordéon.

Lorsqu'une formule est affichée, montrer idéalement :

1. le nom du calcul ;
2. la formule symbolique ;
3. les valeurs injectées ;
4. le résultat ;
5. son unité ;
6. la conclusion ou la limite associée si pertinente.

Le détail de calcul doit être construit à partir des données renvoyées par le backend lorsque cela est possible, afin d'éviter de recalculer les valeurs dans l'UI.

---

## 22. États de conformité

Prévoir des états métier explicites, par exemple :

- `conforme`
- `non conforme`
- `avertissement`
- `non vérifié`
- `non applicable`

Ne pas confondre :

**« aucune erreur technique »**

avec :

**« structure conforme »**.

Si une vérification indispensable n'a pas été effectuée, la structure ne doit pas être présentée comme pleinement conforme.

---

## 23. Responsive

L'application est principalement destinée à un usage professionnel sur ordinateur, mais elle doit rester exploitable sur tablette et petits écrans.

Desktop :
- navigation latérale ;
- formulaire + résultat en colonnes si pertinent.

Mobile/tablette étroite :
- navigation adaptée ;
- contenu sur une colonne ;
- champs suffisamment grands ;
- aucune table ou formule ne doit provoquer un layout cassé.

Ne pas sacrifier la lisibilité des calculs pour maintenir artificiellement le layout desktop.

---

## 24. Accessibilité et ergonomie

Respecter au minimum :

- navigation clavier ;
- labels associés aux inputs ;
- focus visible ;
- contraste suffisant ;
- boutons avec libellé ou alternative accessible ;
- messages d'erreur compréhensibles ;
- zones cliquables suffisamment grandes.

Les icônes servent à renforcer un libellé, pas à remplacer systématiquement le texte.

---

# FRONTEND ANGULAR

## 25. Conventions Angular

Respecter l'architecture Angular déjà utilisée dans le projet.

Préférer :

- composants à responsabilité claire ;
- Reactive Forms pour les formulaires métier complexes ;
- types TypeScript explicites ;
- services pour les communications API ;
- composants de présentation lorsque cela simplifie réellement la page ;
- logique métier UI séparée du template.

Éviter :

- gros templates avec logique complexe ;
- `any` ;
- duplication des types ;
- subscriptions manuelles non nettoyées ;
- calculs Eurocode dans les composants.

Utiliser les fonctionnalités modernes d'Angular lorsqu'elles améliorent réellement la clarté et sont cohérentes avec le code existant.

---

## 26. Styling

Utiliser **Tailwind CSS** en priorité, puisqu'il est déjà présent.

Conserver :

- espacements réguliers ;
- arrondis modérés ;
- bordures discrètes ;
- ombres très légères si nécessaires ;
- forte hiérarchie typographique ;
- densité adaptée à un logiciel professionnel.

Ne pas introduire un framework UI complet sans demande explicite.

Utiliser les icônes **Lucide** déjà disponibles avant d'ajouter une nouvelle librairie d'icônes.

---

# BACKEND LARAVEL

## 27. Conventions Laravel

Respecter les conventions Laravel.

Préférer lorsque pertinent :

- Form Requests pour validation HTTP ;
- DTO / objets d'entrée métier pour éviter de propager directement les arrays HTTP ;
- services/use cases ;
- classes métier dédiées au calcul ;
- Resources ou réponses structurées pour l'API ;
- enums/value objects lorsque leur valeur est concrète.

Le contrôleur orchestre ; il ne réalise pas lui-même le calcul.

---

## 28. Erreurs métier

Distinguer :

- erreur de validation HTTP ;
- configuration non supportée ;
- impossibilité mathématique ;
- donnée hors domaine de validité ;
- calcul effectué mais élément non conforme ;
- erreur serveur inattendue.

Une structure **non conforme n'est pas une erreur HTTP**.

Elle doit retourner un résultat de calcul normal avec un statut métier approprié.

---

# API ET MODÈLES

## 29. Contrats d'API

Les payloads doivent être explicites et stables.

Éviter les réponses composées d'une collection de nombres sans signification.

Préférer :

```json
{
  "status": "non_compliant",
  "summary": {},
  "verifications": [],
  "calculationDetails": []
}
```

Les noms exacts seront définis selon la feature.

Les résultats doivent permettre au frontend d'afficher la page sans reproduire la logique Eurocode.

---

# TESTS ET QUALITÉ

## 30. Tests des calculs

Toute nouvelle formule ou vérification structurelle importante doit être accompagnée de tests.

Tester au minimum :

- cas nominal ;
- limite proche de la conformité ;
- cas non conforme ;
- valeurs invalides ;
- cas limite pertinent ;
- unités/conversions lorsqu'elles interviennent.

Lorsqu'un cas de référence Eurocode ou un calcul manuel validé est fourni, créer un test de non-régression basé sur celui-ci.

Les tolérances numériques doivent être explicites.

Ne pas écrire un test qui reproduit aveuglément la même formule que l'implémentation sans référence indépendante.

---

## 31. Tests frontend

Tester prioritairement :

- validation des formulaires ;
- champs conditionnels ;
- transformation du formulaire en payload ;
- gestion des erreurs API ;
- affichage des états métier importants.

Éviter des tests fragiles centrés sur des détails CSS sans valeur fonctionnelle.

---

## 32. Qualité avant de terminer une tâche

Avant de considérer une feature terminée :

### Frontend

Exécuter lorsque pertinent :

```bash
npm test
npm run build
```

### Backend

Exécuter lorsque pertinent :

```bash
php artisan test
./vendor/bin/pint --test
```

Si une commande ne peut pas être exécutée, le signaler clairement au lieu d'affirmer que les tests passent.

Corriger les erreurs introduites par la tâche.

Ne pas refactorer tout le repository pour corriger une erreur préexistante sans rapport.

---

# WORKFLOW AGENT

## 33. Méthode attendue pour chaque prompt

Pour chaque tâche :

### 1 — Analyse

- lire `instructions.md` ;
- inspecter le code concerné ;
- identifier ce qui existe déjà ;
- identifier les dépendances de la feature.

### 2 — Plan

Présenter ou suivre mentalement un plan court et ciblé.

Ne pas créer un plan de refonte globale lorsqu'une modification locale suffit.

### 3 — Implémentation

Développer uniquement le périmètre demandé.

### 4 — Vérification

- lancer les tests pertinents ;
- lancer le build/lint/formatage pertinent ;
- vérifier les cas importants.

### 5 — Compte rendu

À la fin, indiquer brièvement :

- ce qui a été ajouté/modifié ;
- les fichiers importants ;
- les tests exécutés ;
- les éventuelles limites ou décisions restant à prendre.

---

## 34. Critères d'acceptation

Lorsqu'un prompt fournit des critères d'acceptation :

- les traiter comme contractuels ;
- vérifier chaque critère ;
- ne pas considérer la tâche terminée si un critère n'est pas satisfait ;
- signaler explicitement les critères impossibles à remplir et pourquoi.

---

## 35. Scope control

Ne pas ajouter automatiquement :

- authentification ;
- base de données ;
- gestion de projets ;
- historique ;
- PDF ;
- dashboard ;
- favoris ;
- système de rôles ;
- internationalisation ;
- fonctionnalités visibles sur une maquette mais absentes du backlog courant.

Une maquette représente avant tout une **direction UX/UI**.

Une feature visible sur une image n'est pas automatiquement dans le périmètre d'implémentation.

Seul le prompt courant et le backlog validé définissent le scope fonctionnel.

---

## 36. Fonctionnalités V1 prioritaires

Le périmètre initial se concentre sur :

### Infrastructure du calculateur

- sélection de module ;
- formulaires adaptés au module ;
- gestion des unités ;
- validations ;
- architecture des résultats.

### Poutres en béton armé

Commencer par les cas explicitement définis dans le backlog et les prompts.

Ne pas supposer que toutes les variantes de poutres doivent être supportées dès la V1.

### Dalles en béton armé

Même règle : implémenter progressivement les types de dalles explicitement demandés.

---

## 37. Évolutivité

Les futurs modules peuvent comprendre notamment :

- sections de poutres supplémentaires ;
- poutres continues ;
- dalles bidirectionnelles ;
- planchers-dalles ;
- poinçonnement ;
- semelles ;
- poteaux ;
- autres éléments de structure ;
- historique ;
- projets ;
- notes de calcul PDF.

Ces éléments expliquent pourquoi le code doit rester extensible.

Ils ne doivent pas être implémentés par anticipation.

---

## 38. Priorités du projet

En cas d'arbitrage, respecter cet ordre :

1. **Exactitude et traçabilité des calculs**
2. **Clarté du résultat**
3. **Simplicité du parcours utilisateur**
4. **Maintenabilité du code**
5. **Performance**
6. **Sophistication visuelle**

Une animation ou une abstraction élégante ne doit jamais rendre le calcul plus difficile à comprendre ou à vérifier.

---

## 39. Principe final

WorkerTools doit donner l'impression d'un **outil d'ingénierie professionnel simple à utiliser**, et non d'une interface académique remplie de paramètres.

Le code doit suivre le même principe :

**simple à lire, simple à tester, simple à faire évoluer, sans sacrifier la rigueur du moteur de calcul.**
