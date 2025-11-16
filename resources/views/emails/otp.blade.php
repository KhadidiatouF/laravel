<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Code OTP - Banque JAMILA</title>
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
        .otp-code {
            background-color: white;
            padding: 15px;
            border-left: 4px solid #007bff;
            margin: 20px 0;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            letter-spacing: 2px;
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
        <h1>🏦 Code OTP - Banque JAMILA</h1>
        <p>Vérification de votre compte</p>
    </div>

    <div class="content">
        <p>Bonjour <strong>{{ $client->nom }} {{ $client->prenom }}</strong>,</p>

        <p>Voici votre code de vérification pour activer votre compte bancaire :</p>

        <div class="otp-code">
            {{ $otpCode }}
        </div>

        <div class="warning">
            ⚠️ <strong>Important :</strong> Ce code expire dans 10 minutes. Ne partagez jamais ce code avec qui que ce soit.
        </div>

        <p>Si vous n'avez pas demandé ce code, veuillez ignorer cet email.</p>

        <p>Cordialement,<br>
        L'équipe de la Banque JAMILA</p>
    </div>

    <div class="footer">
        <p>Cet email a été envoyé automatiquement. Merci de ne pas y répondre.</p>
        <p>© 2025 Banque JAMILA - Tous droits réservés</p>
    </div>
</body>
</html>