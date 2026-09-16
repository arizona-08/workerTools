# WorkerTools — Backlog V1

> À ajouter à la suite de l’EPIC 13.
>
> Objectif V1 : rendre WorkerTools exploitable en production avec une base fonctionnelle simple, stable et extensible, sans introduire prématurément des fonctionnalités complexes.

---

# Règles générales V1

- Aller au plus simple tant que le besoin production est couvert.
- Préférer des parcours complets et robustes à des fonctionnalités nombreuses mais partielles.
- Réutiliser les briques existantes avant d’ajouter de nouvelles abstractions.
- Conserver les calculs normatifs côté backend.
- Ne jamais dupliquer les règles de calcul dans Angular.
- Toute donnée normative ou dépendante d’une Annexe Nationale reste portée par le `designCodeProfile`.
- Les fonctionnalités non explicitement prévues dans cette V1 restent hors périmètre.
- Tous les nouveaux parcours doivent être testés côté backend et frontend selon leur responsabilité.
- Les migrations de données doivent rester simples, explicites et réversibles autant que possible.

---

# EPIC 14 — Passage du projet à la V1

Cette Epic retire du produit les références historiques au « MVP » et stabilise le vocabulaire pour la production.

## V1-01 — Retirer les références utilisateur au MVP

Rechercher dans le frontend :

```text
MVP
mode MVP
périmètre MVP
version MVP
premier MVP
```

Retirer ou reformuler ces mentions dans :

- titres ;
- descriptions ;
- messages ;
- warnings ;
- placeholders ;
- textes d’aide ;
- écrans de résultats.

Les limitations techniques doivent continuer à être affichées lorsqu’elles sont utiles, mais sans les présenter comme des « limites du MVP ».

Exemple :

```text
Ancien :
Configuration non supportée par le MVP actuel.

Nouveau :
Cette configuration n’est pas encore prise en charge par WorkerTools.
```

### Critères d’acceptation

- [ ] aucune mention utilisateur de « MVP » dans Angular ;
- [ ] les limitations restent compréhensibles ;
- [ ] aucun statut métier n’est modifié ;
- [ ] aucun calcul n’est modifié.

## V1-02 — Retirer les références techniques au MVP

Auditer :

```text
backend/
frontend/
tests/
docs/
config/
```

et remplacer les noms techniques contenant `MVP` lorsqu’ils sont devenus inadaptés.

Exemples possibles :

```text
FIXED_SCOPE
MvpConfig
mvpLimitations
```

Ne pas renommer mécaniquement une notion si le changement casse inutilement beaucoup de code.

Lorsque `FIXED_SCOPE` exprime en réalité une hypothèse structurelle figée, migrer vers une notion plus durable, par exemple :

```text
FIXED
FIXED_SCOPE
SUPPORTED_CONFIGURATION
```

selon les conventions du domaine.

### Critères d’acceptation

- [ ] aucune nouvelle référence technique à « MVP » ;
- [ ] les anciennes références utiles sont renommées proprement ;
- [ ] tests mis à jour ;
- [ ] aucune règle normative modifiée.

## V1-03 — Mettre à jour la documentation

Mettre à jour les documents qui décrivent WorkerTools comme un MVP.

Le produit doit désormais être présenté comme :

```text
WorkerTools V1
```

avec un périmètre fonctionnel explicite.

Conserver séparément :

```text
fonctionnalités supportées
fonctionnalités non supportées
évolutions futures
```

### Critères d’acceptation

- [ ] documentation principale mise à jour ;
- [ ] anciennes formulations « MVP » supprimées ou historisées si nécessaire ;
- [ ] le périmètre V1 est documenté ;
- [ ] les limitations restent visibles.

---

# EPIC 15 — Authentification simple de production

Objectif : protéger les fonctionnalités utilisateur avec une authentification simple, fiable et suffisante pour la production.

Ne pas construire dans cette Epic :

```text
SSO entreprise
OAuth multi-provider
MFA
RBAC complexe
équipes
organisations
permissions granulaires
```

## AUTH-01 — Modèle utilisateur

Mettre en place ou finaliser un modèle utilisateur minimal.

Champs minimum :

```text
id
name
email
password
createdAt
updatedAt
```

L’email doit être unique.

Le mot de passe doit être stocké via le mécanisme de hash sécurisé fourni par Laravel.

### Critères d’acceptation

- [ ] utilisateur persistant ;
- [ ] email unique ;
- [ ] password hashé ;
- [ ] aucun mot de passe en clair ;
- [ ] migrations et tests présents.

## AUTH-02 — Inscription

Créer un parcours simple :

```text
Nom
Email
Mot de passe
Confirmation
```

Après inscription réussie, l’utilisateur peut être automatiquement connecté si cela correspond à l’architecture retenue.

Prévoir les validations essentielles :

```text
email valide
email unique
mot de passe minimum
confirmation identique
```

### Critères d’acceptation

- [ ] endpoint backend ;
- [ ] formulaire Angular ;
- [ ] erreurs de validation affichées ;
- [ ] état loading ;
- [ ] navigation après succès ;
- [ ] tests backend/frontend.

## AUTH-03 — Connexion

Ajouter :

```text
email
mot de passe
```

Utiliser Laravel Sanctum si déjà présent dans le projet.

Le choix cookie/session ou token doit rester cohérent avec l’architecture existante.

### Critères d’acceptation

- [ ] connexion fonctionnelle ;
- [ ] erreurs d’identifiants gérées ;
- [ ] session persistée de façon sûre ;
- [ ] routes privées protégées ;
- [ ] tests.

## AUTH-04 — Déconnexion

Ajouter une action :

```text
Se déconnecter
```

Elle doit invalider proprement la session / le token courant.

### Critères d’acceptation

- [ ] déconnexion backend ;
- [ ] état frontend nettoyé ;
- [ ] redirection cohérente ;
- [ ] route protégée inaccessible après déconnexion.

## AUTH-05 — Utilisateur courant

Créer un endpoint / mécanisme permettant au frontend de récupérer :

```text
id
name
email
```

de l’utilisateur connecté.

Ne pas exposer de données sensibles inutiles.

### Critères d’acceptation

- [ ] récupération de l’utilisateur courant ;
- [ ] état auth restauré après refresh ;
- [ ] gestion propre d’une session expirée.

## AUTH-06 — Protection des routes

Protéger les écrans nécessitant un compte.

Au minimum :

```text
historique
fonctionnalités liées aux calculs enregistrés
```

Décider explicitement si le calculateur lui-même reste accessible sans compte ou exige une connexion.

Pour une V1 simple de production, privilégier une seule règle cohérente dans toute l’application.

## AUTH-07 — Mot de passe oublié

Ajouter un flux simple basé sur le mécanisme Laravel standard :

```text
demande de réinitialisation
email
token temporaire
nouveau mot de passe
```

Ne pas construire de système maison.

### Critères d’acceptation

- [ ] demande de reset ;
- [ ] token expirant ;
- [ ] changement du mot de passe ;
- [ ] messages non révélateurs sur l’existence d’un compte ;
- [ ] tests.

## AUTH-08 — UI compte minimale

Ajouter dans l’interface :

```text
nom utilisateur
email
déconnexion
```

Pas de page profil complexe dans cette V1.

---

# EPIC 16 — Historique des calculs

Objectif : permettre à un utilisateur authentifié de retrouver ses calculs précédents sans mettre en place un système de projets complet.

Un calcul sauvegardé appartient à un utilisateur.

## HISTORY-01 — Modèle CalculationHistory

Créer un modèle persistant simple.

Conceptuellement :

```text
CalculationHistory
├── id
├── userId
├── module
├── submodule
├── title
├── input
├── result
├── status
├── createdAt
└── updatedAt
```

`input` et `result` peuvent être stockés en JSON pour la V1 afin d’éviter une modélisation relationnelle prématurée de tous les résultats de calcul.

### Règles

- chaque entrée appartient à un seul utilisateur ;
- un utilisateur ne peut lire que son historique ;
- le résultat stocké doit correspondre au résultat serveur ;
- aucune confiance accordée à un résultat calculé par le frontend.

## HISTORY-02 — Sauvegarde automatique après calcul

Après un calcul backend réussi :

```text
input validé
+
result structuré
→ historique
```

Éviter un second appel frontend si la sauvegarde peut être effectuée naturellement par le backend lors du calcul.

Ne pas enregistrer les requêtes invalides.

Décider explicitement si un résultat :

```text
NOT_COMPLIANT
CALCULATION_METHOD_NOT_SUPPORTED
```

doit être sauvegardé.

Recommandation V1 :

```text
oui
```

si le backend a produit un résultat structuré exploitable.

## HISTORY-03 — Liste de l’historique

Ajouter une page simple :

```text
Historique
```

Afficher au minimum :

```text
date
titre
module
sous-module
statut
```

Tri :

```text
plus récent d’abord
```

Pagination backend simple.

Pas de recherche avancée nécessaire pour la V1.

## HISTORY-04 — Détail d’un calcul

Permettre d’ouvrir une entrée d’historique.

Afficher :

```text
inputs
summary
verifications
details
warnings
```

Réutiliser autant que possible les composants de résultats existants.

Ne pas recréer une deuxième interface de résultats.

## HISTORY-05 — Relancer un calcul

Depuis un calcul historique :

```text
Reprendre ce calcul
```

préremplit le formulaire avec les inputs enregistrés.

L’utilisateur doit ensuite explicitement recalculer.

Ne pas présenter l’ancien résultat comme le résultat des nouvelles données modifiées.

## HISTORY-06 — Supprimer une entrée

Ajouter une suppression simple avec confirmation.

Règles :

```text
seul le propriétaire peut supprimer
suppression définitive acceptable en V1
```

Pas de corbeille nécessaire.

## HISTORY-07 — Export PDF depuis l’historique

Si le résultat historique contient toutes les données nécessaires :

permettre de réutiliser le système PDF existant.

Ne pas créer une seconde génération PDF.

Si le backend doit recalculer pour garantir la cohérence, réutiliser les inputs enregistrés et le moteur existant.

## HISTORY-08 — Sécurité et limites

Tester :

```text
user A ne lit pas user B
user A ne supprime pas user B
pagination
entrée inexistante
entrée supprimée
```

---

# EPIC 17 — Sous-modules Poutre

Objectif : introduire explicitement la notion de sous-module pour les poutres et ajouter un second cas de calcul utile sans augmenter fortement la complexité du moteur.

La V1 doit proposer deux sous-modules pleinement fonctionnels :

```text
Poutre rectangulaire simplement appuyée
Poutre rectangulaire en console
```

Le cas **console sous charge uniformément répartie** est retenu comme première extension car il permet de réutiliser presque tout le moteur existant :

```text
matériaux
combinaisons
flexion
ferraillage
cisaillement
ELS
conformité
résultats
PDF
historique
```

La principale différence se situe dans :

```text
système statique
formules d’analyse
position de la zone tendue
orientation du ferraillage principal
paramètres dépendant du système structural
```

Ne pas ajouter dans cette Epic :

```text
poutres continues
sections en T
sections en L
charges ponctuelles
multi-travées
encastrement-encastrement
double ferraillage
```

## BEAM-SUB-01 — Modèle de sous-module

Introduire un identifiant explicite de sous-module.

Par exemple :

```text
BEAM_SIMPLE_RECTANGULAR
BEAM_CANTILEVER_RECTANGULAR
```

ou convention équivalente cohérente avec le code existant.

Le sous-module doit être distinct du module :

```text
module = BEAM
submodule = ...
```

Le système statique doit également rester explicite dans le domaine :

```text
SIMPLY_SUPPORTED
CANTILEVER
```

## BEAM-SUB-02 — Catalogue des sous-modules

Créer un catalogue commun backend/frontend ou une représentation synchronisée.

Pour la V1 :

```text
Poutre rectangulaire simplement appuyée
→ AVAILABLE

Poutre rectangulaire en console
→ AVAILABLE
```

Prévoir la possibilité future de déclarer :

```text
COMING_SOON
UNAVAILABLE
```

sans exposer comme utilisables des calculs non fonctionnels.

## BEAM-SUB-03 — Sélecteur frontend

Le module Poutre doit désormais proposer un choix simple entre :

```text
Poutre simplement appuyée
Poutre en console
```

Le sous-module actif doit être identifiable visuellement.

Le changement de sous-module doit :

```text
adapter les hypothèses affichées
adapter le formulaire seulement si nécessaire
réinitialiser/invalider proprement un résultat devenu incompatible
ne pas recharger toute l’application
```

Conserver l’UX la plus simple possible.

## BEAM-SUB-04 — Routage du calcul

Le backend doit résoudre :

```text
module
+
submodule
→ analyse adaptée
```

Pour la V1 :

```text
BEAM_SIMPLE_RECTANGULAR
→ analyse simplement appuyée existante

BEAM_CANTILEVER_RECTANGULAR
→ analyse console
```

Réutiliser les services communs.

Ne pas dupliquer tout l’orchestrateur Beam uniquement pour changer les sollicitations.

## BEAM-CANT-01 — Configuration Poutre console

Ajouter le cas :

```text
poutre rectangulaire
béton armé
console
section constante
charge uniformément répartie
une action permanente
une action variable principale
```

Entrées principales identiques autant que possible au calcul existant :

```text
portée efficace L
largeur b
hauteur h
matériaux
exposition
enrobage
charges
```

La portée correspond à la longueur efficace de la console utilisée par l’analyse.

Le support doit être explicite :

```text
supportSystem = CANTILEVER
```

## BEAM-CANT-02 — Analyse structurale console

Ajouter uniquement les expressions analytiques nécessaires au cas d’une console soumise à une charge uniformément répartie.

Pour une charge linéique de calcul `w` sur une longueur `L`, les valeurs maximales à l’encastrement sont conceptuellement :

```text
|M|max = w L² / 2
|V|max = w L
```

Respecter la convention de signes déjà utilisée dans WorkerTools.

Le moteur doit retourner les valeurs structurées attendues par le reste du pipeline :

```text
MEd
VEd
MCharacteristic
MFrequent
MQuasiPermanent
```

et les efforts ELS déjà utilisés.

Ne pas ajouter de charge ponctuelle.

Ajouter des tests analytiques indépendants pour ce cas.

## BEAM-CANT-03 — Flexion et position des armatures

Réutiliser le moteur de flexion existant autant que possible.

La console crée un moment négatif au voisinage de l’encastrement : la zone tendue principale se situe côté supérieur pour la convention géométrique usuelle.

Le domaine doit donc distinguer explicitement la face tendue / position des armatures.

Ne pas simplement réutiliser silencieusement un ferraillage inférieur prévu pour la poutre simplement appuyée.

Le moteur de proposition doit pouvoir retourner une proposition d’armatures longitudinales principales en partie supérieure.

Éviter de dupliquer toutes les formules de flexion si elles ne dépendent que de :

```text
|MEd|
géométrie utile
matériaux
position de l’acier tendu
```

## BEAM-CANT-04 — Cisaillement console

Réutiliser les règles de cisaillement existantes avec le `VEd` du cas console lorsque leur domaine d’application reste identique.

Vérifier explicitement les hypothèses liées à la zone proche de l’encastrement.

Si une règle existante n’est pas applicable telle quelle :

```text
retourner une limitation explicite
```

plutôt qu’inventer une adaptation.

## BEAM-CANT-05 — ELS console

Réutiliser les briques ELS existantes lorsque leur domaine le permet :

```text
contraintes
fissuration
contrôle simplifié des déformations
```

Les données de fissuration doivent utiliser le ferraillage réellement placé sur la face tendue.

Pour le contrôle simplifié de déformation :

```text
utiliser le facteur correspondant au système structural CANTILEVER
```

uniquement si ce facteur est déjà validé dans le profil normatif.

Sinon :

```text
CALCULATION_METHOD_NOT_SUPPORTED
```

pour cette vérification précise, sans fausser les autres résultats.

## BEAM-CANT-06 — Résultats et conformité

Le sous-module console doit utiliser le même contrat :

```text
status
summary
verifications
details
warnings
```

que le sous-module simplement appuyé.

Le résumé doit notamment permettre d’identifier :

```text
sous-module = console
MEd à l’encastrement
VEd à l’encastrement
ferraillage longitudinal supérieur
étriers si nécessaires
statut global
```

## BEAM-SUB-05 — Historique compatible

Chaque calcul enregistré doit stocker le sous-module.

Les anciennes entrées Beam sans sous-module peuvent être migrées vers :

```text
BEAM_SIMPLE_RECTANGULAR
```

si leur type est connu sans ambiguïté.

Les nouveaux calculs console doivent stocker :

```text
BEAM_CANTILEVER_RECTANGULAR
```

## BEAM-SUB-06 — PDF compatible

La note de calcul doit afficher le sous-module et les hypothèses associées.

Pour une console, le PDF doit notamment indiquer :

```text
système statique = console
sollicitations à l’encastrement
position du ferraillage principal
```

Réutiliser le mapper Beam existant avec extension minimale.

## BEAM-SUB-07 — Tests de référence

Créer au moins un cas de référence indépendant pour chaque sous-module :

```text
BEAM_SIMPLE_RECTANGULAR
BEAM_CANTILEVER_RECTANGULAR
```

Pour le cas console, vérifier au minimum :

```text
charges
MEd
VEd
flexion
ferraillage
cisaillement
ELS applicables
conformité
```

Les valeurs attendues ne doivent pas être dérivées du service de production testé.

## BEAM-SUB-08 — Préparation à l’extension

Documenter comment ajouter plus tard un nouveau sous-module.

Exemples futurs :

```text
poutres continues
sections en T
sections en L
charges ponctuelles
```

Ils restent hors périmètre tant qu’un ticket dédié n’est pas ajouté.

---

# EPIC 18 — Sous-modules Dalle

Objectif : introduire explicitement la notion de sous-module pour les dalles et ajouter un second cas de calcul utile avec un minimum de nouvelles règles.

La V1 doit proposer deux sous-modules pleinement fonctionnels :

```text
Dalle pleine unidirectionnelle simplement appuyée
Dalle pleine unidirectionnelle en console
```

La **dalle unidirectionnelle en console sous charges uniformément réparties** est retenue comme première extension parce qu’elle permet de conserver :

```text
bande de calcul de 1 m
charges surfaciques
matériaux
combinaisons
flexion
ferraillage par mètre
ELS
conformité
résultats
PDF
historique
```

tout en modifiant principalement :

```text
système statique
analyse de la bande
face tendue
position des armatures principales
paramètres dépendant du système structural
```

Ne pas ajouter dans cette Epic :

```text
dalle bidirectionnelle
dalle continue multi-travées
plancher-dalle
poinçonnement
charges ponctuelles
```

## SLAB-SUB-01 — Modèle de sous-module

Introduire des identifiants explicites.

Par exemple :

```text
SLAB_SOLID_ONE_WAY_SIMPLE
SLAB_SOLID_ONE_WAY_CANTILEVER
```

ou convention équivalente.

Le système statique reste explicite :

```text
SIMPLY_SUPPORTED
CANTILEVER
```

## SLAB-SUB-02 — Catalogue des sous-modules

Pour la V1 :

```text
Dalle pleine unidirectionnelle simplement appuyée
→ AVAILABLE

Dalle pleine unidirectionnelle en console
→ AVAILABLE
```

Les autres types restent indisponibles tant qu’ils ne disposent pas d’un moteur validé.

## SLAB-SUB-03 — Sélecteur frontend

Le module Dalle doit proposer un choix simple entre :

```text
Dalle unidirectionnelle simplement appuyée
Dalle unidirectionnelle en console
```

Le sous-module actif doit être visible.

Le changement de sous-module doit invalider proprement un résultat devenu incompatible.

Conserver le même formulaire autant que possible.

## SLAB-SUB-04 — Routage du calcul

```text
module = SLAB
submodule = SLAB_SOLID_ONE_WAY_SIMPLE
→ analyse Slab existante

module = SLAB
submodule = SLAB_SOLID_ONE_WAY_CANTILEVER
→ analyse console
```

Réutiliser le même orchestrateur et les services communs autant que possible.

## SLAB-CANT-01 — Configuration Dalle console

Ajouter le cas :

```text
dalle pleine
unidirectionnelle
console
béton armé
bande de calcul de 1 m
charges uniformément réparties
```

Les entrées restent autant que possible identiques :

```text
portée L
épaisseur h
béton
acier
exposition
enrobage
charges permanentes
charge variable
```

Le système statique doit être :

```text
supportSystem = CANTILEVER
```

## SLAB-CANT-02 — Analyse de la bande console

Conserver :

```text
b = 1 m
```

et transformer les charges surfaciques en charge de bande selon la convention existante.

Pour la bande en console sous charge uniformément répartie, les sollicitations maximales à l’encastrement sont conceptuellement :

```text
|M|max = w L² / 2
|V|max = w L
```

Respecter la convention de signes WorkerTools.

Retourner les mêmes concepts que pour la dalle simplement appuyée :

```text
MEd
VEd
MCharacteristic
MFrequent
MQuasiPermanent
```

et les valeurs ELS nécessaires.

## SLAB-CANT-03 — Flexion et armatures principales

Réutiliser la chaîne de flexion Slab existante.

La console entraîne une traction principale en partie supérieure à proximité de l’encastrement.

La proposition de ferraillage doit donc distinguer explicitement :

```text
face / position = TOP
direction principale
diamètre
espacement
AsProvided / m
```

Ne pas présenter silencieusement ce ferraillage comme une nappe inférieure.

## SLAB-CANT-04 — Armatures secondaires

Réutiliser la logique d’armatures secondaires existante lorsque son domaine reste applicable.

La direction principale / secondaire doit rester explicite.

Ne pas inventer de nouvelles règles constructives propres aux consoles sans source normative identifiée.

## SLAB-CANT-05 — ELS console

Réutiliser :

```text
fissuration
contrôle simplifié des déformations
```

lorsque les méthodes existantes s’appliquent.

La fissuration doit utiliser la nappe principale réellement tendue.

Pour la méthode simplifiée `L/d`, utiliser les paramètres correspondant à une console uniquement s’ils sont validés dans le profil normatif.

Sinon :

```text
CALCULATION_METHOD_NOT_SUPPORTED
```

pour la vérification concernée.

## SLAB-CANT-06 — Résultats et conformité

Conserver le contrat commun :

```text
status
summary
verifications
details
warnings
```

Le résumé doit permettre d’identifier :

```text
sous-module = dalle console
MEd à l’encastrement
ferraillage principal supérieur
armatures secondaires
ELS
statut global
```

## SLAB-SUB-05 — Historique compatible

Chaque calcul Slab historique doit stocker son sous-module.

Les anciennes entrées sans sous-module peuvent être migrées vers :

```text
SLAB_SOLID_ONE_WAY_SIMPLE
```

si elles sont non ambiguës.

Les nouvelles consoles utilisent :

```text
SLAB_SOLID_ONE_WAY_CANTILEVER
```

## SLAB-SUB-06 — PDF compatible

Afficher dans la note :

```text
sous-module
système statique
bande de 1 m
sollicitations à l’encastrement
position du ferraillage principal
```

Réutiliser le mapper Slab commun avec une extension minimale.

## SLAB-SUB-07 — Tests de référence

Créer au moins un cas indépendant pour :

```text
SLAB_SOLID_ONE_WAY_SIMPLE
SLAB_SOLID_ONE_WAY_CANTILEVER
```

Pour la console, vérifier au minimum :

```text
charges surfaciques
charge de bande
MEd
VEd
flexion
armatures principales
armatures secondaires
ELS applicables
conformité
```

## SLAB-SUB-08 — Préparation à l’extension

Documenter comment ajouter ultérieurement :

```text
dalles bidirectionnelles
dalles continues
planchers-dalles
poinçonnement
```

sans les implémenter dans cette Epic.

---

# EPIC 19 — Expansion du référentiel matériaux et exposition

Cette Epic prolonge le référentiel déjà existant pour le béton, l’acier d’armature et les classes d’exposition.

L’utilisateur sélectionne une classe ; WorkerTools déduit les propriétés nécessaires.

Ne pas ajouter de saisie libre de propriétés mécaniques dans la V1 standard.

## MATERIAL-01 — Audit du référentiel actuel

Avant toute extension, lister précisément :

```text
classes béton existantes
classes acier existantes
classes exposition existantes
propriétés associées
règles utilisant ces classes
```

Identifier :

```text
classes réellement calculables
classes uniquement présentes dans les enums
classes refusées par certaines vérifications
```

Ne pas rendre « disponible » une classe qui casserait une vérification obligatoire.

## MATERIAL-02 — Expansion des classes de béton

Compléter le catalogue béton du profil normatif retenu.

Le moteur doit continuer à déduire depuis la classe les propriétés nécessaires, par exemple :

```text
fck
fcm
fctm
Ecm
autres paramètres déjà utilisés
```

La source des valeurs doit rester documentée et versionnée.

### Règles

- aucune propriété mécanique saisie manuellement par l’utilisateur ;
- aucune valeur normative inventée ;
- mêmes classes pour Beam et Slab ;
- pas de duplication des tables dans plusieurs services.

### Tests

Pour chaque classe ajoutée :

```text
lookup
propriétés
fcd
compatibilité flexion
compatibilité ELS pertinente
```

avec cas représentatifs plutôt qu’un test end-to-end complet pour chaque classe si les règles sont communes.

## MATERIAL-03 — Expansion des aciers d’armature

Étendre les nuances réellement supportées par le profil WorkerTools V1.

Avant d’ajouter une nuance :

vérifier que le moteur dispose des propriétés nécessaires, par exemple :

```text
fyk
Es
ductilityClass
fyd
```

et que les règles de dimensionnement utilisées sont applicables.

Ne pas ajouter une nuance simplement parce qu’elle existe dans un catalogue externe.

### Règles

- centraliser le référentiel ;
- Beam et Slab partagent les mêmes données ;
- `γs` continue à provenir du profil normatif lorsqu’applicable ;
- les catalogues de diamètres restent séparés de la nuance d’acier.

## MATERIAL-04 — Expansion des classes d’exposition

Compléter le référentiel des classes d’exposition pertinentes.

Exemples de familles déjà prévues par l’architecture :

```text
XC
XD
XS
...
```

L’extension doit être pilotée par les règles réellement implémentées.

Pour chaque classe :

```text
sélection possible ?
enrobage automatique supporté ?
fissuration supportée ?
limitations particulières ?
```

doivent être explicites.

## MATERIAL-05 — Matrice de capacités exposition

Créer une matrice claire, par exemple :

```text
ExposureClass | selectable | automaticCover | crackCheck | notes
```

Une classe peut être connue du référentiel sans être entièrement supportée par toutes les vérifications.

Dans ce cas, WorkerTools doit retourner une limitation explicite plutôt qu’un résultat approximatif.

## MATERIAL-06 — UI des sélecteurs

Les formulaires Beam et Slab doivent utiliser le même référentiel.

Les sélecteurs doivent :

```text
afficher uniquement les options réellement utilisables
ou
indiquer clairement les limitations
```

Préférer pour la V1 :

```text
n’afficher comme sélectionnables que les classes fonctionnelles
```

si cela simplifie l’expérience production.

## MATERIAL-07 — Compatibilité historique

Un calcul historique doit conserver la classe utilisée au moment du calcul.

Ne pas modifier rétroactivement un ancien résultat parce que le référentiel a évolué.

## MATERIAL-08 — Compatibilité PDF

Le PDF doit continuer à afficher :

```text
classe béton
nuance acier
classe exposition
profil normatif
```

sans logique supplémentaire dans le renderer.

## MATERIAL-09 — Documentation normative

Mettre à jour :

```text
docs/domain/variables-and-constants.md
```

et les documents de profil.

Pour chaque classe / propriété ajoutée, documenter :

```text
code name
symbole
type
signification
unité
origine
valeur
contexte
référence normative
limitations
```

Les paramètres dépendants d’un `NDP` restent fournis par `designCodeProfile`.

---

# EPIC 20 — Stabilisation production V1

Cette Epic vérifie que les nouvelles fonctionnalités V1 fonctionnent ensemble avant mise en production.

Elle ne doit pas ajouter de nouvelle fonctionnalité métier.

## PROD-01 — Parcours authentification

Tester :

```text
inscription
connexion
refresh
déconnexion
reset mot de passe
session expirée
```

## PROD-02 — Parcours Beam connecté

Tester :

```text
connexion
→ choix du sous-module Beam
→ calcul simplement appuyé
→ résultat
→ calcul console
→ résultat
→ historique
→ détail historique
→ export PDF
```

## PROD-03 — Parcours Slab connecté

Tester :

```text
connexion
→ choix du sous-module Slab
→ calcul simplement appuyé
→ résultat
→ calcul console
→ résultat
→ historique
→ détail historique
→ export PDF
```

## PROD-04 — Isolation des données utilisateurs

Tester au minimum deux utilisateurs.

Vérifier :

```text
historique isolé
lecture interdite
suppression interdite
export d’un calcul tiers interdit
```

## PROD-05 — Régression référentiels

Tester des cas représentatifs avec :

```text
plusieurs classes béton
plusieurs aciers supportés
plusieurs classes exposition supportées
```

sur Beam et Slab lorsque les règles s’appliquent.

## PROD-06 — Nettoyage du vocabulaire

Lancer une recherche globale sur :

```text
MVP
```

dans :

```text
frontend
backend
docs
tests
```

Toute occurrence restante doit être :

- supprimée ;
- reformulée ;
- ou explicitement justifiée comme historique non exposé au produit.

## PROD-07 — Vérifications production

Vérifier :

```text
migrations
seeders nécessaires
variables d’environnement
CORS / cookies / Sanctum
HTTPS assumptions
build frontend
build backend
tests
Docker
CI
logs
gestion erreurs
```

## PROD-08 — Checklist V1

La V1 est considérée prête lorsque :

```text
authentification fonctionnelle
historique fonctionnel
Beam avec deux sous-modules fonctionnels
Slab avec deux sous-modules fonctionnels
référentiels étendus validés
PDF toujours fonctionnel
aucune référence produit au MVP
parcours critiques testés
```

---

# Définition de WorkerTools V1

WorkerTools V1 comprend :

```text
Authentification utilisateur
        │
        ├── Calculateur
        │     │
        │     ├── Poutres
        │     │     ├── Poutre rectangulaire simplement appuyée
        │     │     └── Poutre rectangulaire en console
        │     │
        │     └── Dalles
        │           ├── Dalle pleine unidirectionnelle simplement appuyée
        │           └── Dalle pleine unidirectionnelle en console
        │
        ├── Référentiels
        │     ├── Béton
        │     ├── Acier d’armature
        │     └── Classes d’exposition
        │
        ├── Historique des calculs
        │
        └── Export des notes de calcul PDF
```

La V1 reste volontairement centrée sur des cas structuraux simples et explicitement supportés.

Les extensions structurelles telles que :

```text
poutres continues
sections T / L
charges ponctuelles
dalles bidirectionnelles
poinçonnement
semelles
poteaux
```

restent des évolutions futures tant qu’elles ne sont pas ajoutées explicitement au backlog.
