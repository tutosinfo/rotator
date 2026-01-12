# Gesturl Lite v1.0

Rotateur d'URLs avec calcul de vélocité pour optimiser vos campagnes POP.

## Fonctionnalités

- Ajout d'URLs (unitaire ou import en masse)
- Rotation aléatoire des URLs
- Compteur de hits par URL
- Suppression automatique après X hits
- **Calcul de vélocité** pour optimiser les campagnes POP

## Installation

1. Uploadez les fichiers sur votre serveur PHP
2. Assurez-vous que le dossier est accessible en écriture (pour `database.json`, `buffer.txt`, `velocity_log.json`)
3. Accédez à `index.php` via votre navigateur

## Utilisation

### Ajouter des URLs

- **Unitaire** : Entrez l'URL dans le champ et cliquez "Ajouter"
- **En masse** : Collez plusieurs URLs (une par ligne) dans la zone de texte et cliquez "Importer"

### Lien de rotation

Utilisez le lien `random.php` affiché en haut de page. Chaque appel à ce lien :
1. Sélectionne une URL aléatoire
2. Redirige vers cette URL
3. Incrémente le compteur de hits

## Calcul de Vélocité

### Principe

La vélocité mesure le **trafic cache/spread** résiduel qui arrive naturellement sur votre rotateur.

### Quand s'active-t-elle ?

Le calcul de vélocité s'active **uniquement quand il reste 1 seule URL** dans le rotateur. C'est le moment idéal pour mesurer le flux d'impressions résiduelles.

### Comment ça marche ?

1. Ajoutez vos URLs dans le rotateur
2. Les URLs se suppriment automatiquement après avoir atteint leur quota de hits (ex: 300)
3. Quand il ne reste qu'**1 URL**, le panneau de vélocité apparaît
4. Il compte les hits reçus sur les **20 dernières secondes**

### Niveaux de vélocité

| Niveau | Hits / 20s | Signification | Action recommandée |
|--------|------------|---------------|-------------------|
| **Très élevé** | 100+ | Fort trafic cache | NE PAS déclencher de campagne POP |
| **Élevé** | 50-99 | Bon trafic cache | NE PAS déclencher de campagne POP |
| **Moyen** | 30-49 | Trafic cache modéré | Optionnel |
| **Faible** | < 30 | Peu de trafic cache | DÉCLENCHER campagne POP |

### Objectif : Optimiser vos coûts

- **Vélocité élevée** = Beaucoup d'impressions arrivent via le cache/spread
  - Pas besoin de dépenser en campagne POP
  - Économie d'appels API et de crédits

- **Vélocité faible** = Peu d'impressions naturelles
  - Déclenchez une campagne POP pour générer du trafic
  - Investissement nécessaire pour maintenir le flux

## Fichiers

| Fichier | Description |
|---------|-------------|
| `index.php` | Interface d'administration |
| `random.php` | Script de rotation (à utiliser comme lien) |
| `data_manager.php` | Fonctions de gestion des données |
| `database.json` | Stockage des URLs et hits |
| `buffer.txt` | Tampon temporaire des hits |
| `velocity_log.json` | Timestamps pour le calcul de vélocité |

## Workflow typique

```
1. Importer URLs (ex: 10 URLs)
         ↓
2. Diffuser le lien random.php
         ↓
3. Les URLs accumulent des hits
         ↓
4. URLs supprimées après quota atteint
         ↓
5. 1 URL restante → Mode vélocité activé
         ↓
6. Lecture de la vélocité
         ↓
   ├─ Élevée → Attendre (trafic cache suffisant)
   └─ Faible → Déclencher campagne POP
         ↓
7. Recommencer avec nouvelles URLs
```

## Notes techniques

- Les données de vélocité sont nettoyées automatiquement (garde uniquement les 60 dernières secondes)
- Le buffer de hits est traité à chaque chargement de `index.php`
- Verrouillage des fichiers (`LOCK_EX`) pour éviter les corruptions en cas de forte charge

---

Gesturl Lite v1.0 - Décembre 2025
