# Configuration de Cloudinary pour le stockage des médias

Ce document explique comment configurer et utiliser Cloudinary pour stocker et servir les médias (images et vidéos) de votre application ACOS Football Academy.

## 1. Pourquoi utiliser Cloudinary?

- **Accès aux médias plus fiable**: Les médias sont accessibles même si votre backend est redémarré
- **CDN intégré**: Livraison rapide des médias dans le monde entier
- **Transformations d'images**: Redimensionnement, recadrage, etc. à la volée
- **Gestion des vidéos**: Conversion, streaming, et optimisation des vidéos
- **Plan gratuit généreux**: Jusqu'à 25GB de stockage et 25GB de bande passante par mois

## 2. Configuration initiale

### 2.1 Créer un compte Cloudinary

1. Allez sur [cloudinary.com](https://cloudinary.com/) et inscrivez-vous
2. Récupérez vos informations d'identification dans le Dashboard:
   - Cloud Name
   - API Key
   - API Secret

### 2.2 Configuration des variables d'environnement

Ajoutez ces variables dans votre fichier `.env` sur Railway:

```
CLOUDINARY_CLOUD_NAME=votre_cloud_name
CLOUDINARY_KEY=votre_api_key
CLOUDINARY_SECRET=votre_api_secret
CLOUDINARY_URL=cloudinary://${CLOUDINARY_KEY}:${CLOUDINARY_SECRET}@${CLOUDINARY_CLOUD_NAME}
```

Ou directement dans les variables d'environnement de Railway:

1. Allez dans votre projet sur Railway
2. Cliquez sur l'onglet "Variables"
3. Ajoutez les variables ci-dessus avec leurs valeurs

## 3. Migrer les médias existants vers Cloudinary

Une fois les variables d'environnement configurées, vous pouvez migrer vos médias existants vers Cloudinary:

```bash
# Via une requête API (authentification admin requise)
curl -X POST https://backend-production-b4aa.up.railway.app/api/media/migrate-to-cloudinary \
  -H "Authorization: Bearer VOTRE_TOKEN_ADMIN"
```

Ou utilisez l'endpoint suivant dans Postman ou via une autre application:
- `POST /api/media/migrate-to-cloudinary`
- Avec l'en-tête d'authentification admin

## 4. Télécharger de nouveaux médias

### 4.1 Via l'API

```bash
curl -X POST https://backend-production-b4aa.up.railway.app/api/media \
  -H "Content-Type: multipart/form-data" \
  -H "Authorization: Bearer VOTRE_TOKEN_ADMIN" \
  -F "file=@/chemin/vers/votre/image.jpg" \
  -F "title=Titre de l'image" \
  -F "type=photo" \
  -F "category_id=1"
```

### 4.2 Via le panneau d'administration

Utilisez le formulaire de téléchargement de média dans votre panneau d'administration.

## 5. Vérification de la configuration

Pour vérifier si Cloudinary est correctement configuré, exécutez le script de test:

```bash
php test-cloudinary-config.php
```

Ce script vérifie:
- La présence des variables d'environnement
- La connexion à Cloudinary
- La capacité à uploader et supprimer des fichiers

## 6. Dépannage

### 6.1 Les images ne s'affichent pas

1. Vérifiez que l'URL Cloudinary est correcte en la consultant dans la base de données
2. Vérifiez que le fichier existe bien sur Cloudinary dans votre Dashboard
3. Vérifiez que le frontend est configuré pour utiliser les URLs Cloudinary

### 6.2 Erreurs de téléchargement

Si vous rencontrez des erreurs lors du téléchargement:

1. Vérifiez que les variables d'environnement sont correctes
2. Vérifiez la taille du fichier (max 20MB par défaut)
3. Vérifiez les logs de Railway pour voir les erreurs détaillées

### 6.3 Message d'erreur "Target class [cloudinary] does not exist"

Ce message indique que le package Cloudinary n'est pas correctement installé ou configuré. Vérifiez:
1. Que le package `cloudinary-labs/cloudinary-laravel` est bien installé
2. Que le provider `CloudinaryLabs\CloudinaryLaravel\CloudinaryServiceProvider::class` est bien enregistré dans `config/app.php`
3. Que les variables d'environnement sont correctement définies

## 7. Gestion des médias dans Cloudinary

Vous pouvez gérer vos médias directement dans le Dashboard Cloudinary:

1. Connexion sur [cloudinary.com](https://cloudinary.com/)
2. Accédez à la section "Media Library"
3. Vous verrez vos médias organisés dans les dossiers:
   - `acos_football/photos`
   - `acos_football/videos`

Vous pouvez y effectuer diverses opérations: suppression, édition, création de dossiers, etc. 