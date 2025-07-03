# Configuration des médias pour Railway et Netlify

Ce guide explique comment configurer correctement l'accès aux médias (photos et vidéos) entre votre frontend hébergé sur Netlify et votre backend hébergé sur Railway.

## Problème résolu

Les chemins des médias stockés dans la base de données doivent être compatibles avec l'URL du backend Railway et accessibles depuis le frontend Netlify.

## Modifications apportées

1. **Backend (Laravel)**
   - Modification de `MediaController.php` pour utiliser l'URL du backend Railway au lieu de localhost:8000
   - Création de scripts pour standardiser les chemins des fichiers dans la base de données
   - Création d'un script pour configurer le stockage sur Railway

2. **Frontend (React)**
   - Modification de `mediaService.js` pour construire correctement les URLs des médias

## Étapes à suivre

### 1. Sur le serveur Railway (Backend)

1. **Exécuter le script de configuration du stockage**

   ```bash
   php setup_railway_storage.php
   ```

   Ce script va :
   - Vérifier et créer le lien symbolique entre `storage/app/public` et `public/storage`
   - Créer les répertoires nécessaires (`media`, `news`, `documents`)
   - Configurer les permissions correctement
   - Créer un fichier de test pour vérifier l'accès

2. **Standardiser les chemins des fichiers dans la base de données**

   ```bash
   php standardize_media_paths.php
   ```

   Ce script va :
   - Parcourir tous les médias dans la base de données
   - Standardiser les chemins au format `media/nom_du_fichier.ext`
   - Ignorer les URLs externes (YouTube, Vimeo, etc.)

3. **Vérifier l'accès aux fichiers**

   Accédez à l'URL suivante pour vérifier que le fichier de test est accessible :
   ```
   https://backend-production-b4aa.up.railway.app/storage/media/test-railway.txt
   ```

### 2. Déployer les modifications du frontend sur Netlify

Le service `mediaService.js` a été modifié pour construire correctement les URLs des médias en utilisant l'URL du backend Railway.

## Structure des chemins de fichiers

Pour que tout fonctionne correctement, les chemins des fichiers dans la base de données doivent être au format :
- `media/nom_du_fichier.jpg` (recommandé)
- ou simplement `nom_du_fichier.jpg`

Le script `standardize_media_paths.php` s'assure que tous les chemins sont au bon format.

## Dépannage

### Fichiers non accessibles

Si les fichiers ne sont pas accessibles, vérifiez :
1. Que le lien symbolique est correctement configuré sur Railway
2. Que les fichiers existent dans le répertoire `storage/app/public/media`
3. Que les chemins dans la base de données sont au format correct

### Erreurs CORS

Si vous rencontrez des erreurs CORS, assurez-vous que votre backend autorise les requêtes depuis votre domaine Netlify :

```php
// Dans app/Http/Middleware/CorsMiddleware.php
header('Access-Control-Allow-Origin: https://heroic-gaufre-c8e8ae.netlify.app');
```

### Problèmes de cache

Essayez de vider le cache du navigateur ou d'utiliser le mode incognito pour tester.

## Vérification des URLs dans la base de données

Pour vérifier que les chemins sont correctement formatés dans la base de données :

```sql
SELECT id, type, title, file_path FROM media;
```

Les chemins devraient être au format `media/nom_du_fichier.jpg`. 