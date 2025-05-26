<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reçu de Paiement - {{ $numero_document }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            background: #fff;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            display: table;
            width: 100%;
            margin-bottom: 30px;
            border-bottom: 3px solid #2563eb;
            padding-bottom: 20px;
        }
        
        .company-info {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        
        .document-info {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            text-align: right;
        }
        
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 10px;
        }
        
        .company-details {
            font-size: 11px;
            color: #666;
            line-height: 1.5;
        }
        
        .document-title {
            font-size: 28px;
            font-weight: bold;
            color: #dc2626;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .document-number {
            font-size: 14px;
            font-weight: bold;
            color: #374151;
            margin-bottom: 5px;
        }
        
        .document-date {
            font-size: 11px;
            color: #666;
        }
        
        .content-section {
            margin: 30px 0;
        }
        
        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 15px;
            padding-bottom: 5px;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        
        .info-row {
            display: table-row;
        }
        
        .info-label {
            display: table-cell;
            width: 30%;
            padding: 8px 10px 8px 0;
            font-weight: bold;
            color: #374151;
            vertical-align: top;
        }
        
        .info-value {
            display: table-cell;
            padding: 8px 0;
            color: #1f2937;
            vertical-align: top;
        }
        
        .amount-section {
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            border: 2px solid #d1d5db;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
        }
        
        .amount-title {
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            color: #1f2937;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .amount-details {
            display: table;
            width: 100%;
        }
        
        .amount-row {
            display: table-row;
        }
        
        .amount-label {
            display: table-cell;
            width: 60%;
            padding: 12px 20px;
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            border-bottom: 1px solid #d1d5db;
        }
        
        .amount-value {
            display: table-cell;
            width: 40%;
            padding: 12px 20px;
            font-size: 14px;
            font-weight: bold;
            text-align: right;
            color: #1f2937;
            border-bottom: 1px solid #d1d5db;
        }
        
        .amount-row.total {
            background: #1e40af;
            color: white;
        }
        
        .amount-row.total .amount-label,
        .amount-row.total .amount-value {
            color: white;
            font-size: 16px;
            border-bottom: none;
        }
        
        .amount-row.reste {
            background: #dc2626;
            color: white;
        }
        
        .amount-row.reste .amount-label,
        .amount-row.reste .amount-value {
            color: white;
            font-size: 15px;
            border-bottom: none;
        }
        
        .payment-details {
            background: #fafafa;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
        }
        
        .signature-section {
            margin-top: 50px;
            display: table;
            width: 100%;
        }
        
        .signature-block {
            display: table-cell;
            width: 50%;
            text-align: center;
            vertical-align: top;
        }
        
        .signature-title {
            font-weight: bold;
            margin-bottom: 40px;
            color: #374151;
        }
        
        .signature-line {
            border-top: 1px solid #000;
            width: 200px;
            margin: 0 auto;
            padding-top: 5px;
            font-size: 11px;
            color: #666;
        }
        
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #e5e7eb;
            padding-top: 15px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            background: #fbbf24;
            color: #92400e;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .highlight {
            background: #fef3c7;
            padding: 2px 4px;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- En-tête -->
        <div class="header">
            <div class="company-info">
                <div class="company-name">GESTION IMMOBILIÈRE</div>
                <div class="company-details">
                    123 Avenue de l'Immobilier<br>
                    Dakar, Sénégal<br>
                    Tél: +221 33 XXX XX XX<br>
                    Email: contact@gestion-immo.sn
                </div>
            </div>
            <div class="document-info">
                <div class="document-title">Reçu</div>
                <div class="document-number">N° {{ $numero_document }}</div>
                <div class="document-date">
                    Émis le {{ $date_generation->format('d/m/Y à H:i') }}
                </div>
                <div style="margin-top: 10px;">
                    <span class="status-badge">Paiement Partiel</span>
                </div>
            </div>
        </div>

        <!-- Informations du locataire -->
        <div class="content-section">
            <div class="section-title">Informations du Locataire</div>
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-label">Nom complet :</div>
                    <div class="info-value">{{ $tenant->name }} {{ $tenant->last_name }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Téléphone :</div>
                    <div class="info-value">{{ $tenant->phone ?? 'Non renseigné' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Email :</div>
                    <div class="info-value">{{ $tenant->email ?? 'Non renseigné' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Propriété :</div>
                    <div class="info-value">{{ $payment->property->name ?? 'Non spécifiée' }}</div>
                </div>
            </div>
        </div>

        <!-- Détails du paiement -->
        <div class="content-section">
            <div class="section-title">Détails du Versement</div>
            <div class="payment-details">
                <div class="info-grid">
                    <div class="info-row">
                        <div class="info-label">Référence du versement :</div>
                        <div class="info-value"><span class="highlight">{{ $versement->reference }}</span></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Date du versement :</div>
                        <div class="info-value">{{ $versement->date_versement ? \Carbon\Carbon::parse($versement->date_versement)->format('d/m/Y') : $date_generation->format('d/m/Y') }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Mode de paiement :</div>
                        <div class="info-value">{{ $versement->payment_method }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Période concernée :</div>
                        <div class="info-value">{{ $payment->period ?? 'Non spécifiée' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Récapitulatif des montants -->
        <div class="amount-section">
            <div class="amount-title">Récapitulatif des Montants</div>
            <div class="amount-details">
                <div class="amount-row">
                    <div class="amount-label">Montant Total de la Facture :</div>
                    <div class="amount-value">{{ number_format($montant_total, 0, ',', ' ') }} FCFA</div>
                </div>
                <div class="amount-row">
                    <div class="amount-label">Montant Déjà Versé (cumulé) :</div>
                    <div class="amount-value">{{ number_format($montant_cumule, 0, ',', ' ') }} FCFA</div>
                </div>
                <div class="amount-row total">
                    <div class="amount-label">Montant de ce Versement :</div>
                    <div class="amount-value">{{ number_format($montant_verse, 0, ',', ' ') }} FCFA</div>
                </div>
                <div class="amount-row reste">
                    <div class="amount-label">Montant Restant à Payer :</div>
                    <div class="amount-value">{{ number_format($montant_restant, 0, ',', ' ') }} FCFA</div>
                </div>
            </div>
        </div>

        @if($montant_restant > 0)
        <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 6px; padding: 15px; margin: 20px 0; text-align: center;">
            <strong style="color: #dc2626;">
                ⚠️ ATTENTION : Il reste {{ number_format($montant_restant, 0, ',', ' ') }} FCFA à régler pour solder cette facture.
            </strong>
        </div>
        @endif

        <!-- Signatures -->
        <div class="signature-section">
            <div class="signature-block">
                <div class="signature-title">Signature du Locataire</div>
                <div class="signature-line">
                    {{ $tenant->first_name }} {{ $tenant->last_name }}
                </div>
            </div>
            <div class="signature-block">
                <div class="signature-title">Signature du Bailleur</div>
                <div class="signature-line">
                    Cachet et Signature
                </div>
            </div>
        </div>

        <!-- Pied de page -->
        <div class="footer">
            <p><strong>Reçu de paiement partiel généré automatiquement</strong></p>
            <p>Ce document certifie la réception du montant indiqué ci-dessus.</p>
            <p>Document généré le {{ $date_generation->format('d/m/Y à H:i:s') }}</p>
        </div>
    </div>
</body>
</html>