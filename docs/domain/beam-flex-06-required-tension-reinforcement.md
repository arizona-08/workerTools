# Armature longitudinale requise Poutre — BEAM-FLEX-06

## Équilibre ELU

Pour la section rectangulaire simplement armée couverte par le MVP, la
résultante de traction dans l'acier est :

`Fs = As × fyd`

Le moment associé au bras de levier interne `z` est :

`M = Fs × z`

L'équilibre au moment de calcul conduit à l'aire théorique d'armatures tendues
requise :

`As_req = MEd_Nmm / (fyd × z)`

`MEd` provient de BEAM-CALC-05 et est converti de `kN·m` en `N·mm` par
`MomentConverter`. `fyd`, issu de BEAM-FLEX-02, est exprimé en `MPa`,
numériquement équivalent à `N/mm²`; `z`, issu de BEAM-FLEX-05, est en `mm`.
Le résultat est donc exprimé en `mm²`.

## Portée et limites

`As_req` exprime exclusivement l'équilibre ELU sans arrondi intermédiaire. Une
valeur de moment nulle est admise et donne `As_req = 0 mm²`.

Cette aire n'est pas l'armature finale à mettre en œuvre :

- `As_min` sera une exigence réglementaire distincte, traitée par
  BEAM-FLEX-07 ;
- l'armature fournie ou proposée (nombre et diamètre de barres) n'est pas
  calculée ici ;
- aucune comparaison avec une armature fournie, résistance `MRd`, limite de
  ductilité ou conformité n'est réalisée.

L'armature minimale réglementaire `As_min` est désormais calculée séparément
par BEAM-FLEX-07. Elle n'est ni comparée ni combinée à `As_req` dans cette
étape.
