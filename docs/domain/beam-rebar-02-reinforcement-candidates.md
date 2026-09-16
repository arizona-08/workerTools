# Candidats de ferraillage longitudinal Poutre — BEAM-REBAR-02

## Génération d'aires discrètes

BEAM-REBAR-02 transforme l'aire continue `As_target` en une liste de candidats
homogènes `n × φ`, en utilisant exclusivement le catalogue V1 centralisé
`ReinforcementBarDiameterCatalog` : `8`, `10`, `12`, `14`, `16`, `20`, `25`
et `32 mm`.

Pour chaque diamètre et chaque nombre de barres configuré, l'aire provient de
`BeamLongitudinalReinforcement` :

`Aφ = π × φ² / 4`

`As_prov = n × Aφ`

Seuls les candidats avec `As_prov ≥ As_target` sont conservés. Ils sont classés
par excès d'aire croissant, puis nombre de barres croissant, puis diamètre
croissant. Ce classement est une stratégie déterministe de proposition, non
une preuve de constructibilité ou d'optimalité de chantier.

## Configuration et limites

`BeamReinforcementProposalConfiguration` fixe pour le V1 `2 ≤ n ≤ 8`.
Ces bornes sont des choix `CONFIG` du générateur : elles ne constituent pas une
règle Eurocode. Même pour `As_target = 0`, le générateur propose au minimum
deux barres et ne crée jamais un candidat à zéro barre.

Si aucune combinaison du catalogue et de cette plage ne satisfait l'aire
cible, le résultat porte le statut explicite `NO_REINFORCEMENT_CANDIDATE`; le
générateur n'augmente ni la limite de huit barres ni le catalogue.

BEAM-REBAR-02 ne vérifie pas la largeur intérieure, l'espacement libre,
l'enrobage latéral, les collisions, les diamètres mixtes ou plusieurs lits. Ces
candidats d'aire devront être filtrés géométriquement par BEAM-REBAR-03. Il ne
recalcule pas non plus `d`, `As_req`, `As_target` ou `MRd`.
