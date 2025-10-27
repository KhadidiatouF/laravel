<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenue à la Banque Example</title>
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
            background-color: #007bff;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 0 0 5px 5px;
        }
        .credentials {
            background-color: white;
            padding: 15px;
            border-left: 4px solid #007bff;
            margin: 20px 0;
        }
        .footer {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            font-size: 12px;
            color: #6c757d;
        }
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 10px;
            border-radius: 4px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🏦 Bienvenue à la Banque Example</h1>
        <p>Votre compte a été créé avec succès</p>
    </div>

    <div class="content">
        <p>Bonjour <strong>{{ $client->nom }} {{ $client->prenom }}</strong>,</p>

        <p>Félicitations ! Votre compte bancaire a été créé avec succès. Voici vos informations de connexion :</p>

        <div class="credentials">
            <h3>📋 Vos informations de connexion</h3>
            <p><strong>Email :</strong> {{ $client->email }}</p>
            <p><strong>Mot de passe temporaire :</strong> {{ $password }}</p>
            <p><strong>Code de vérification :</strong> {{ $code }}</p>
        </div>

        <div class="warning">
            ⚠️ <strong>Important :</strong> Ce mot de passe est temporaire. Nous vous recommandons de le changer lors de votre première connexion.
        </div>

        <p>Pour accéder à votre compte, veuillez :</p>
        <ol>
            <li>Visiter notre application mobile ou site web</li>
            <li>Utiliser votre email et le mot de passe ci-dessus</li>
            <li>Saisir le code de vérification : <strong>{{ $code }}</strong></li>
            <li>Changer votre mot de passe immédiatement</li>
        </ol>

        <p>Si vous avez des questions, n'hésitez pas à contacter notre service client.</p>

        <p>Cordialement,<br>
        L'équipe de la Banque Example</p>
    </div>

    <div class="footer">
        <p>Cet email a été envoyé automatiquement. Merci de ne pas y répondre.</p>
        <p>© 2025 Banque Example - Tous droits réservés</p>
    </div>
</body>
</html>