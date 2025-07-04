# Guide de Configuration de Cloudinary pour ACOS Football Academy

## 1. Créer un compte Cloudinary

1. Rendez-vous sur [cloudinary.com](https://cloudinary.com/) et inscrivez-vous gratuitement
2. Une fois connecté, accédez à votre Dashboard
3. Récupérez les informations suivantes:
   - **Cloud Name**: Visible en haut à droite
   - **API Key**: Dans l'onglet "Access Keys"
   - **API Secret**: Dans l'onglet "Access Keys"

## 2. Configurer les variables d'environnement sur Railway

1. Connectez-vous à votre compte Railway
2. Accédez à votre projet ACOS Football
3. Cliquez sur le service backend (Laravel)
4. Allez dans l'onglet "Variables"
5. Ajoutez les variables suivantes:

```
CLOUDINARY_CLOUD_NAME=votre_cloud_name
CLOUDINARY_KEY=votre_api_key
CLOUDINARY_SECRET=votre_api_secret
CLOUDINARY_URL=cloudinary://${CLOUDINARY_KEY}:${CLOUDINARY_SECRET}@${CLOUDINARY_CLOUD_NAME}
```

> ⚠️ **Important**: Pour CLOUDINARY_URL, remplacez directement les variables par vos valeurs réelles. Par exemple: `cloudinary://123456789012345:abcdefghijklmnopqrstuvwxyz@moncloud`

6. Cliquez sur "Save Changes"
7. Attendez que votre application redémarre

## 3. Vérifier la configuration

Pour vérifier que tout fonctionne correctement:

1. Connectez-vous à votre serveur Railway via le terminal
2. Exécutez le script de vérification:

```bash
php check-cloudinary-env.php
```

Ce script vérifiera si toutes les variables sont correctement définies et testera la connexion à Cloudinary.

## 4. Création automatique des dossiers

Le système créera automatiquement deux dossiers dans votre compte Cloudinary:

- `acos_football/photos` pour les images
- `acos_football/videos` pour les vidéos

Vous pouvez vérifier leur existence dans votre Media Library sur le Dashboard Cloudinary.

## 5. Migration des médias existants

Pour migrer tous les médias existants vers Cloudinary:

1. Connectez-vous à l'interface d'administration
2. Allez dans "Paramètres" > onglet "Cloudinary"
3. Cliquez sur le bouton "Migrer vers Cloudinary"

Alternativement, vous pouvez exécuter cette commande dans le terminal Railway:

```bash
php artisan media:migrate-to-cloudinary
```

## Problèmes courants

### Erreur "Target class [cloudinary] does not exist"

Cette erreur indique un problème avec la configuration de Cloudinary. Vérifiez:
- Que les variables d'environnement sont correctement définies
- Que l'application a bien redémarré après l'ajout des variables

### Erreur 500 lors de l'upload

Vérifiez les logs de l'application sur Railway. Les causes possibles sont:
- Clés API incorrectes
- Problèmes de permission dans votre compte Cloudinary
- Limite de téléchargement atteinte (plan gratuit)

### Images non affichées sur le frontend

- Vérifiez que les URLs stockées en base de données commencent bien par `https://res.cloudinary.com/`
- Assurez-vous que votre frontend est configuré pour afficher les images depuis Cloudinary 