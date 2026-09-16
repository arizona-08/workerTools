# WorkerTools V1

WorkerTools est une application web de calcul de structures en béton armé.
Elle fournit des parcours lisibles pour renseigner un élément, exécuter le
calcul côté serveur et consulter la conformité, les valeurs principales, le
détail des vérifications et une note de calcul PDF.

## Fonctionnalités supportées

- Poutre rectangulaire en béton armé, simplement appuyée, sous charges
  verticales uniformément réparties ;
- Dalle pleine unidirectionnelle à une travée, représentée par une bande de
  calcul de 1 m simplement appuyée sur deux côtés opposés ;
- Dimensionnement et vérification lorsque les données de ferraillage requises
  sont disponibles ;
- Vérifications couvertes en ELU et ELS, selon le profil français
  `NF EN 1992-1-1:2005` et ses paramètres associés ;
- Résultats structurés, détail des calculs et export de note de calcul PDF.

## Fonctionnalités non supportées

- Poutres continues, consoles, sections en T ou L et double ferraillage ;
- Charges ponctuelles, effets sismiques, effort normal et précontrainte ;
- Dalles bidirectionnelles, poinçonnement, semelles et poteaux ;
- Calcul explicite de flèche en millimètres lorsque seule la méthode simplifiée
  portée/hauteur utile est applicable.

Les limitations affichées par l’application décrivent le périmètre réellement
pris en charge ; elles ne modifient ni les règles normatives ni les résultats
produits par le moteur.

## Évolutions futures

Les futurs développements sont décrits dans
[workerTools_v1_epics.md](workerTools_v1_epics.md), notamment
l’authentification et l’historique des calculs.

## Développement

Consultez [docs/development.md](docs/development.md) pour démarrer Angular,
Laravel, Nginx et la base de données en environnement local.
