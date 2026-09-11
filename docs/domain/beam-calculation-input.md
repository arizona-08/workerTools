# Contrat d'entrée Poutre MVP

`BeamCalculationInputFactory` valide et assemble les entrées sans lancer de
calcul structurel. Les longueurs sont en `mm` et les charges linéaires en
`kN/m`. Les propriétés mécaniques et dérivées ne sont jamais des entrées
client.

## DESIGN

```json
{
  "configuration": {
    "calculationMode": "DESIGN",
    "elementType": "BEAM",
    "materialType": "REINFORCED_CONCRETE",
    "sectionType": "RECTANGULAR",
    "supportSystem": "SIMPLY_SUPPORTED",
    "loadModel": "UNIFORMLY_DISTRIBUTED",
    "designCodeProfile": "NF_EN_1992_1_1_2005_FR",
    "designSituation": "PERSISTENT_TRANSIENT"
  },
  "geometry": { "effectiveSpan": 6500, "width": 300, "height": 600, "unit": "mm" },
  "materials": { "concreteClass": "C30/37", "steelGrade": "B500B", "exposureClasses": ["XC1"] },
  "loads": {
    "permanent": { "includeSelfWeight": true, "additionalPermanentLoad": 5, "unit": "kN/m" },
    "variable": { "category": "A", "characteristicLoad": 3.5, "unit": "kN/m" }
  }
}
```

## VERIFICATION

Le même contrat est utilisé avec `calculationMode: "VERIFICATION"` et le
ferraillage longitudinal existant obligatoire :

```json
{
  "reinforcement": {
    "longitudinal": {
      "tension": { "barCount": 4, "barDiameter": 16, "diameterUnit": "mm" }
    }
  }
}
```

`providedSteelArea` / `As,prov` n'est pas accepté dans ce contrat : le backend
le dérive de `barCount` et `barDiameter`. En DESIGN, `reinforcement` est absent
et sa présence est refusée. Une entrée valide ne signifie jamais que la poutre
est conforme ; elle est seulement exploitable par le futur moteur.
