<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quittance de Loyer - {{ $numero_document }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            font-size: 14px;
            line-height: 1.6;
            color: #333;
            background: #fff;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #2563eb;
            padding-bottom: 20px;
        }
        
        .header h1 {
            color: #2563eb;
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .header .subtitle {
            color: #666;
            font-size: 16px;
            font-style: italic;
        }
        
        .document-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #2563eb;
        }
        
        .document-number {
            font-weight: bold;
            color: #2563eb;
            font-size: 16px;
        }
        
        .document-date {
            color: #666;
            font-size: 14px;
        }
        
        .parties {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            gap: 30px;
        }
        
        .partie {
            flex: 1;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 20px;
        }
        
        .partie h3 {
            color: #2563eb;
            font-size: 16px;
            margin-bottom: 15px;
            text-transform: uppercase;
            font-weight: bold;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 8px;
        }
        
        .partie p {
            margin: 8px 0;
            color: #374151;
        }
        
        .partie strong {
            color: #111827;
        }
        
        .payment-details {
            background: #f0f9ff;
            border: 2px solid #0ea5e9;
            border-radius: 12px;
            padding: 25px;
            margin: 30px 0;
        }
        
        .payment-details h3 {
            color: #0c4a6e;
            font-size: 18px;
            margin-bottom: 20px;
            text-align: center;
            text-transform: uppercase;
            font-weight: bold;
        }
        
        .amount-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #bae6fd;
        }
        
        .amount-row:last-child {
            border-bottom: none;
            background: #0ea5e9;
            color: white;
            margin: 15px -25px -25px -25px;
            padding: 20px 25px;
            border-radius: 0 0 10px 10px;
            font-weight: bold;
            font-size: 18px;
        }
        
        .amount-label {
            font-weight: 600;
            font-size: 16px;
        }
        
        .amount-value {
            font-weight: bold;
            font-size: 16px;
            color: #0c4a6e;
        }
        
        .amount-row:last-child .amount-value {
            color: white;
        }
        
        .signature-section {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
        }
        
        .signature-box {
            width: 45%;
            text-align: center;
            padding: 20px;
            border: 2px dashed #d1d5db;
            border-radius: 8px;
            background: #fafafa;
        }
        
        .signature-title {
            font-weight: bold;
            color: #374151;
            margin-bottom: 50px;
            text-transform: uppercase;
            font-size: 14px;
        }
        
        .signature-line {
            border-top: 2px solid #6b7280;
            margin-top: 50px;
            padding-top: 10px;
            color: #6b7280;
            font-size: 12px;
        }
        
        .footer {
            margin-top: 40px;
            text-align: center;
            color: #6b7280;
            font-size: 12px;
            border-top: 1px solid #e5e7eb;
            padding-top: 20px;
        }
        
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 100px;
            color: rgba(37, 99, 235, 0.05);
            font-weight: bold;
            z-index: -1;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    <div class="watermark">QUITTANCE</div>
    
    <div class="container">
        <div class="header">
            <h1>Quittance de Loyer</h1>
            <p class="subtitle">Reçu pour solde de tout compte</p>
        </div>
        
        <div class="document-info">
            <div class="document-number">N° {{ $numero_document }}</div>
            <div class="document-date">
                Émise le : {{ $date_generation->format('d/m/Y à H:i') }}
            </div>
        </div>
        
        <div class="parties">
            
            <div class="partie">
                <h3>👤 Locataire</h3>
                <p><strong>Nom :</strong> {{ $tenant->name }}</p>
                <p><strong>Adresse :</strong> {{ $tenant->address ?? 'Non renseignée' }}</p>
                <p><strong>Téléphone :</strong> {{ $tenant->phone ?? 'Non renseigné' }}</p>
                <p><strong>Email :</strong> {{ $tenant->email ?? 'Non renseigné' }}</p>
            </div>
        </div>
        
        <div class="payment-details">
            <h3>💰 Détails du Paiement</h3>
            
            <div class="amount-row">
                <span class="amount-label">Période de location :</span>
                <span class="amount-value">{{ $payment->period ?? 'Mois courant' }}</span>
            </div>
            
            <div class="amount-row">
                <span class="amount-label">Date de paiement :</span>
                <span class="amount-value">{{ $payment->created_at->format('d/m/Y') }}</span>
            </div>
            
            <div class="amount-row">
                <span class="amount-label">Méthode de paiement :</span>
                <span class="amount-value">{{ $payment->payment_method ?? 'Non spécifiée' }}</span>
            </div>
            
            <div class="amount-row">
                <span class="amount-label">Montant Total Payé :</span>
                <span class="amount-value">{{ number_format($montant_total, 0, ',', ' ') }} FCFA</span>
            </div>
        </div>
        
        <div style="text-align: center; margin: 30px 0; padding: 20px; background: #dcfce7; border-radius: 8px; border: 2px solid #16a34a;">
            <p style="color: #166534; font-size: 16px; font-weight: bold; margin: 0;">
                ✅ Le locataire est à jour de ses obligations locatives pour la période mentionnée.
            </p>
        </div>
        
        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-title">Signature du Bailleur</div>
                <div class="signature-line">Nom et Signature</div>
            </div>
            
            <div class="signature-box">
                <div class="signature-title">Signature du Locataire</div>
                <div class="signature-line">Nom et Signature</div>
            </div>
        </div>
        
        <div class="footer">
            <p>Cette quittance a été générée automatiquement le {{ $date_generation->format('d/m/Y à H:i') }}</p>
            <p>Document valable sans signature conformément aux dispositions légales en vigueur.</p>
        </div>
    </div>
</body>
</html>