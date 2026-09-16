# WorkerTools — Backlog V1

## Objectif

Construire progressivement le V1 de WorkerTools autour de deux modules de calcul en béton armé :

- Poutres
- Dalles

Le V1 doit rester centré sur des cas simples, fiables et clairement documentés :

- poutre rectangulaire simplement appuyée ;
- dalle unidirectionnelle ;
- calculs basés sur l’Eurocode 2 ;
- vérifications ELU / ELS ;
- dimensionnement / vérification ;
- affichage synthétique et détaillé des résultats.

Le backlog est découpé pour que **chaque sous-étape puisse devenir un prompt Codex indépendant**.

---

# EPIC 0 — Préparer le socle existant

Avant de développer le moteur de calcul, Codex doit comprendre ce qui existe déjà.

| ID | Feature | Objectif | Priorité |
|---|---|---|---|
| `CORE-01` | Audit frontend | Identifier routes, layouts, composants, styles et formulaires déjà présents | P0 |
| `CORE-02` | Audit backend | Identifier architecture Laravel, routes API, conventions et services existants | P0 |
| `CORE-03` | Nettoyage ciblé | Corriger uniquement les problèmes structurels bloquant les prochaines features | P0 |

## CORE-01 — Audit frontend

Codex devra analyser notamment :

```text
frontend/
├── src/
├── app/
├── routes
├── components
└── styles
```

Résultat attendu : pas de refonte. On veut seulement savoir ce qu’on peut conserver.

## CORE-02 — Audit backend

Même principe pour Laravel :

```text
backend/
├── app/
├── routes/
├── tests/
└── config/
```

À la fin, on doit savoir où placer proprement :

```text
Calculations
Materials
Eurocode
Verifications
Results
```

sans créer toute l’architecture prématurément.

---

# EPIC 1 — Infrastructure commune du calculateur

## CALC-01 — Sélection des modules

L’utilisateur doit pouvoir choisir :

```text
Poutres
Dalles
```

Pour l’instant, rien d’autre.

### Critères d’acceptation

```text
[ ] Poutres est disponible
[ ] Dalles est disponible
[ ] Un seul module est sélectionné à la fois
[ ] Le module actif est identifiable visuellement
[ ] Le changement de module ne recharge pas la page
[ ] Ajouter un futur module reste simple
```

## CALC-02 — Layout de la page de calcul

Reproduire la logique générale du design :

```text
Titre                              [ Calculer ]

┌──────────────────────────┐ ┌──────────────────┐
│                          │ │                  │
│ Formulaire               │ │ Résumé résultat │
│                          │ │                  │
└──────────────────────────┘ └──────────────────┘

┌───────────────────────────────────────────────┐
│ Détails du calcul                             │
└───────────────────────────────────────────────┘
```

À ce stade, les résultats peuvent être vides.

Le layout doit déjà être responsive.

## CALC-03 — Architecture des formulaires dynamiques

Créer une architecture où :

```text
Calculator
    ↓
Module actif
    ├── BeamForm
    └── SlabForm
```

Le parent ne doit pas contenir toute la logique des deux formulaires.

## CALC-04 — Gestion commune des unités

Créer les conventions utilisées dans l’application.

Exemples :

```text
Longueurs
mm / cm / m

Charges
kN
kN/m
kN/m²

Moments
kN·m

Contraintes
MPa

Armatures
mm²
cm²
mm²/m
```

Le moteur devra utiliser une convention interne stable.

## CALC-05 — Modèle générique des résultats

Définir le contrat commun permettant à Angular d’afficher les résultats.

Conceptuellement :

```ts
CalculationResult {
  status
  summary
  verifications
  details
  warnings
}
```

Sans encore imposer tous les champs définitifs.

---

# EPIC 2 — Référentiel Eurocode / matériaux

## EC2-01 — Classes de béton

L’utilisateur choisit par exemple :

```text
C20/25
C25/30
C30/37
...
```

Le moteur doit pouvoir en déduire les propriétés nécessaires au calcul.

On ne demande donc pas à l’utilisateur de saisir manuellement :

```text
fck
fcm
fctm
Ecm
...
```

si elles peuvent être déterminées à partir de la classe.

## EC2-02 — Classes d’acier

Même principe.

Exemple V1 :

```text
B500B
```

Le moteur connaît les propriétés nécessaires :

```text
fyk
Es
γs
...
```

## EC2-03 — Coefficients de sécurité

Créer une représentation explicite des coefficients utilisés par le moteur.

Ils ne doivent pas apparaître comme des valeurs dispersées dans différentes classes.

L’interface pourra avoir une section :

**Coefficients de sécurité**

repliée par défaut comme dans le design.

## EC2-04 — Classes d’exposition

Ajouter le référentiel nécessaire aux futures règles de durabilité :

```text
XC...
XD...
XS...
etc.
```

Cette étape peut initialement se limiter au modèle de données et au sélecteur si le calcul d’enrobage arrive juste après.

## EC2-05 — Calcul de l’enrobage

Permettre deux modes :

```text
● Calcul automatique
○ Valeur imposée
```

En automatique, le moteur utilise les paramètres applicables au cas traité.

En manuel :

```text
Enrobage nominal
[ 35 ] mm
```

---

# EPIC 3 — Poutre : formulaire V1

> **Cadre normatif de cette Epic**
>
> Le V1 Poutre est cadré sur une poutre en béton armé, à section rectangulaire, simplement appuyée et soumise à des charges verticales uniformément réparties. Les règles de béton armé relèvent de NF EN 1992-1-1 et de l’Annexe Nationale française applicable. Les actions et combinaisons nécessaires au calcul relèvent également d’EN 1990 / EN 1991. Les paramètres déterminés nationalement ne doivent pas être codés en dur dans les formulaires.
>
> **Convention importante :**
>
> - `USER` = valeur saisie ou choisie par l’utilisateur ;
> - `DERIVED` = valeur calculée par WorkerTools ;
> - `PROFILE` = valeur provenant du profil normatif / Annexe Nationale ;
> - `FIXED_SCOPE` = hypothèse volontairement figée pour le premier V1.

## Champs métier complets du formulaire Poutre

### A. Configuration générale

| Champ | Symbole / clé suggérée | Type | Origine | Unité | V1 |
|---|---|---:|---|---|---|
| Mode de calcul | `calculationMode` | enum `DESIGN / VERIFICATION` | USER | — | Oui |
| Type de section | `sectionType` | enum | FIXED_SCOPE = `RECTANGULAR` | — | Oui |
| Système statique | `supportSystem` | enum | FIXED_SCOPE = `SIMPLY_SUPPORTED` | — | Oui |
| Type de matériau | `materialType` | enum | FIXED_SCOPE = `REINFORCED_CONCRETE` | — | Oui |
| Type de chargement | `loadModel` | enum | FIXED_SCOPE = `UNIFORMLY_DISTRIBUTED` | — | Oui |
| Profil normatif | `designCodeProfile` | enum/id | PROFILE | — | Oui |
| Situation de projet | `designSituation` | enum | FIXED_SCOPE = persistante/transitoire | — | Oui |

Ne pas afficher les valeurs `FIXED_SCOPE` comme des sélecteurs inutiles si un seul choix est supporté. Elles doivent toutefois exister explicitement dans le domaine afin de ne pas rendre les hypothèses implicites.

### B. Portée et appuis

L’Eurocode distingue la portée libre de la portée efficace. Pour le V1, deux stratégies sont acceptables :

1. **Mode simple recommandé au départ :** l’utilisateur saisit directement la **portée efficace de calcul** `l_eff`.
2. **Mode assisté ultérieur :** l’utilisateur saisit la portée libre et la géométrie des appuis ; WorkerTools détermine `l_eff`.

#### Champs V1

| Champ | Symbole / clé | Origine | Unité | Validation |
|---|---|---|---|---|
| Portée efficace | `l_eff` | USER | m | `> 0` |

#### Champs à prévoir pour une évolution vers le calcul automatique de `l_eff`

| Champ | Symbole / clé | Origine | Unité |
|---|---|---|---|
| Portée libre entre faces d’appui | `l_n` | USER | m |
| Largeur appui gauche | `supportWidthLeft` | USER | mm |
| Largeur appui droit | `supportWidthRight` | USER | mm |
| Contributions d’extrémité | `a1`, `a2` | DERIVED | mm |
| Portée efficace | `l_eff` | DERIVED | m |

### C. Géométrie de section

| Champ | Symbole / clé | Origine | Unité | Validation |
|---|---|---|---|---|
| Largeur de poutre | `b` / `width` | USER | mm ou cm UI | `> 0` |
| Hauteur totale | `h` / `height` | USER | mm ou cm UI | `> 0` |
| Aire brute de béton | `Ac` | DERIVED | mm² | `b × h` |
| Hauteur utile | `d` | DERIVED | mm | calculée plus tard |
| Distance armatures comprimées | `dPrime` | DERIVED | mm | seulement si nécessaire dans une future méthode |

Le backend doit convertir les dimensions vers une unité interne unique avant tout calcul.

### D. Matériau béton

#### Champs utilisateur

| Champ | Clé | Origine | UI |
|---|---|---|---|
| Classe de résistance du béton | `concreteClass` | USER | select, ex. `C25/30`, `C30/37` |

#### Propriétés dérivées / référentiel

| Propriété | Symbole | Origine | Utilisation |
|---|---|---|---|
| Résistance caractéristique en compression | `fck` | DERIVED | ELU, cisaillement, ELS |
| Résistance moyenne en compression | `fcm` | DERIVED | propriétés matériau |
| Résistance moyenne en traction | `fctm` | DERIVED | armatures minimales, fissuration |
| Module sécant du béton | `Ecm` | DERIVED | ELS |
| Déformation caractéristique/ultime pertinente | `εc*` | DERIVED | modèle constitutif si requis |
| Coefficient partiel béton | `γc` | PROFILE | `fcd` |
| Coefficient relatif à la résistance de calcul | `αcc` si utilisé par le profil | PROFILE | `fcd` |
| Résistance de calcul du béton | `fcd` | DERIVED | flexion / cisaillement |
| Poids volumique béton armé | `γconcrete` | PROFILE / config | poids propre |

### E. Acier d’armature

#### Champs utilisateur

| Champ | Clé | Origine | UI |
|---|---|---|---|
| Nuance / classe d’acier | `steelGrade` | USER | select, ex. `B500B` |

#### Propriétés dérivées / référentiel

| Propriété | Symbole | Origine |
|---|---|---|
| Limite caractéristique d’élasticité | `fyk` | DERIVED |
| Module d’Young de l’acier | `Es` | DERIVED / référentiel |
| Coefficient partiel acier | `γs` | PROFILE |
| Résistance de calcul | `fyd` | DERIVED |
| Classe de ductilité | `ductilityClass` | DERIVED |

### F. Durabilité et enrobage

Deux modes doivent être distingués.

#### Mode manuel V1 possible

| Champ | Clé | Origine | Unité |
|---|---|---|---|
| Mode d’enrobage | `coverMode` | USER | — |
| Enrobage nominal | `c_nom` | USER | mm |

#### Mode automatique

Le calcul automatique ne doit être activé que lorsque les paramètres nécessaires au profil normatif sont réellement implémentés.

| Champ | Clé | Origine |
|---|---|---|
| Classe d’exposition | `exposureClass` | USER |
| Durée d’utilisation de projet | `designWorkingLife` | USER / PROFILE |
| Classe structurale ou équivalent exigé par la génération normative | `structuralClass` | USER / DERIVED / PROFILE |
| Tolérance d’exécution | `Δc_dev` | PROFILE ou USER avancé |
| Exigence de durabilité | `c_min,dur` | DERIVED |
| Exigence d’adhérence | `c_min,b` | DERIVED |
| Enrobage minimal | `c_min` | DERIVED |
| Enrobage nominal | `c_nom` | DERIVED |

Le calcul automatique de l’enrobage doit rester désactivé tant que les paramètres de l’Annexe Nationale retenue ne sont pas explicitement disponibles.

### G. Armatures longitudinales — mode Dimensionnement

La hauteur utile dépend du ferraillage envisagé. Le moteur doit donc connaître au moins un diamètre d’armature de calcul ou implémenter une itération contrôlée.

| Champ | Clé | Origine | Unité |
|---|---|---|---|
| Diamètre longitudinal préféré / initial | `preferredLongitudinalBarDiameter` | USER | mm |
| Diamètre d’étrier envisagé | `preferredStirrupDiameter` | USER | mm |
| Nombre maximal de lits autorisés | `maxRebarLayers` | USER / config | — |
| Diamètres autorisés | `availableLongitudinalDiameters` | config | mm |

Pour un premier V1 simple, le formulaire peut uniquement demander le diamètre longitudinal préféré et le diamètre d’étrier ; les règles de proposition de ferraillage appartiennent à l’EPIC 6.

### H. Armatures longitudinales — mode Vérification

| Champ | Clé | Origine | Unité |
|---|---|---|---|
| Nombre de barres tendues | `tensionBarsCount` | USER | — |
| Diamètre des barres tendues | `tensionBarDiameter` | USER | mm |
| Aire d’acier tendu fournie | `As_prov` | DERIVED | mm² |
| Nombre de lits | `tensionRebarLayers` | USER | — |
| Nombre de barres comprimées | `compressionBarsCount` | USER | — |
| Diamètre des barres comprimées | `compressionBarDiameter` | USER | mm |
| Aire d’acier comprimé fournie | `As2_prov` | DERIVED | mm² |

Pour le V1 en flexion simple, les armatures comprimées peuvent être optionnelles et la configuration doit être déclarée `non supportée` si la méthode de calcul nécessite un dimensionnement doublement armé non encore implémenté.

### I. Armatures transversales — mode Vérification

Ces champs deviennent obligatoires lorsque la vérification du cisaillement avec armatures transversales est activée.

| Champ | Clé | Origine | Unité |
|---|---|---|---|
| Diamètre d’étrier | `stirrupDiameter` | USER | mm |
| Nombre de branches efficaces | `stirrupLegs` | USER | — |
| Espacement des étriers | `stirrupSpacing` | USER | mm |
| Angle des étriers | `alpha` | FIXED_SCOPE | 90° |
| Aire transversale par étrier | `Asw` | DERIVED | mm² |
| Ratio fourni | `Asw_over_s_prov` | DERIVED | mm²/mm |

### J. Actions permanentes

| Champ | Clé | Origine | Unité |
|---|---|---|---|
| Inclure le poids propre | `includeSelfWeight` | USER | bool |
| Charge permanente additionnelle | `Gk_additional` | USER | kN/m |
| Poids propre | `Gk_self` | DERIVED | kN/m |
| Charge permanente totale | `Gk_total` | DERIVED | kN/m |

### K. Action variable

Pour le V1, une seule action variable uniformément répartie est supportée.

| Champ | Clé | Origine | Unité |
|---|---|---|---|
| Charge variable caractéristique | `Qk` | USER | kN/m |
| Catégorie / type d’action variable | `variableActionCategory` | USER | — |
| Coefficient de combinaison | `ψ0` | PROFILE / DERIVED | — |
| Coefficient fréquent | `ψ1` | PROFILE / DERIVED | — |
| Coefficient quasi-permanent | `ψ2` | PROFILE / DERIVED | — |

Les coefficients `ψ` relèvent du profil EN 1990 / EN 1991 + Annexe Nationale applicable. Ils ne doivent pas être inventés par le moteur.

### L. Paramètres avancés / normatifs

La section « Coefficients de sécurité » du design peut afficher ces valeurs en lecture seule et, éventuellement, autoriser un override expert plus tard.

| Paramètre | Origine |
|---|---|
| `γG` | PROFILE |
| `γQ` | PROFILE |
| `γc` | PROFILE |
| `γs` | PROFILE |
| `αcc` si applicable | PROFILE |
| `ψ0`, `ψ1`, `ψ2` | PROFILE |
| paramètres nationaux de fissuration | PROFILE |
| paramètres nationaux de cisaillement | PROFILE |

Pour le V1, ne pas autoriser la modification libre de ces coefficients dans l’UI standard.


On attaque le premier vrai module.

À ce stade, on ne code encore aucune formule de résistance.

## BEAM-01 — Configuration du calcul

Créer les choix structurants du formulaire.

Pour le V1 :

```text
Élément
Poutre

Matériau
Béton armé

Section
Rectangulaire

Système statique
Simplement appuyé
```

Les autres valeurs pourront apparaître comme futures options désactivées ou ne pas apparaître du tout.

## BEAM-02 — Mode Dimensionnement / Vérification

Ajouter :

```text
[ Dimensionnement ] [ Vérification ]
```

### Dimensionnement

WorkerTools calcule notamment l’armature nécessaire.

### Vérification

L’utilisateur fournit également le ferraillage existant.

Cette distinction aura un impact sur les champs affichés.

## BEAM-03 — Géométrie

Pour la poutre rectangulaire simplement appuyée :

```text
Portée L
Largeur b
Hauteur h
```

Exemple UI :

```text
Portée (L)       [ 6.50 ] m
Largeur (b)      [ 30   ] cm
Hauteur (h)      [ 60   ] cm
```

## BEAM-04 — Matériaux

Formulaire :

```text
Classe béton
[C30/37 ▼]

Acier
[B500B ▼]
```

Éventuellement :

```text
Classe d’exposition
[XC1 ▼]
```

Les propriétés dérivées restent côté moteur.

## BEAM-05 — Charges permanentes

Ajouter :

```text
☑ Inclure automatiquement le poids propre

Charge permanente supplémentaire Gk
[     ] kN/m
```

Le poids propre ne doit pas être saisi manuellement lorsque son calcul automatique est activé.

## BEAM-06 — Charges d’exploitation

Ajouter :

```text
Charge variable Qk
[     ] kN/m
```

Pour le V1, on reste sur des charges uniformément réparties.

Pas encore de charges ponctuelles.

## BEAM-07 — Ferraillage en mode vérification

Uniquement lorsque :

```text
mode = verification
```

Afficher au minimum les armatures longitudinales nécessaires à la vérification.

Par exemple :

```text
Armatures tendues
Nombre      [4]
Diamètre    [20] mm
```

Puis les étriers lorsqu’on implémentera le cisaillement.

## BEAM-08 — Validation du formulaire

Vérifier :

```text
valeurs obligatoires
valeurs positives
dimensions cohérentes
classes disponibles
valeurs numériques
unités
configurations supportées
```

La validation frontend améliore l’UX.

Le backend doit malgré tout refaire la validation.

---

# EPIC 4 — Poutre : analyse structurale

## Données d’entrée et de sortie de l’analyse

Cette Epic transforme les caractéristiques et actions saisies dans l’EPIC 3 en sollicitations de calcul. Le cas V1 reste strictement :

- poutre simplement appuyée ;
- portée efficace `l_eff` ;
- section constante ;
- charge uniformément répartie ;
- une action permanente totale ;
- une action variable principale ;
- pas d’effort normal imposé ;
- pas de torsion ;
- pas de charge ponctuelle.

### Entrées nécessaires

| Donnée | Symbole |
|---|---|
| Portée efficace | `l_eff` |
| Largeur | `b` |
| Hauteur | `h` |
| Poids volumique béton | `γconcrete` |
| Inclure poids propre | `includeSelfWeight` |
| Charge permanente additionnelle | `Gk_additional` |
| Charge variable caractéristique | `Qk` |
| Coefficients partiels d’actions | `γG`, `γQ` |
| Coefficients ELS | `ψ0`, `ψ1`, `ψ2` |

### Valeurs dérivées minimales

| Résultat | Symbole / clé | Unité |
|---|---|---|
| Aire de section | `Ac` | m² ou mm² interne cohérent |
| Poids propre linéaire | `Gk_self` | kN/m |
| Charge permanente totale | `Gk_total` | kN/m |
| Charge ELU de calcul | `wEd` | kN/m |
| Charge ELS caractéristique | `wSlsCharacteristic` | kN/m |
| Charge ELS fréquente | `wSlsFrequent` | kN/m |
| Charge ELS quasi-permanente | `wSlsQuasiPermanent` | kN/m |
| Moment ELU maximal | `MEd` | kN·m |
| Effort tranchant ELU maximal | `VEd` | kN |
| Moment ELS caractéristique | `MCharacteristic` | kN·m |
| Moment ELS fréquent | `MFrequent` | kN·m |
| Moment ELS quasi-permanent | `MQuasiPermanent` | kN·m |

Pour la poutre simplement appuyée sous charge uniformément répartie, le moteur peut appliquer les expressions analytiques adaptées au cas, mais il doit refuser toute configuration qui ne correspond pas aux hypothèses du V1.

### Traçabilité à retourner

Pour chaque combinaison, retourner :

```text
nom de la combinaison
valeurs caractéristiques utilisées
coefficients appliqués
charge résultante
formule d’analyse
moment résultant
effort tranchant résultant
unité
```

Ainsi le frontend peut afficher le détail du calcul sans refaire les opérations.


## BEAM-CALC-01 — Poids propre

À partir de :

```text
b
h
masse volumique béton
```

calculer la charge permanente correspondant au poids propre.

Créer des tests dédiés.

## BEAM-CALC-02 — Actions caractéristiques

Construire :

```text
Gk
Qk
```

à partir des données du formulaire.

## BEAM-CALC-03 — Combinaisons ELU

Implémenter la combinaison utilisée dans le périmètre V1.

Le moteur doit retourner les valeurs intermédiaires nécessaires à la traçabilité.

## BEAM-CALC-04 — Combinaisons ELS

Séparer explicitement les combinaisons nécessaires aux vérifications ELS prévues.

Ne pas mélanger ELU et ELS dans une seule variable « charge calculée ».

## BEAM-CALC-05 — Moment fléchissant

Pour la poutre simplement appuyée sous charge uniformément répartie, calculer notamment :

```text
MEd
```

et exposer le détail du calcul.

## BEAM-CALC-06 — Effort tranchant

Calculer :

```text
VEd
```

avec tests.

---

# EPIC 5 — Poutre : flexion ELU

## Données nécessaires à la vérification / au dimensionnement en flexion

### Entrées provenant des Epics précédentes

| Donnée | Symbole |
|---|---|
| Largeur de section | `b` |
| Hauteur totale | `h` |
| Enrobage nominal | `c_nom` |
| Diamètre des étriers | `φ_st` |
| Diamètre longitudinal de calcul | `φ_l` |
| Classe béton | `concreteClass` |
| Classe acier | `steelGrade` |
| Résistance caractéristique béton | `fck` |
| Résistance moyenne traction | `fctm` |
| Résistance de calcul béton | `fcd` |
| Limite caractéristique acier | `fyk` |
| Résistance de calcul acier | `fyd` |
| Moment de calcul ELU | `MEd` |
| Aire d’acier fournie en mode vérification | `As_prov` |

### Grandeurs géométriques dérivées

| Résultat | Symbole | Unité |
|---|---|---|
| Hauteur utile | `d` | mm |
| Distance au centre des armatures comprimées, si pertinente | `d'` | mm |
| Aire brute béton | `Ac` | mm² |

Pour un seul lit de barres tendues, la position du centre des armatures doit tenir compte de l’enrobage, du diamètre d’étrier et du demi-diamètre de la barre longitudinale. Pour plusieurs lits, utiliser le centre de gravité réel du groupe de barres et non une approximation silencieuse.

### Grandeurs matériaux dérivées

| Résultat | Symbole |
|---|---|
| Résistance de calcul béton | `fcd` |
| Résistance de calcul acier | `fyd` |
| Paramètres du bloc de contraintes correspondant à la génération EC2 retenue | `λ`, `η` ou équivalent |
| Déformation ultime béton pertinente | `εcu*` |

Ces paramètres doivent provenir du profil normatif.

### Grandeurs de calcul flexion à exposer

La méthode exacte doit être documentée par référence à la version EC2 utilisée. Les noms suivants représentent les concepts à conserver même si la formulation mathématique varie selon la méthode retenue.

| Résultat | Symbole / clé | Unité |
|---|---|---|
| Moment réduit / paramètre adimensionnel | `μ` ou `K` | — |
| Limite du domaine simplement armé | `μ_lim` / `K_lim` | — |
| Position de l’axe neutre | `x` | mm |
| Rapport axe neutre / hauteur utile | `x_over_d` | — |
| Bras de levier | `z` | mm |
| Section d’acier requise | `As_req` | mm² |
| Section minimale d’acier | `As_min` | mm² |
| Section maximale / limite constructive si appliquée | `As_max` | mm² |
| Section d’acier retenue/fournie | `As_prov` | mm² |
| Moment résistant de la section fournie | `MRd` | kN·m |
| Taux d’utilisation en flexion | `utilizationFlexure` | % |
| Statut flexion | `flexureStatus` | enum |

### Conditions à vérifier

Le moteur doit au minimum distinguer :

```text
MEd <= MRd
As_prov >= As_req
As_prov >= As_min
d > 0
x/d dans le domaine de validité de la méthode
section simplement armée compatible avec le périmètre V1
```

En mode `DESIGN`, si le moment dépasse le domaine géré par le dimensionnement simplement armé, ne pas générer arbitrairement une quantité d’acier. Retourner une limitation explicite, par exemple :

```text
DOUBLE_REINFORCEMENT_REQUIRED
```

En mode `VERIFICATION`, utiliser le ferraillage réellement fourni pour déterminer la résistance et non `As_req`.

### Détail de calcul attendu

Le backend doit pouvoir transmettre au frontend des étapes structurées telles que :

```text
1. hauteur utile d
2. fcd et fyd
3. paramètre réduit
4. axe neutre
5. bras de levier
6. As,req
7. As,min
8. As,provided
9. MRd
10. vérification MEd / MRd
```

Chaque étape doit contenir les variables utilisées, le résultat non arrondi, la valeur d’affichage et l’unité.


## BEAM-FLEX-01 — Hauteur utile

Calculer :

```text
d
```

à partir notamment de :

```text
h
cnom
diamètre des armatures
diamètre des étriers
```

selon le cas traité.

## BEAM-FLEX-02 — Résistance de calcul des matériaux

Déterminer les valeurs utilisées par les formules de dimensionnement :

```text
fcd
fyd
```

à partir du référentiel Eurocode.

## BEAM-FLEX-03 — Moment réduit

Implémenter le calcul du paramètre réduit utilisé par la méthode retenue.

Retourner :

```text
valeur
limite
statut
```

## BEAM-FLEX-04 — Position de l’axe neutre

Calculer les grandeurs nécessaires à la détermination du bras de levier.

## BEAM-FLEX-05 — Bras de levier

Calculer :

```text
z
```

avec ses bornes applicables.

## BEAM-FLEX-06 — Section d’armature requise

Calculer :

```text
As,req
```

## BEAM-FLEX-07 — Armature minimale

Calculer :

```text
As,min
```

et vérifier que :

```text
As >= As,min
```

## BEAM-FLEX-08 — Limites du domaine couvert

Si le cas nécessite une méthode ou un ferraillage qui n’est pas encore pris en charge, WorkerTools doit retourner quelque chose du type :

```text
Configuration non supportée par le moteur actuel
```

plutôt qu’un résultat approximatif.

---

# EPIC 6 — Proposition de ferraillage

## Données nécessaires au moteur de proposition

Le moteur de proposition de ferraillage ne doit pas uniquement trouver une aire d’acier supérieure à `As_req`. Il doit également vérifier que la disposition est constructible dans la poutre et qu’elle respecte les règles de détail couvertes par le V1.

### Entrées

| Donnée | Symbole / clé | Origine |
|---|---|---|
| Largeur de poutre | `b` | EPIC 3 |
| Hauteur totale | `h` | EPIC 3 |
| Enrobage nominal | `c_nom` | EPIC 3 |
| Diamètre d’étrier | `φ_st` | EPIC 3 |
| Section requise | `As_req` | EPIC 5 |
| Section minimale | `As_min` | EPIC 5 |
| Diamètres longitudinaux disponibles | `availableDiameters` | config |
| Diamètre préféré | `preferredDiameter` | USER |
| Nombre maximum de lits | `maxLayers` | config / USER |
| Taille maximale de granulat `d_g` | `maxAggregateSize` | USER si la vérification d’espacement l’exige |
| Règles d’espacement minimal | paramètres normatifs | PROFILE |

### Champ supplémentaire à ajouter au formulaire si la constructibilité complète est incluse dans le V1

| Champ | Clé | Unité | Pourquoi |
|---|---|---|---|
| Diamètre maximal du granulat | `maxAggregateSize` | mm | intervient dans les exigences d’espacement libre entre barres |

Si ce champ n’est pas encore collecté, le moteur de proposition doit soit utiliser une hypothèse explicitement documentée dans le profil de projet, soit limiter sa promesse à une proposition d’aire d’acier sans prétendre valider complètement la disposition géométrique.

### Résultats d’une proposition

| Résultat | Clé | Unité |
|---|---|---|
| Nombre de barres | `barCount` | — |
| Diamètre | `barDiameter` | mm |
| Nombre de lits | `layers` | — |
| Distribution par lit | `barsPerLayer` | — |
| Aire fournie | `As_prov` | mm² |
| Excédent par rapport à `As_req` | `reinforcementExcess` | % |
| Espacement libre horizontal | `clearHorizontalSpacing` | mm |
| Largeur disponible dans l’étrier | `availableInternalWidth` | mm |
| Constructible | `fitsSection` | bool |
| Règles constructives satisfaites | `detailingStatus` | enum |

### Contraintes à prendre en compte

Vérifier au minimum, lorsque les données nécessaires sont disponibles :

```text
As_prov >= max(As_req, As_min)
barres physiquement contenues dans la largeur disponible
espacement libre minimal entre barres
enrobage respecté
diamètres appartenant au catalogue autorisé
nombre de lits <= limite du V1
```

Le moteur peut essayer plusieurs combinaisons, puis classer les solutions par simplicité, proximité avec `As_req` et préférence de diamètre. Éviter de considérer automatiquement la combinaison avec le moins d’acier comme la meilleure si elle conduit à une disposition peu pratique.


## REBAR-01 — Catalogue des diamètres

Définir les diamètres supportés par l’application.

Pas de valeur magique directement dans le composant Angular.

## REBAR-02 — Proposition d’armature longitudinale

À partir de :

```text
As,req
```

proposer une disposition telle que :

```text
4 HA20
```

avec :

```text
As,provided
```

## REBAR-03 — Validation géométrique

Une proposition de ferraillage n’est valide que si elle peut physiquement tenir dans la section.

Il faudra donc vérifier les contraintes géométriques pertinentes avant de proposer une disposition.

Inclut le filtrage géométrique des candidats et le recalcul de la chaîne de flexion avec le diamètre réel `(candidate → d → μEd → x → z → As_req → As_min → As_target)` avant validation du candidat.

---

# EPIC 7 — Cisaillement ELU

## Données nécessaires au cisaillement

### Entrées communes

| Donnée | Symbole | Origine |
|---|---|---|
| Effort tranchant de calcul | `VEd` | EPIC 4 |
| Largeur de l’âme / section | `bw` | `b` pour section rectangulaire |
| Hauteur utile | `d` | EPIC 5 |
| Béton | `fck`, `fcd` | référentiel |
| Acier | `fywd` / propriété de calcul correspondante | référentiel |
| Aire d’armatures longitudinales ancrées au droit de la section | `Asl` | EPIC 5/6 ou saisie vérification |
| Effort normal | `NEd` | FIXED_SCOPE = 0 |
| Aire béton | `Ac` | DERIVED |
| Paramètres nationaux de cisaillement | `CRd,c`, `vmin`, `k1`, etc. | PROFILE |

L’aire `Asl` utilisée pour `VRd,c` doit correspondre aux armatures longitudinales effectivement pertinentes au droit de la section vérifiée ; ne pas utiliser automatiquement `As_req` si une aire différente est réellement fournie.

### A. Vérification sans armatures transversales requises

Grandeurs dérivées à conserver :

| Résultat | Symbole | Unité |
|---|---|---|
| Facteur de taille | `k` | — |
| Taux longitudinal | `ρl` | — |
| Contrainte moyenne de compression | `σcp` | MPa |
| Résistance béton au cisaillement | `VRd,c` | kN |
| Taux d’utilisation béton | `VEd / VRd,c` | % |

Pour le cas V1 sans effort normal :

```text
NEd = 0
σcp = 0
```

mais ces valeurs doivent rester explicites dans le moteur.

### B. Dimensionnement d’armatures transversales

#### Hypothèses V1

| Paramètre | Valeur |
|---|---|
| Type d’armature transversale | étriers |
| Angle des étriers `α` | 90° |
| Modèle treillis | selon profil EC2 retenu |
| Inclinaison des bielles `θ` | déterminée/contrôlée dans le domaine EC2 supporté |

Si `θ` est choisi par l’utilisateur dans une future version, il devra être exposé comme paramètre avancé. Pour le V1, préférer une stratégie déterministe documentée plutôt qu’un champ expert supplémentaire.

#### Données dérivées

| Résultat | Symbole | Unité |
|---|---|---|
| Bras de levier utilisé au cisaillement | `z` | mm |
| Armature transversale requise par unité de longueur | `Asw/s_req` | mm²/mm ou mm²/m |
| Armature transversale minimale | `Asw/s_min` | mm²/mm ou mm²/m |
| Résistance apportée par les étriers | `VRd,s` | kN |
| Résistance maximale associée aux bielles comprimées | `VRd,max` | kN |
| Taux d’utilisation cisaillement | `utilizationShear` | % |
| Statut | `shearStatus` | enum |

### C. Champs de vérification d’étriers existants

En mode `VERIFICATION` :

| Champ | Clé | Unité |
|---|---|---|
| Diamètre étrier | `stirrupDiameter` | mm |
| Nombre de branches | `stirrupLegs` | — |
| Espacement | `stirrupSpacing` | mm |
| Aire `Asw` | `Asw` | DERIVED |
| Aire par unité de longueur fournie | `Asw_over_s_prov` | DERIVED |

### D. Critères de résultat

Le moteur doit distinguer au minimum :

```text
VEd <= VRd,c : aucune armature transversale de calcul requise selon le modèle,
              sous réserve des armatures minimales / dispositions applicables

VEd > VRd,c : dimensionner ou vérifier les étriers

VEd <= VRd,s
VEd <= VRd,max
Asw/s_prov >= Asw/s_req
Asw/s_prov >= Asw/s_min lorsque requis
```

Ne jamais conclure « conforme au cisaillement » uniquement parce que `VEd <= VRd,s` si la limite `VRd,max` ou une autre vérification requise n’a pas été effectuée.


## BEAM-SHEAR-01 — Résistance sans armatures transversales

Implémenter la vérification correspondante du périmètre retenu.

Résultats structurés :

```text
VEd
VRd,c
```

## BEAM-SHEAR-02 — Dimensionnement des étriers

Lorsque nécessaire :

```text
Asw/s
```

## BEAM-SHEAR-03 — Vérification résistance maximale

Vérifier également la limite supérieure applicable au mécanisme de compression.

## BEAM-SHEAR-04 — Proposition d’étriers

Exemple :

```text
2 HA8 / 150 mm
```

La proposition doit respecter à la fois la quantité nécessaire et les règles constructives couvertes.

---

# EPIC 8 — ELS Poutre

## Principe général ELS

Les vérifications ELS ne peuvent pas être correctement réalisées avec seulement `Gk` et `Qk` si le moteur ne connaît pas les coefficients de combinaison de l’action variable. L’EPIC 3 doit donc fournir la catégorie d’action ou directement un profil permettant de déterminer `ψ0`, `ψ1` et `ψ2`.

Le backend doit conserver séparément les effets :

```text
combinaison caractéristique
combinaison fréquente
combinaison quasi-permanente
```

et utiliser la combinaison exigée par la vérification considérée et par le profil normatif.

## BEAM-SLS-01 — Vérification des contraintes

### Entrées

| Donnée | Symbole |
|---|---|
| Section `b × h` | `b`, `h` |
| Hauteur utile | `d` |
| Aire d’acier fournie | `As_prov` |
| Aire comprimée si présente | `As2_prov` |
| Module béton | `Ecm` |
| Module acier | `Es` |
| Résistances matériaux | `fck`, `fyk` |
| Moment ELS de la combinaison pertinente | `M_sls` |
| Effort normal ELS | FIXED_SCOPE = 0 |

### Valeurs dérivées possibles

| Résultat | Symbole |
|---|---|
| Rapport modulaire | `αe = Es/Ecm` ou modèle approprié |
| Position de l’axe neutre fissuré | `x_sls` |
| Inertie fissurée / transformée | `I_cr` |
| Contrainte béton | `σc` |
| Contrainte acier | `σs` |
| Limites de contrainte | provenant du PROFILE |
| Statut de vérification | `stressStatus` |

La méthode exacte doit être cohérente avec les hypothèses retenues pour fluage, fissuration et durée des charges. Ne pas mélanger une analyse instantanée et une analyse long terme sans l’indiquer.

## BEAM-SLS-02 — Fissuration

La vérification directe de largeur de fissure nécessite davantage de données que la flexion ELU.

### Entrées nécessaires

| Donnée | Symbole / clé | Origine |
|---|---|---|
| Enrobage jusqu’à l’armature longitudinale | `c` | DERIVED depuis géométrie |
| Diamètre des barres tendues | `φ` | ferraillage fourni/retenu |
| Espacement des barres | `s` | DERIVED depuis disposition ou USER |
| Aire d’acier tendue | `As_prov` | ferraillage |
| Largeur de section | `b` | géométrie |
| Hauteur totale | `h` | géométrie |
| Hauteur utile | `d` | géométrie |
| Axe neutre fissuré | `x_sls` | DERIVED |
| Aire efficace de béton tendu | `Ac,eff` | DERIVED |
| Ratio d’armature efficace | `ρp,eff` | DERIVED |
| Résistance traction efficace | `fct,eff` | DERIVED / hypothèse documentée |
| Module acier | `Es` | référentiel |
| Rapport modulaire | `αe` | DERIVED |
| Contrainte acier sous combinaison pertinente | `σs` | DERIVED |
| Coefficient durée de charge | `kt` | DERIVED / PROFILE |
| Coefficient adhérence | `k1` | DERIVED selon type de barre |
| Coefficient distribution des déformations | `k2` | DERIVED selon flexion/tension |
| Coefficients nationaux de fissuration | `k3`, `k4` ou équivalent | PROFILE |
| Limite de fissure | `wmax` | PROFILE selon exposition / usage |

### Champ éventuellement requis dans le formulaire

Si le moteur ne peut pas déduire la durée de chargement ou le scénario de vérification à partir des combinaisons :

| Champ | Clé | UI |
|---|---|---|
| Condition de chargement pour fissuration | `crackLoadDuration` | court terme / long terme, ou déterminée automatiquement |

Préférer la détermination automatique lorsque la combinaison et le cas de charge suffisent.

### Résultats à exposer

| Résultat | Symbole | Unité |
|---|---|---|
| Hauteur efficace de béton tendu | `hc,eff` | mm |
| Aire efficace de béton tendu | `Ac,eff` | mm² |
| Ratio efficace | `ρp,eff` | — |
| Espacement maximal des fissures | `sr,max` | mm |
| Différence moyenne de déformation acier/béton | `εsm - εcm` | — |
| Largeur caractéristique de fissure | `wk` | mm |
| Limite | `wmax` | mm |
| Taux d’utilisation | `wk / wmax` | % |
| Statut | `crackStatus` | enum |

Le détail doit explicitement indiquer la combinaison ELS utilisée.

## BEAM-SLS-03 — Flèche / déformation

### Stratégie V1 recommandée

Commencer par la **méthode simplifiée de contrôle par rapport portée / hauteur utile** prévue par EC2 pour les poutres et dalles de bâtiments lorsque son domaine d’application est satisfait.

Le moteur doit être capable de répondre :

```text
vérification simplifiée applicable ?
oui -> comparer le rapport réel à la limite calculée
non -> retourner CALCULATION_METHOD_NOT_SUPPORTED
```

Ne pas prétendre avoir calculé une flèche en millimètres si seule la méthode simplifiée `l/d` a été appliquée.

### Entrées de la vérification simplifiée

| Donnée | Symbole |
|---|---|
| Portée efficace | `l_eff` |
| Hauteur utile | `d` |
| Résistance béton | `fck` |
| Aire d’acier requise | `As_req` |
| Aire d’acier fournie | `As_prov` |
| Largeur de la zone tendue | `b` |
| Taux d’armatures | `ρ` |
| Taux d’armatures de référence | `ρ0` si utilisé |
| Type de système structural | simplement appuyé |
| Facteur structural `K` ou équivalent | PROFILE / DERIVED |
| Facteurs correctifs applicables | PROFILE / DERIVED |

### Résultats

| Résultat | Clé |
|---|---|
| Rapport réel | `actualSpanDepthRatio` |
| Rapport limite de base | `baseAllowableSpanDepthRatio` |
| Facteurs correctifs | `deflectionFactors` |
| Rapport limite final | `allowableSpanDepthRatio` |
| Taux d’utilisation | `actual / allowable` |
| Statut | `deflectionStatus` |
| Méthode | `SIMPLIFIED_SPAN_DEPTH` |

### Calcul explicite de flèche — hors V1 initial

Un calcul explicite ultérieur pourra nécessiter notamment :

```text
fluage φ(t,t0)
retrait
âge au chargement
durée de chargement
Ecm / module effectif
courbures section fissurée et non fissurée
inerties
conditions de support
combinaisons quasi-permanentes
```

Ne pas ajouter ces champs au formulaire V1 tant que le calcul explicite de déformation n’est pas réellement implémenté.

## Résultat ELS global

L’EPIC 8 doit produire au minimum :

```text
stressVerification
crackVerification
deflectionVerification
slsStatus
```

avec, pour chaque vérification :

```text
status
utilization
governingValue
limitValue
loadCombination
method
details
warnings
```

Un statut `NOT_CHECKED` ou `NOT_APPLICABLE` doit être possible ; il ne doit jamais être assimilé à `COMPLIANT`.


## BEAM-SLS-01 — Vérification des contraintes

Mettre en place les calculs nécessaires aux contraintes lorsque le périmètre les exige.

## BEAM-SLS-02 — Fissuration

Calculer les paramètres nécessaires à la vérification de fissuration.

Retour attendu :

```text
wk
wk,max
status
```

ou la méthode retenue dans le périmètre validé.

## BEAM-SLS-03 — Flèche

Implémenter la méthode de vérification choisie pour le V1.

Pour le premier V1, privilégier une approche simplifiée Eurocode 2 avant un calcul complet de déformation.

---

# EPIC 9 — Conformité Poutre

## BEAM-RESULT-01 — Agrégation des vérifications

Créer un service capable de transformer :

```text
Flexion       OK
Cisaillement  OK
Fissuration   OK
Flèche        OK
```

en un statut global.

## BEAM-RESULT-02 — Vérification gouvernante

Identifier la vérification la plus sollicitée lorsque c’est possible.

Exemple :

```text
Flexion : 75 %
Cisaillement : 42 %
```

Le taux affiché dans l’interface devra provenir de cette logique.

## BEAM-RESULT-03 — Résumé

Retourner au frontend les données nécessaires aux cartes :

```text
Taux d’utilisation
Moment
Hauteur utile
As requise
Ferraillage proposé
Conformité
```

## BEAM-RESULT-04 — Détail du calcul

Retourner une représentation structurée :

```text
Hypothèses
Combinaisons
Moment
Flexion
Armatures
Cisaillement
ELS
```

Le frontend pourra utiliser ces données pour les accordéons.

---

# EPIC 10 — Interface des résultats Poutre

## UI-RESULT-01 — Indicateur de conformité

Donut / indicateur :

```text
75 %
OPTIMAL

Conforme
```

Sans dépendre uniquement de la couleur.

## UI-RESULT-02 — Cartes des valeurs principales

Par exemple :

```text
Moment utile
Hauteur utile
Armatures
Ferraillage proposé
```

## UI-RESULT-03 — Message synthétique

Exemple :

> La section satisfait les vérifications réalisées dans le périmètre actuel selon l’Eurocode 2.

Le texte doit s’adapter à la situation.

## UI-RESULT-04 — Accordéons du détail

Comme dans le design :

```text
1. Hypothèses et paramètres
2. Calcul des sollicitations
3. Flexion
4. Armatures
5. Cisaillement
6. ELS
```

## UI-RESULT-05 — Affichage des formules

Pour chaque étape disponible :

```text
Nom

Formule
μ = ...

Substitution
μ = ...

Résultat
μ = 0.xxx
```

---

# EPIC 11 — Module Dalle unidirectionnelle

Une fois le module Poutre stabilisé, réutiliser les briques communes.

## SLAB-01 — Configuration

V1 :

```text
Dalle pleine
Unidirectionnelle
Béton armé
```

## SLAB-02 — Géométrie

Entrées principales :

```text
Portée L
Épaisseur h
```

Le calcul peut travailler sur une bande :

```text
b = 1 m
```

sans demander cette information à l’utilisateur.

## SLAB-03 — Matériaux

Réutiliser exactement :

```text
ConcreteClass
SteelGrade
ExposureClass
```

du moteur commun.

Pas de duplication.

## SLAB-04 — Charges surfaciques

Exemple :

```text
Poids propre             automatique
Revêtement               kN/m²
Cloisons                  kN/m²
Autres permanentes        kN/m²
Charge d’exploitation     kN/m²
```

## SLAB-05 — Combinaisons

Réutiliser le moteur commun lorsqu’il est réellement applicable.

## SLAB-06 — Analyse

Calculer les sollicitations de la bande de dalle dans le cas V1.

## SLAB-07 — Flexion ELU

Déterminer notamment :

```text
MEd
As,req
As,min
```

## SLAB-08 — Proposition de ferraillage

Retour attendu plutôt sous la forme :

```text
HA10 / 150 mm
```

avec :

```text
As,provided / m
```

## SLAB-09 — Armatures secondaires

Ajouter les armatures nécessaires dans la direction secondaire selon le périmètre normatif retenu.

## SLAB-10 — ELS

Implémenter progressivement :

```text
fissuration
flèche
```

## SLAB-11 — Conformité

Même contrat que pour Beam :

```text
status
summary
verifications
details
```

## SLAB-12 — UI résultats

Réutiliser autant que possible les composants de résultats déjà créés.

Le but est d’arriver à quelque chose comme :

```text
BeamCalculationPage
        │
        ├──── ResultSummary
        ├──── VerificationCard
        └──── CalculationDetails

SlabCalculationPage
        │
        ├──── ResultSummary
        ├──── VerificationCard
        └──── CalculationDetails
```

plutôt que deux implémentations entièrement indépendantes.

---

# EPIC 12 — Fiabilisation du V1

Cette étape est obligatoire avant de considérer le calculateur terminé.

## QA-01 — Cas de référence Poutre

Créer plusieurs cas dont les résultats sont connus indépendamment.

Pour chacun :

```text
inputs connus
↓
résultats théoriques connus
↓
WorkerTools
↓
comparaison avec tolérance
```

## QA-02 — Cas de référence Dalle

Même stratégie.

## QA-03 — Cas non conformes

Tester volontairement :

```text
section insuffisante
ferraillage insuffisant
cisaillement trop élevé
etc.
```

## QA-04 — Cas limites

Tester notamment :

```text
valeur = 0
dimensions absurdes
très petites sections
très grandes charges
valeur exactement sur une limite
```

## QA-05 — Vérification des unités

Construire des tests empêchant une régression du style :

```text
cm traité comme mm
kN·m traité comme N·mm
```

## QA-06 — UX globale

Vérifier finalement :

```text
desktop
tablette
mobile
clavier
loading
erreur API
validation
non conformité
warning
```

---

# EPIC 13 — Export de note de calcul PDF

## PDF-01 — Modèle de note de calcul
Définir la structure commune du document :
- titre ;
- type de calcul ;
- date ;
- profil normatif ;
- hypothèses ;
- géométrie ;
- matériaux ;
- charges ;
- combinaisons ;
- sollicitations ;
- vérifications ;
- ferraillage proposé ;
- statut final ;
- avertissements / limitations.

## PDF-02 — Génération backend
Générer le PDF côté backend à partir du résultat structuré existant.

Le générateur ne doit effectuer aucun calcul normatif.

Il consomme :
- `summary`
- `verifications`
- `details`
- les entrées du calcul

## PDF-03 — Note de calcul Poutre
Mapper les résultats Beam vers le modèle PDF commun.

## PDF-04 — Note de calcul Dalle
Mapper les résultats Slab vers le même modèle.

## PDF-05 — Téléchargement frontend
Ajouter le bouton :
`Exporter la note de calcul`

avec gestion :
- loading ;
- erreur ;
- téléchargement du PDF.

# Définition du V1

Une fois les Epics `0 → 12` terminées, WorkerTools dispose de son premier vrai V1 :

```text
                    WORKERTOOLS V1

                      Calculateur
                           │
             ┌─────────────┴──────────────┐
             │                            │
          POUTRE                         DALLE
             │                            │
   Rectangulaire simple          Unidirectionnelle
             │                            │
      Béton armé EC2              Béton armé EC2
             │                            │
     ┌───────┴────────┐          ┌────────┴───────┐
     │                │          │                │
    ELU              ELS        ELU              ELS
     │                │          │                │
 Flexion          Fissuration  Flexion        Fissuration
 Cisaillement     Flèche       Ferraillage    Flèche
 Ferraillage
     │                            │
     └────────────┬───────────────┘
                  │
             CONFORMITÉ
                  │
      Résumé + détails du calcul
```

---

# Hors périmètre V1

Ces fonctionnalités pourront être traitées dans une V1 ou plus tard :

- poutres continues ;
- sections en T ;
- sections en L ;
- double ferraillage ;
- charges ponctuelles ;
- dalles bidirectionnelles ;
- planchers-dalles ;
- poinçonnement ;
- semelles ;
- poteaux ;
- projets ;
- historique ;
- favoris ;
- génération de notes de calcul PDF ;
- authentification avancée ;
- dashboard métier.

---

# Références de cadrage utilisées pour préciser les EPIC 3 à 8

Ces références servent à cadrer les données nécessaires et la structure du backlog. Elles ne remplacent pas l’accès au texte normatif complet lors de l’implémentation et de la validation finale.

- **NF EN 1992-1-1:2005 + Annexe Nationale française NF EN 1992-1-1/NA** : règles générales pour les structures en béton. L’Annexe Nationale française de 2016 est indiquée comme en vigueur par AFNOR et a reçu un amendement `NF EN 1992-1-1/NA/A1` publié en avril 2026.
- **EN 1990 / EN 1991 + Annexes Nationales applicables** : actions, coefficients partiels et coefficients de combinaison `ψ`.
- **JRC — Eurocode 2: Background & Applications, Design of Concrete Buildings — Worked Examples** : exemples de conception et de vérification pas à pas.
- **The Concrete Centre — Worked Examples to Eurocode 2** : exemples de poutres/dalles incluant combinaisons, flexion, cisaillement et contrôle simplifié des déformations.
- **EN 1992-1-1 §5.3.2.2** : notion de portée efficace.
- **EN 1992-1-1 §6.2** : vérification au cisaillement.
- **EN 1992-1-1 §7.3** : maîtrise de la fissuration.
- **EN 1992-1-1 §7.4** : maîtrise des déformations.

> **Règle projet :** lorsqu’une valeur dépend d’un paramètre national (`NDP`), elle doit provenir du `designCodeProfile` et non être codée directement dans l’algorithme.

---

# Prochaine étape recommandée

Avant de lancer Codex sur les tâches de calcul, verrouiller précisément la spécification métier du premier cas :

**Poutre rectangulaire en béton armé simplement appuyée sous charges uniformément réparties**

À définir précisément :

- champs d’entrée ;
- unités ;
- hypothèses ;
- règles Eurocode ;
- combinaisons ;
- formules ;
- limites de validité ;
- résultats ;
- critères de conformité ;
- cas de tests de référence.
