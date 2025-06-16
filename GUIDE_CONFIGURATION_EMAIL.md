# Guide de configuration de Gmail pour l'envoi d'emails

Si vos emails sont bien enregistrés dans la base de données mais ne sont pas reçus dans la boîte mail du destinataire, suivez ce guide pour configurer correctement Gmail.

## 1. Activer l'authentification à deux facteurs

Google exige l'authentification à deux facteurs pour générer des mots de passe d'application:

1. Connectez-vous à votre compte Gmail
2. Allez dans "Gérer votre compte Google"
3. Dans la section "Sécurité", cliquez sur "Validation en deux étapes"
4. Suivez les instructions pour l'activer

## 2. Générer un mot de passe d'application

Une fois l'authentification à deux facteurs activée:

1. Retournez dans "Sécurité"
2. Cliquez sur "Mots de passe des applications" (sous "Validation en deux étapes")
3. Sélectionnez "Application" → "Autre (nom personnalisé)"
4. Entrez un nom comme "ACOS Football Academy"
5. Cliquez sur "Générer"
6. **Copiez le mot de passe de 16 caractères généré**

## 3. Mettre à jour votre fichier .env

Modifiez votre fichier `.env` avec ces paramètres:

```
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=votre_email@gmail.com
MAIL_PASSWORD=votre_mot_de_passe_d_application  # Le mot de passe de 16 caractères généré
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=votre_email@gmail.com
MAIL_FROM_NAME="ACOS Football Academy"
```

## 4. Redémarrer votre serveur Laravel

Après avoir modifié le fichier `.env`, redémarrez votre serveur Laravel:

```
php artisan cache:clear
php artisan config:clear
php artisan serve
```

## 5. Tester l'envoi d'email

Utilisez la route de test que nous avons créée:

```
http://localhost:8000/api/test-email/votre_email_test@example.com
```

## Problèmes courants

1. **Erreur "Username and Password not accepted"**:
   - Assurez-vous d'utiliser un mot de passe d'application et non votre mot de passe Gmail normal
   - Vérifiez que l'adresse email est correctement écrite

2. **Erreur "Connection could not be established with host"**:
   - Vérifiez que votre hébergeur ou pare-feu ne bloque pas le port SMTP (587)
   - Essayez le port alternatif 465 avec encryption="ssl"

3. **Emails dans le dossier spam**:
   - C'est normal pour les tests, vérifiez votre dossier spam

4. **Limite d'envoi Gmail**:
   - Gmail limite à environ 500 emails par jour pour les comptes personnels
   - Pour des volumes plus importants, utilisez un service comme Mailgun, SendGrid ou Amazon SES 