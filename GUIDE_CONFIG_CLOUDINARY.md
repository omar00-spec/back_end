# Guide de configuration Cloudinary pour ACOS Football

Ce guide explique comment configurer et utiliser Cloudinary pour le stockage des médias dans l'application ACOS Football.

## Problème rencontré

L'erreur `Trying to access array offset on value of type null` lors de l'upload sur Cloudinary indique que la configuration est incorrecte ou que les variables d'environnement ne sont pas correctement définies.

## Vérification de la configuration

1. **Accéder à l'outil de diagnostic**

   Ouvrez votre navigateur et accédez à l'URL :
   ```
   https://backend-production-b4aa.up.railway.app/cloudinary-diagnostic.php
   ```
   
   Cet outil vérifiera automatiquement :
   - La présence des variables d'environnement Cloudinary
   - La validité des clés d'API
   - La disponibilité du SDK Cloudinary
   - La possibilité d'uploader un fichier de test

2. **Vérifier l'état de l'API**

   Vous pouvez également utiliser l'endpoint API de diagnostic :
   ```
   https://backend-production-b4aa.up.railway.app/api/diagnostic/cloudinary
   ```

## Configuration de Cloudinary sur Railway

1. **Créer un compte Cloudinary**

   Si ce n'est pas déjà fait, créez un compte sur [Cloudinary](https://cloudinary.com/).

2. **Récupérer les informations d'API**

   Dans votre dashboard Cloudinary, trouvez les informations suivantes :
   - Cloud Name
   - API Key
   - API Secret

3. **Configurer les variables d'environnement sur Railway**

   Accédez à votre projet Railway et ajoutez les variables d'environnement suivantes :

   ```
   CLOUDINARY_CLOUD_NAME=votre_cloud_name
   CLOUDINARY_KEY=votre_api_key
   CLOUDINARY_SECRET=votre_api_secret
   CLOUDINARY_URL=cloudinary://votre_api_key:votre_api_secret@votre_cloud_name
   ```

   > **Important** : Le format de CLOUDINARY_URL doit être exactement : `cloudinary://API_KEY:API_SECRET@CLOUD_NAME`

4. **Redémarrer le service**

   Après avoir ajouté les variables, redémarrez votre service sur Railway.

## Migrer les médias existants vers Cloudinary

Une fois la configuration vérifiée, vous pouvez migrer tous vos médias existants vers Cloudinary :

1. Accédez à l'URL suivante :
   ```
   https://backend-production-b4aa.up.railway.app/api/media/migrate-to-cloudinary
   ```

2. Le système tentera de migrer tous les médias existants vers Cloudinary.

3. Consultez les logs Laravel pour voir le détail des opérations.

## Test d'upload manuel

Pour tester manuellement l'upload vers Cloudinary :

1. Accédez au panneau d'administration
2. Allez dans la section Média
3. Essayez d'ajouter une nouvelle photo ou vidéo

Si l'erreur persiste après avoir configuré correctement les variables d'environnement, vérifiez les logs Laravel sur Railway.

## Structure des dossiers sur Cloudinary

Les médias sont organisés dans les dossiers suivants sur Cloudinary :

- `acos_football/photos` : pour les images
- `acos_football/videos` : pour les vidéos
- `acos_football/test` : pour les fichiers de test (supprimés automatiquement)

## Consulter les logs d'erreurs

En cas d'erreur, consultez les logs Laravel sur Railway pour obtenir des détails :

1. Accédez à votre dashboard Railway
2. Sélectionnez le service backend
3. Consultez les logs
4. Recherchez les erreurs liées à Cloudinary

## Support

Pour toute assistance supplémentaire, contactez l'équipe de développement.
