<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Réponse à votre message</title>
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
            background-color: #1976d2;
            color: white;
            padding: 15px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            padding: 20px;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 5px 5px;
        }
        .original-message {
            background-color: #f5f5f5;
            padding: 15px;
            margin: 15px 0;
            border-left: 4px solid #1976d2;
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
        <h1>ACOS Football Academy</h1>
    </div>
    
    <div class="content">
        <p>Bonjour {{ $name }},</p>
        
        <p>Merci d'avoir contacté l'ACOS Football Academy. Nous avons bien reçu votre message et nous vous répondons ci-dessous.</p>
        
        <div class="original-message">
            <strong>Votre message :</strong>
            <p>{{ $originalMessage }}</p>
        </div>
        
        <strong>Notre réponse :</strong>
        <p>{{ $response }}</p>
        
        <p>Si vous avez d'autres questions, n'hésitez pas à nous contacter à nouveau.</p>
        
        <p>Cordialement,<br>
        L'équipe ACOS Football Academy</p>
    </div>
    
    <div class="footer">
        <p>Ce message est envoyé automatiquement, merci de ne pas y répondre directement.</p>
        <p>© {{ date('Y') }} ACOS Football Academy - Tous droits réservés</p>
    </div>
</body>
</html> 