<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Réinitialisation de votre mot de passe</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            max-width: 150px;
        }
        .content {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
        }
        .button {
            display: inline-block;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            margin-top: 30px;
            font-size: 12px;
            color: #777;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>ACOS Football Academy</h2>
    </div>
    
    <div class="content">
        <h3>Réinitialisation de votre mot de passe</h3>
        
        <p>Bonjour,</p>
        
        <p>Vous recevez cet email car nous avons reçu une demande de réinitialisation de mot de passe pour votre compte.</p>
        
        <p>Veuillez cliquer sur le bouton ci-dessous pour réinitialiser votre mot de passe :</p>
        
        <p style="text-align: center;">
            <a href="{{ $resetUrl }}" class="button">Réinitialiser mon mot de passe</a>
        </p>
        
        <p>Ce lien de réinitialisation expirera dans 60 minutes.</p>
        
        <p>Si vous n'avez pas demandé de réinitialisation de mot de passe, aucune action n'est requise de votre part.</p>
        
        <p>Cordialement,<br>L'équipe ACOS Football Academy</p>
    </div>
    
    <div class="footer">
        <p>© {{ date('Y') }} ACOS Football Academy. Tous droits réservés.</p>
        <p>Si vous avez des difficultés à cliquer sur le bouton "Réinitialiser mon mot de passe", copiez et collez l'URL ci-dessous dans votre navigateur web : {{ $resetUrl }}</p>
    </div>
</body>
</html>
