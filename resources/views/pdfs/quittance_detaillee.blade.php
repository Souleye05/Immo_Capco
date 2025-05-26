<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quittance Détaillée - {{ $numero_document }}</title>
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
            border-bottom: 3px solid #7c3aed;
            padding-bottom: 20px;
        }
        
        .header h1 {
            color: #7c3aed;
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
            border-left: 4px solid #7c3aed;
        }
        
        .document-number {
            font-weight: bold;
            color: #7c3aed;
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
            color: #7c3aed;
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
        
        .versements-section {
            background: #faf5ff;
            border: 2px solid #a855f7;
            border-radius: 12px;
            padding: 25px;
            margin: 30px 0;
        }
        
        .versements-section h3 {
            color: #581c87;
            font-size: 18px;
            margin-bottom: 20px;
            text-align: center;
            text-transform: uppercase;
            font-weight: bold;
        }
        
        .versements-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .versements-table th {
            background: #7c3aed;
            color: white;
            padding: 15px 10px;
            text-align: left;
            font-weight: bold;
            font-size: 14px;
            text-transform: uppercase;
        }
        
        .versements-table td {
            padding: 12px 10px;
            border-bottom: 1px solid #e5e7eb;
            color: #374151;
        }
        
        .versements-table tr:nth-child(even) {
            background: #f9fafb;
        }
        
        .versements-table tr:hover {
            background: #f3e8ff;
        }
        
        .amount-cell {
            font-weight: bold;
            color: #059669;
            text-align: right;
        }
        
        .method-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .method-om { background: #fee2e2; color: #dc2626; }
        .method-wave { background: #dbeafe; color: #2563eb; }
        .method-free-money { background: #fef3c7; color: #d97706; }
        .method-cheque { background: #fef3c7; color: #d97706; }
        .method-virement { background: #dbeafe; color: #2563eb; }
        .method-especes { background: #d1fae5; color: #059669; }
        .method-default { background: #f3f4f6; color: #6b7280; }
        
        .summary-section {
            background: #f0fdf4;
            border: 2px solid #16a34a;
            border-radius: 12px;
            padding: 25px;
            margin: 30px 0;
        }
        
        .summary-section h3 {
            color: #15803d;
            font-size: 18px;
            margin-bottom: 20px;
            text-align: center;
            text-transform: uppercase;
            font-weight: bold;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #bbf7d0;
        }
        
        .summary-row:last-child {
            border-bottom: none;
            background: #16a34a;
            color: white;
            margin: 15px -25px -25px -25px;
            padding: 20px 25px;
            border-radius: 0 0 10px 10px;
            font-weight: bold;
            font-size: 18px;
        }
        
        .summary-label {
            font-weight: 600;
            font-size: 16px;
        }
        
        .summary-value {
            font-weight: bold;
            font-size: 16px;
            color: #15803d;
        }
        
        .summary-row:last-child .summary-value {
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
            color: rgba(124, 58, 237, 0.05);
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
            <h1>Quittance Détaillée</h1>
            <p class="subtitle">Récapitulatif des versements - Solde de tout compte</p>
        </div>
        
        <div class="document-info">
            <div class="document-number">N° {{ $numero_document }}</div>
            <div class="document-date">
                Émise le : {{ $date_generation->format('d/m/Y à H:i') }}
            </div>
        </div>
        
        <div class="parties">
            <div class="partie">
                <h3>🏠 Bailleur</h3>
                <p><strong>Nom :</strong> [Nom du Bailleur]</p>
                <p><strong>Adresse :</strong> [Adresse du Bailleur]</p>
                <p><strong>Téléphone :</strong> [Téléphone]</p>
                <p><strong>Email :</strong> [Email]</p>
            </div>
            
            <div class="partie">
                <h3>👤 Locataire</h3>
                <p><strong>Nom :</strong> {{ $tenant->name }}</p>
                <p><strong>Adresse :</strong> {{ $tenant->address ?? 'Non renseignée' }}</p>
                <p><strong>Téléphone :</strong> {{ $tenant->phone ?? 'Non renseigné' }}</p>
                <p><strong>Email :</strong> {{ $tenant->email ?? 'Non renseigné' }}</p>
            </div>
        </div>
        
        <div class="versements-section">
            <h3>📋 Détail des Versements ({{ $nombre_versements }} versement{{ $nombre_versements > 1 ? 's' : '' }})</h3>
            
            <table class="versements-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Référence</th>
                        <th>Méthode</th>
                        <th>Montant</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($versements as $versement)
                    <tr>
                        <td>{{ $versement->versement_date ? \Carbon\Carbon::parse($versement->versement_date)->format('d/m/Y') : 'Non définie' }}</td>
                        <td>{{ $versement->reference ?? 'N/A' }}</td>
                        <td>
                            <span class="method-badge method-{{ strtolower(str_replace(' ', '-', $versement->payment_method ?? 'default')) }}">
                                {{ $versement->payment_method ?? 'Non spécifiée' }}
                            </span>
                        </td>
                        <td class="amount-cell">{{ number_format($versement->amount, 0, ',', ' ') }} FCFA</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <div class="summary-section">
            <h3>📊 Récapitulatif Financier</h3>
            
            <div class="summary-row">
                <span class="summary-label">Période de location :</span>
                <span class="summary-value">{{ $payment->period ?? 'Mois courant' }}</span>
            </div>
            
            <div class="summary-row">
                <span class="summary-label">Montant total dû :</span>
                <span class="summary-value">{{ number_format($montant_total, 0, ',', ' ') }} FCFA</span>
            </div>
            
            <div class="summary-row">
                <span class="summary-label">Nombre de versements :</span>
                <span class="summary-value">{{ $nombre_versements }}</span>
            </div>
            
            <div class="summary-row">
                <span class="summary-label">Première échéance :</span>
                <span class="summary-value">{{ $versements->first()->versement_date ? \Carbon\Carbon::parse($versements->first()->versement_date)->format('d/m/Y') : 'N/A' }}</span>
            </div>
            
            <div class="summary-row">
                <span class="summary-label">Dernière échéance :</span>
                <span class="summary-value">{{ $versements->last()->versement_date ? \Carbon\Carbon::parse($versements->last()->versement_date)->format('d/m/Y') : 'N/A' }}</span>
            </div>
            
            <div class="summary-row">
                <span class="summary-label">TOTAL PAYÉ :</span>
                <span class="summary-value">{{ number_format($total_versements, 0, ',', ' ') }} FCFA</span>
            </div>
        </div>
        
        <div style="text-align: center; margin: 30px 0; padding: 20px; background: #dcfce7; border-radius: 8px; border: 2px solid #16a34a;">
            <p style="color: #166534; font-size: 16px; font-weight: bold; margin: 0;">
                ✅ Le locataire est à jour de ses obligations locatives pour la période mentionnée.
            </p>
            <p style="color: #166534; font-size: 14px; margin-top: 8px;">
                Paiement effectué en {{ $nombre_versements }} versement{{ $nombre_versements > 1 ? 's' : '' }} échelonné{{ $nombre_versements > 1 ? 's' : '' }}.
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
            <p>Cette quittance détaillée a été générée automatiquement le {{ $date_generation->format('d/m/Y à H:i') }}</p>
            <p>Document valable sans signature conformément aux dispositions légales en vigueur.</p>
        </div>
    </div>
</body>
</html>