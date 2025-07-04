# Guide d'upload de vidéos vers Cloudinary

Ce guide explique comment configurer et utiliser la fonctionnalité d'upload de vidéos vers Cloudinary dans l'application ACOS Football Academy.

## 1. Configuration préalable

Avant de pouvoir uploader des vidéos, assurez-vous que Cloudinary est correctement configuré :

1. Vérifiez que les variables d'environnement suivantes sont définies dans votre fichier `.env` ou dans les variables d'environnement de Railway :

```
CLOUDINARY_CLOUD_NAME=votre_cloud_name
CLOUDINARY_KEY=votre_api_key
CLOUDINARY_SECRET=votre_api_secret
CLOUDINARY_URL=cloudinary://${CLOUDINARY_KEY}:${CLOUDINARY_SECRET}@${CLOUDINARY_CLOUD_NAME}
```

2. Vérifiez que votre compte Cloudinary est configuré pour accepter les vidéos (c'est généralement le cas par défaut).

## 2. Uploader des vidéos via l'API

### 2.1 Endpoint d'upload

Pour uploader une vidéo, utilisez l'endpoint suivant :

```
POST /api/media
```

### 2.2 Paramètres requis

- `file` : Le fichier vidéo à uploader (format MP4, MOV, AVI, etc.)
- `title` : Le titre de la vidéo
- `type` : Doit être défini à `video`
- `category_id` : L'ID de la catégorie à laquelle associer la vidéo (optionnel)

### 2.3 Exemple de requête avec cURL

```bash
curl -X POST https://backend-production-b4aa.up.railway.app/api/media \
  -H "Content-Type: multipart/form-data" \
  -H "Authorization: Bearer VOTRE_TOKEN_ADMIN" \
  -F "file=@/chemin/vers/votre/video.mp4" \
  -F "title=Titre de la vidéo" \
  -F "type=video" \
  -F "category_id=1"
```

### 2.4 Exemple de requête avec JavaScript/Axios

```javascript
const formData = new FormData();
formData.append('file', videoFile); // videoFile est un objet File ou Blob
formData.append('title', 'Titre de la vidéo');
formData.append('type', 'video');
formData.append('category_id', '1');

axios.post('https://backend-production-b4aa.up.railway.app/api/media', formData, {
  headers: {
    'Content-Type': 'multipart/form-data',
    'Authorization': 'Bearer VOTRE_TOKEN_ADMIN'
  }
})
.then(response => {
  console.log('Vidéo uploadée avec succès:', response.data);
})
.catch(error => {
  console.error('Erreur lors de l\'upload:', error);
});
```

## 3. Limites et considérations

### 3.1 Taille maximale des fichiers

La taille maximale des fichiers vidéo est configurée à 200 MB. Si vous avez besoin d'uploader des fichiers plus volumineux, vous devrez ajuster la configuration PHP et Cloudinary.

### 3.2 Formats supportés

Les formats vidéo suivants sont supportés :
- MP4
- MOV
- AVI
- WMV
- FLV
- WEBM
- MKV
- 3GP
- MPEG
- MPG
- M4V

### 3.3 Traitement des vidéos

Après l'upload, Cloudinary traite automatiquement les vidéos pour :
- Optimiser la qualité
- Créer différentes versions pour le streaming adaptatif
- Générer des miniatures

Ce traitement peut prendre quelques minutes selon la taille et la complexité de la vidéo.

## 4. Affichage des vidéos

Pour afficher une vidéo dans votre application React, utilisez l'URL stockée dans la base de données :

```jsx
<video controls width="100%">
  <source src={videoUrl} type="video/mp4" />
  Votre navigateur ne supporte pas la lecture de vidéos.
</video>
```

Ou avec le player Cloudinary pour plus d'options :

```jsx
import { Video } from 'cloudinary-react';

<Video
  cloudName="votre_cloud_name"
  publicId="acos_football/videos/nom_de_la_video"
  controls
  width="100%"
/>
```

## 5. Dépannage

### 5.1 La vidéo ne s'upload pas

Vérifiez :
- La taille du fichier (max 200 MB)
- Le format du fichier
- Les logs de l'application pour des erreurs détaillées

### 5.2 La vidéo s'upload mais ne s'affiche pas

Vérifiez :
- Que l'URL stockée dans la base de données est correcte
- Que la vidéo est bien visible dans votre dashboard Cloudinary
- Les paramètres de sécurité de Cloudinary

### 5.3 La qualité de la vidéo est mauvaise

Cloudinary optimise automatiquement les vidéos. Si la qualité n'est pas satisfaisante, vous pouvez ajuster les paramètres de transformation dans le code du MediaController.

## 6. Support et ressources

- [Documentation Cloudinary sur les vidéos](https://cloudinary.com/documentation/video_manipulation_and_delivery)
- [Documentation du SDK Cloudinary pour PHP](https://cloudinary.com/documentation/php_integration)
- [Forum de support Cloudinary](https://support.cloudinary.com/) 