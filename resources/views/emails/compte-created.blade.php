<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compte bancaire créé</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #007bff;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #007bff;
            margin: 0;
            font-size: 24px;
        }
        .content {
            margin-bottom: 30px;
        }
        .account-info {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #007bff;
        }
        .account-info h3 {
            margin-top: 0;
            color: #007bff;
        }
        .credentials {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .credentials strong {
            color: #856404;
        }
        .footer {
            text-align: center;
            color: #666;
            font-size: 12px;
            border-top: 1px solid #ddd;
            padding-top: 20px;
            margin-top: 30px;
        }
        .warning {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #f5c6cb;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏦 Banque Example</h1>
            <p>Votre compte bancaire a été créé avec succès !</p>
        </div>

        <div class="content">
            <p>Bonjour <strong>{{ $client->prenom }} {{ $client->nom }}</strong>,</p>

            <p>Nous avons le plaisir de vous informer que votre compte bancaire a été créé avec succès dans notre système.</p>

            <div class="account-info">
                <h3>📋 Informations de votre compte</h3>
                <p><strong>Numéro de compte :</strong> {{ $compte->numCompte }}</p>
                <p><strong>Type de compte :</strong> {{ ucfirst($compte->type) }}</p>
                <p><strong>Devise :</strong> FCFA</p>
                <p><strong>Date de création :</strong> {{ $compte->date_creation->format('d/m/Y H:i') }}</p>
                <p><strong>Statut :</strong>
                    @if($compte->statut === 'actif')
                        <span style="color: #28a745;">✅ Actif</span>
                    @elseif($compte->statut === 'bloqué')
                        <span style="color: #dc3545;">🔒 Bloqué</span>
                    @else
                        <span style="color: #6c757d;">{{ ucfirst($compte->statut) }}</span>
                    @endif
                </p>

                @if($compte->type === 'epargne' && $compte->date_debut_bloquage)
                    <p><strong>Date début blocage :</strong> {{ \Carbon\Carbon::parse($compte->date_debut_bloquage)->format('d/m/Y') }}</p>
                    <p><strong>Date fin blocage :</strong> {{ \Carbon\Carbon::parse($compte->date_fin_bloquage)->format('d/m/Y') }}</p>
                    <p><strong>Durée de blocage :</strong> {{ $compte->duree_bloquage_jours }} jours</p>
                @endif
            </div>

            @if($motDePasse)
            <div class="credentials">
                <h4>🔐 Vos identifiants de connexion</h4>
                <p><strong>Email :</strong> {{ $client->email }}</p>
                <p><strong>Mot de passe temporaire :</strong> {{ $motDePasse }}</p>
                <p style="color: #856404; font-size: 14px;">
                    ⚠️ <strong>Important :</strong> Veuillez changer ce mot de passe lors de votre première connexion.
                </p>
            </div>
            @endif

            @if($otpCode)
            <div class="credentials">
                <h4>📱 Code d'activation par SMS</h4>
                <p><strong>Code OTP :</strong> {{ $otpCode }}</p>
                <p><strong>Numéro de téléphone :</strong> {{ $client->telephone }}</p>
                <p style="color: #856404; font-size: 14px;">
                    📨 Ce code vous a également été envoyé par SMS. Il est valide pendant 2 minutes.
                </p>
            </div>
            @endif

            <div class="warning">
                <h4>🔔 Activation du compte</h4>
                <p>Pour activer votre compte, utilisez le code OTP envoyé par SMS ou visible ci-dessus.</p>
                <p>Appelez l'endpoint <code>/verify-otp</code> avec votre numéro de téléphone et le code.</p>
            </div>

            <p>Si vous avez des questions concernant votre compte, n'hésitez pas à nous contacter.</p>

            <p>Cordialement,<br>
            <strong>L'équipe Banque Example</strong></p>
        </div>

        <div class="footer">
            <p>Cet email a été envoyé automatiquement. Merci de ne pas y répondre.</p>
            <p>&copy; 2025 Banque Example. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>