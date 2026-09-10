# EC2-05 — Enrobage nominal MVP

## Références et version du profil

Le moteur couvre les armatures passives de béton armé et applique :

- EN 1992-1-1:2004, 4.4.1.1 à 4.4.1.3 et 8.9.1 ;
- NF EN 1992-1-1:2005 ;
- NF EN 1992-1-1/NA:2016-03-24, notamment 4.4.1.2(5), tableau 4.3NF
  (modulations) et tableau 4.4N (armatures de béton armé) ;
- le profil versionné `NF_EN_1992_1_1_2005_FR`.

L’amendement NF EN 1992-1-1/NA/A1:2026-04-14 est indiqué comme en vigueur
dans la documentation du profil. Son texte intégral n’étant pas accessible
publiquement, le moteur ne met en œuvre aucune modification qui lui serait
propre. Il faut confronter les paramètres ci-dessous à cet amendement avant
d’étendre le périmètre ou d’affirmer une couverture exhaustive de l’Annexe
Nationale en vigueur.

## Règle appliquée

`c_nom = c_min + Δc_dev`, avec :

`c_min = max(c_min,b, c_min,dur + Δc_dur,γ - Δc_dur,st - Δc_dur,add, 10 mm)`.

Pour une barre individuelle, `c_min,b` est son diamètre. Le calcul utilise
une unité interne et de restitution unique : millimètre (`mm`).

Les paramètres du profil, centralisés dans `CoverRequirements`, sont :

| Paramètre | Valeur MVP | Provenance / limite |
|---|---:|---|
| Classe structurale initiale | S4 | NA 2016, 4.4.1.2(5), ouvrage courant et béton conforme NF EN 206/CN / annexe E |
| `c_min,dur` | tableau 4.4N | dépend de la classe structurale et de l’exposition |
| `Δc_dur,γ` | 0 mm | valeur recommandée retenue par le profil |
| `Δc_dur,st` | 0 mm | NA 2016, 4.4.1.2(7) ; acier ordinaire uniquement |
| `Δc_dur,add` | 0 mm | pas de protection additionnelle MVP |
| `Δc_dev` | 10 mm | scénario d’exécution MVP ; aucun abattement arbitraire |

## Classe structurale et expositions multiples

Les durées explicitement disponibles sont 25, 50 et 100 ans. Elles produisent
respectivement les modulations -1, 0 et +2 selon le tableau 4.3NF. Les seules
modulations de résistance actuellement applicables aux trois classes béton du
référentiel sont celles de C30/37 : -1 pour X0, XC1, XC2 et XC3. L’option
`compactCover` applique -1 uniquement lorsque l’appelant garantit les
conditions du tableau 4.3NF ; WorkerTools ne peut pas les déduire seul.

Chaque exposition reçoit sa propre classe structurale modulée et son propre
`c_min,dur`. Le plus élevé gouverne ; les résultats intermédiaires sont rendus
dans `exposureResults`.

## Exemple numérique de référence

Pour C25/30, 50 ans, XC4, barre individuelle de diamètre 16 mm :

- S4 sans modulation ;
- tableau 4.4N : `c_min,dur = 30 mm` ;
- `c_min = max(16, 30 + 0 - 0 - 0, 10) = 30 mm` ;
- `c_nom = 30 + 10 = 40 mm`.

## Cas refusés explicitement

- précontrainte, paquets, acier inoxydable, protection ajoutée, traitement de
  surface ou vérification au feu ;
- XF : une exposition XC/XD de référence est nécessaire pour l’enrobage
  français et n’est pas fournie par ce MVP ;
- XA : l’agent agressif doit être caractérisé ;
- durée autre que 25, 50 ou 100 ans ;
- classe structurale hors S1 à S6, données manquantes ou valeurs invalides.

Le mode manuel accepte seulement un enrobage nominal strictement positif et
avertit explicitement qu’aucune conformité normative n’est vérifiée.
