# Guide pour résoudre les problèmes de médias sur Railway

Ce guide vous aidera à résoudre les problèmes d'accès aux fichiers médias (photos et vidéos) entre votre frontend sur Netlify et votre backend sur Railway.

## 1. Configuration du backend sur Railway

### 1.1 Vérifier le lien symbolique storage

Exécutez le script `check_storage_link.php` sur votre serveur Railway pour vérifier et créer le lien symbolique si nécessaire :

```bash
php check_storage_link.php
```

### 1.2 Créer les dossiers nécessaires

Assurez-vous que les dossiers suivants existent sur votre serveur Railway :

- `/storage/app/public/media`
- `/public/storage/media`

### 1.3 Configurer les permissions

Assurez-vous que les permissions sont correctement configurées :

```bash
chmod -R 755 storage/app/public
chmod -R 755 public/storage
```

## 2. Téléchargement des fichiers médias

### 2.1 Option 1 : Téléchargement via SFTP/SSH

1. Connectez-vous à votre serveur Railway via SSH ou SFTP
2. Téléchargez vos fichiers médias dans le dossier `/storage/app/public/media`

### 2.2 Option 2 : Téléchargement via le panneau d'administration

1. Connectez-vous à votre panneau d'administration
2. Utilisez la fonctionnalité de téléchargement de médias
3. Les fichiers seront automatiquement stockés au bon endroit

### 2.3 Option 3 : Utiliser l'API Laravel

Vous pouvez utiliser l'API Laravel pour télécharger des fichiers :

```bash
curl -X POST https://backend-production-b4aa.up.railway.app/api/upload \
  -H "Content-Type: multipart/form-data" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -F "file=@/chemin/vers/votre/fichier.jpg" \
  -F "type=photo"
```

## 3. Tester l'accès aux fichiers

Après avoir téléchargé vos fichiers, testez l'accès en visitant :

```
https://backend-production-b4aa.up.railway.app/test-media.php
```

Ce script testera différentes combinaisons de chemins pour vos fichiers médias et vous montrera lesquels sont accessibles.

## 4. Mise à jour du frontend

Si vous avez modifié les chemins des fichiers dans la base de données, vous devrez peut-être mettre à jour votre frontend pour utiliser les nouveaux chemins.

Le service `mediaService.js` a été mis à jour pour gérer correctement les différents formats de chemins de fichiers.

## 5. Vérification des URLs dans la base de données

Vérifiez que les chemins stockés dans la base de données sont cohérents :

```sql
SELECT id, type, title, file_path FROM media;
```

Les chemins devraient être au format :
- `media/nom_du_fichier.jpg` (recommandé)
- ou simplement `nom_du_fichier.jpg`

## 6. Dépannage

### 6.1 Erreurs CORS

Si vous rencontrez des erreurs CORS, assurez-vous que votre backend autorise les requêtes depuis votre domaine Netlify :

```php
// Dans app/Http/Middleware/CorsMiddleware.php
header('Access-Control-Allow-Origin: https://heroic-gaufre-c8e8ae.netlify.app');
```

### 6.2 Problèmes de chemins

Si les chemins ne fonctionnent pas, essayez de standardiser tous les chemins dans la base de données :

```sql
UPDATE media SET file_path = CONCAT('media/', SUBSTRING_INDEX(file_path, '/', -1)) 
WHERE file_path NOT LIKE 'http%' AND file_path NOT LIKE 'media/%';
```

### 6.3 Problèmes de cache

Essayez de vider le cache du navigateur ou d'utiliser le mode incognito pour tester.

## 7. Solution alternative : Utiliser un service de stockage externe

Si les problèmes persistent, envisagez d'utiliser un service de stockage externe comme :

1. **Amazon S3** : Stockage cloud fiable et évolutif
2. **Cloudinary** : Spécialisé dans la gestion des médias
3. **Uploadcare** : Simple à intégrer

Ces services offrent des URLs stables et une meilleure performance pour la distribution de contenu. 