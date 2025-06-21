<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contrat de Location - {{ $contract->contract_number }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        
        .header h1 {
            font-size: 24px;
            margin: 0;
            color: #2c3e50;
        }
        
        .contract-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .section {
            margin-bottom: 25px;
        }
        
        .section h2 {
            font-size: 16px;
            color: #2c3e50;
            border-bottom: 1px solid #bdc3c7;
            padding-bottom: 5px;
            margin-bottom: 15px;
        }
        
        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }
        
        .info-row {
            display: table-row;
        }
        
        .info-label {
            display: table-cell;
            font-weight: bold;
            width: 200px;
            padding: 5px 10px 5px 0;
        }
        
        .info-value {
            display: table-cell;
            padding: 5px 0;
        }
        
        .financial-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .financial-table th,
        .financial-table td {
            border: 1px solid #bdc3c7;
            padding: 10px;
            text-align: left;
        }
        
        .financial-table th {
            background-color: #ecf0f1;
            font-weight: bold;
        }
        
        .signatures {
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
        
        .signature-line {
            border-top: 1px solid #333;
            margin-top: 60px;
            padding-top: 5px;
        }
        
        .footer {
            position: fixed;
            bottom: 20px;
            left: 20px;
            right: 20px;
            text-align: center;
            font-size: 10px;
            color: #7f8c8d;
            border-top: 1px solid #bdc3c7;
            padding-top: 10px;
        }
        
        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>CONTRAT DE LOCATION</h1>
        <p><strong>{{ $contract->contract_number }}</strong></p>
    </div>

    <div class="contract-info">
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Date du contrat :</div>
                <div class="info-value">{{ $contract->created_at->format('d/m/Y') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Statut :</div>
                <div class="info-value">{{ $contract->status->label() }}</div>
            </div>
        </div>
    </div>

    <div class="section">
        <h2>PARTIES AU CONTRAT</h2>
        
        <h3>Le Bailleur :</h3>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Nom/Société :</div>
                <div class="info-value">[NOM DU BAILLEUR]</div>
            </div>
            <div class="info-row">
                <div class="info-label">Adresse :</div>
                <div class="info-value">[ADRESSE DU BAILLEUR]</div>
            </div>
        </div>

        <h3>Le Locataire :</h3>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Nom complet :</div>
                <div class="info-value">{{ $contract->tenant->name }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Téléphone :</div>
                <div class="info-value">{{ $contract->tenant->phone ?? 'Non renseigné' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Adresse :</div>
                <div class="info-value">{{ $contract->tenant->address ?? 'Non renseignée' }}</div>
            </div>
        </div>
    </div>

    <div class="section">
        <h2>BIEN LOUÉ</h2>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Propriété :</div>
                <div class="info-value">{{ $contract->flat->property->name ?? 'Non renseignée' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Type d'appartement :</div>
                <div class="info-value">{{ $contract->flat->type }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Adresse de l'appartement :</div>
                <div class="info-value">{{ $contract->flat->property->address }}</div>
            </div>
        </div>
    </div>

    <div class="section">
        <h2>CONDITIONS FINANCIÈRES</h2>
        <table class="financial-table">
            <tr>
                <th>Description</th>
                <th>Montant</th>
            </tr>
            <tr>
                <td>Loyer mensuel</td>
                <td>{{ number_format($contract->monthly_rent) }} FCFA</td>
            </tr>
            @if($contract->cautions)
            <tr>
                <td>Dépôt de garantie</td>
                <td>{{ number_format($contract->cautions) }} FCFA</td>
            </tr>
            @endif
        </table>
    </div>

    <div class="section">
        <h2>DURÉE DU CONTRAT</h2>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Date de début :</div>
                <div class="info-value">{{ $contract->start_date->format('d/m/Y') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Date de fin :</div>
                <div class="info-value">{{ $contract->end_date->format('d/m/Y') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Durée :</div>
                <div class="info-value">{{ $contract->start_date->diffInMonths($contract->end_date) }} mois</div>
            </div>
            <div class="info-row">
                <div class="info-label">Préavis :</div>
                <div class="info-value">{{ $contract->notice_period_days }} jours</div>
            </div>
        </div>
    </div>

    @if($contract->auto_renewal)
    <div class="section">
        <h2>RENOUVELLEMENT</h2>
        <p>Ce contrat sera automatiquement renouvelé pour une durée de {{ $contract->renewal_duration_months ?? 12 }} mois, sauf préavis contraire.</p>
    </div>
    @endif

    @if($contract->notes)
    <div class="section">
        <h2>NOTES ET REMARQUES</h2>
        <p>{{ $contract->notes }}</p>
    </div>
    @endif

    <div class="signatures">
        <div class="signature-block">
            <p><strong>Le Bailleur</strong></p>
            <div class="signature-line">
                Signature et date
            </div>
        </div>
        <div class="signature-block">
            <p><strong>Le Locataire</strong></p>
            <div class="signature-line">
                {{ $contract->tenant->name }}<br>
                Signature et date
            </div>
        </div>
    </div>

    <div class="footer">
        <p>Contrat généré le {{ now()->format('d/m/Y à H:i') }} - {{ $contract->contract_number }}</p>
    </div>
</body>
</html>