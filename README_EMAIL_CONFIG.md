# Configuration de l'envoi d'emails pour les réponses aux contacts

Pour activer l'envoi d'emails de réponse aux contacts, vous devez configurer les paramètres d'email dans votre fichier `.env`. Suivez ces étapes :

## 1. Configuration avec Mailtrap (pour les tests)

Mailtrap est un service qui permet de tester l'envoi d'emails sans réellement les envoyer aux destinataires. C'est parfait pour le développement et les tests.

1. Créez un compte sur [Mailtrap](https://mailtrap.io/) si vous n'en avez pas déjà un
2. Accédez à votre boîte de réception de test
3. Copiez les informations d'identification SMTP
4. Ajoutez ces informations à votre fichier `.env` :

```
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=votre_username_mailtrap
MAIL_PASSWORD=votre_password_mailtrap
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="contact@acosfootball.com"
MAIL_FROM_NAME="ACOS Football Academy"
```

## 2. Configuration avec Gmail (pour la production)

Pour utiliser Gmail comme service d'envoi d'emails :

1. Créez un compte Gmail ou utilisez un compte existant
2. Activez l'authentification à deux facteurs sur ce compte
3. Générez un "mot de passe d'application" spécifique pour votre application
4. Configurez votre fichier `.env` comme suit :

```
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=votre_email@gmail.com
MAIL_PASSWORD=votre_mot_de_passe_d_application
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="votre_email@gmail.com"
MAIL_FROM_NAME="ACOS Football Academy"
```

## 3. Vérification de la configuration

Pour vérifier que votre configuration fonctionne correctement, vous pouvez exécuter cette commande Artisan :

```
php artisan tinker
```

Puis testez l'envoi d'un email :

```php
Mail::raw('Test d\'envoi d\'email', function($message) {
    $message->to('votre_email@example.com')
            ->subject('Test d\'envoi d\'email');
});
```

## 4. Dépannage

Si vous rencontrez des problèmes :

1. Vérifiez les logs d'erreur dans `storage/logs/laravel.log`
2. Assurez-vous que les informations d'identification sont correctes
3. Si vous utilisez Gmail, vérifiez que le "mot de passe d'application" est correctement configuré
4. Vérifiez que votre fournisseur d'hébergement n'a pas bloqué le port SMTP

## 5. Utilisation dans le code

Le système est déjà configuré pour utiliser cette configuration. Lorsqu'un administrateur répond à un contact, la classe `App\Mail\ContactResponse` est utilisée pour envoyer un email formaté au contact. 