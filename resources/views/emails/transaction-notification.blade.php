<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification de Transaction - Banque API</title>
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
        .transaction-details {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #007bff;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 5px 0;
        }
        .detail-label {
            font-weight: bold;
            color: #555;
        }
        .detail-value {
            color: #333;
        }
        .amount {
            font-size: 18px;
            font-weight: bold;
            color: #28a745;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            color: #6c757d;
            font-size: 14px;
        }
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔔 Notification de Transaction</h1>
            <p>Banque API - Service de notification automatique</p>
        </div>

        <p>Bonjour <strong>{{ $client->prenom }} {{ $client->nom }}</strong>,</p>

        <p>Une transaction a été effectuée sur votre compte bancaire. Voici les détails :</p>

        <div class="transaction-details">
            <div class="detail-row">
                <span class="detail-label">Type de transaction :</span>
                <span class="detail-value">{{ ucfirst($transaction->type) }}</span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Numéro de transaction :</span>
                <span class="detail-value">{{ $transaction->numero_transaction }}</span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Montant :</span>
                <span class="detail-value amount">{{ number_format($transaction->montant, 2, ',', ' ') }} FCFA</span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Date et heure :</span>
                <span class="detail-value">{{ $transaction->date_transaction->format('d/m/Y H:i:s') }}</span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Statut :</span>
                <span class="detail-value">{{ ucfirst($transaction->statut) }}</span>
            </div>

            @if($transaction->description)
            <div class="detail-row">
                <span class="detail-label">Description :</span>
                <span class="detail-value">{{ $transaction->description }}</span>
            </div>
            @endif

            @if($transaction->compte)
            <div class="detail-row">
                <span class="detail-label">Compte concerné :</span>
                <span class="detail-value">{{ $transaction->compte->numCompte ?? 'N/A' }}</span>
            </div>
            @endif
        </div>

        @if(in_array($transaction->type, ['retrait', 'transfert', 'virement']))
        <div class="warning">
            <strong>⚠️ Information importante :</strong><br>
            Cette transaction a débité votre compte. Assurez-vous que votre solde reste suffisant pour vos autres opérations.
        </div>
        @endif

        <p>Si vous n'êtes pas à l'origine de cette transaction, veuillez contacter immédiatement notre service client.</p>

        <p>Pour consulter l'historique complet de vos transactions, connectez-vous à votre espace client.</p>

        <div class="footer">
            <p>
                Cordialement,<br>
                <strong>L'équipe Banque API</strong><br>
                Service de notification automatique<br>
                Email : support@banque-api.com<br>
                Téléphone : +221 33 123 45 67
            </p>
        </div>
    </div>
</body>
</html>