<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quittance de Reversement</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        :root {
            --primary-color: #2563eb;
            --secondary-color: #f8fafc;
            --accent-color: #0f172a;
            --text-color: #334155;
            --light-gray: #e2e8f0;
            --success-color: #10b981;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            color: var(--text-color);
            background-color: #fff;
            line-height: 1.6;
            padding: 0;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05), 0 1px 3px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            overflow: hidden;
        }
        
        .header {
            background-color: var(--primary-color);
            color: white;
            padding: 20px 30px;
            position: relative;
        }
        
        .logo {
            position: absolute;
            top: 20px;
            right: 30px;
            font-weight: 700;
            font-size: 24px;
            letter-spacing: 1px;
        }
        
        .title {
            font-size: 26px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .subtitle {
            font-size: 14px;
            font-weight: 400;
            opacity: 0.9;
        }
        
        .content {
            padding: 30px;
        }
        
        .section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--light-gray);
        }
        
        .section:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--accent-color);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        
        .section-title::before {
            content: "";
            display: inline-block;
            width: 4px;
            height: 18px;
            background-color: var(--primary-color);
            margin-right: 10px;
            border-radius: 2px;
        }
        
        .grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        
        .info-item {
            margin-bottom: 12px;
        }
        
        .info-label {
            font-size: 13px;
            color: #64748b;
            display: block;
            margin-bottom: 2px;
        }
        
        .info-value {
            font-size: 15px;
            font-weight: 500;
            color: var(--accent-color);
        }
        
        .highlight {
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .amount-box {
            background-color: var(--secondary-color);
            border-radius: 8px;
            padding: 15px;
            margin-top: 5px;
        }
        
        .amount-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .amount-label {
            font-size: 14px;
        }
        
        .amount-value {
            font-weight: 600;
            font-size: 15px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            border-top: 1px dashed var(--light-gray);
            padding-top: 10px;
            margin-top: 10px;
        }
        
        .total-label {
            font-weight: 600;
            font-size: 15px;
        }
        
        .total-value {
            font-weight: 700;
            font-size: 16px;
            color: var(--primary-color);
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 14px;
        }
        
        .table th {
            background-color: var(--secondary-color);
            text-align: left;
            padding: 12px 15px;
            font-weight: 500;
            color: var(--accent-color);
            border-bottom: 1px solid var(--light-gray);
        }
        
        .table td {
            padding: 12px 15px;
            border-bottom: 1px solid var(--light-gray);
        }
        
        .table tr:last-child td {
            border-bottom: none;
        }
        
        .status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
        }
        
        .status-success {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }
        
        .footer {
            padding: 20px 30px;
            text-align: center;
            font-size: 13px;
            background-color: var(--secondary-color);
            border-top: 1px solid var(--light-gray);
        }
        
        .stamp {
            width: 150px;
            height: 150px;
            border: 2px solid var(--primary-color);
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            transform: rotate(-15deg);
            position: absolute;
            right: 30px;
            bottom: 40px;
            opacity: 0.8;
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .stamp-text {
            font-size: 16px;
            margin-bottom: 5px;
        }
        
        .stamp-date {
            font-size: 12px;
        }
        
        .qr-code {
            margin-top: 15px;
            width: 100px;
            height: 100px;
            background-color: var(--light-gray);
            display: inline-block;
        }
        
        @media print {
            body {
                background-color: white;
            }
            
            .container {
                box-shadow: none;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">IMMOBILIER+</div>
            <h1 class="title">Quittance de Reversement</h1>
            <p class="subtitle">Document officiel de versement au propriétaire</p>
        </div>
        
        <div class="content">
            <div class="section">
                <h2 class="section-title">Informations générales</h2>
                <div class="grid">
                    <div class="info-item">
                        <span class="info-label">Référence</span>
                        <span class="info-value">{{ $partial->reference }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Date de versement</span>
                        <span class="info-value">{{ \Carbon\Carbon::parse($partial->remittance_date)->format('d/m/Y') }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Mode de versement</span>
                        <span class="info-value">{{ ucfirst($partial->mode_remit) }}</span>
                    </div>
                </div>
            </div>
            
            <div class="section">
                <h2 class="section-title">Propriétaire & Propriété</h2>
                <div class="grid">
                    <div class="info-item">
                        <span class="info-label">Propriétaire</span>
                        <span class="info-value">{{ $partial->remittance->owner->name }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Propriété</span>
                        <span class="info-value">{{ $partial->remittance->property->name }}</span>
                    </div>
                </div>
            </div>
            
            <div class="section">
                <h2 class="section-title">Détails financiers</h2>
                <div class="amount-box">
                    <div class="amount-row">
                        <span class="amount-label">Montant dû</span>
                        <span class="amount-value">{{ number_format($partial->remittance->amount_to_transfer, 0, ',', ' ') }} FCFA</span>
                    </div>
                    <div class="amount-row">
                        <span class="amount-label">Montant du versement actuel</span>
                        <span class="amount-value highlight">{{ number_format($partial->amount, 0, ',', ' ') }} FCFA</span>
                    </div>
                </div>
            </div>
            
            <div class="section">
                <h2 class="section-title">Historique des versements</h2>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Référence</th>
                            <th>Montant</th>
                            <th>Mode</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($partial->remittance->remittancePartials as $remittancePartial)
                            <tr>
                                <td>{{ $remittancePartial->reference }}</td>
                                <td>{{ number_format($remittancePartial->amount, 0, ',', ' ') }} FCFA</td>
                                <td>{{ ucfirst($remittancePartial->mode_remit) }}</td>
                                <td>{{ \Carbon\Carbon::parse($remittancePartial->remittance_date)->format('d/m/Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <div class="section" style="position: relative;">
                <h2 class="section-title">Certification</h2>
                <p>Cette quittance atteste du reversement effectué à cette date. Ce document fait foi de paiement et peut être utilisé comme justificatif comptable.</p>
                
                <div class="stamp">
                    <span class="stamp-text">PAYÉ</span>
                    <span class="stamp-date">{{ \Carbon\Carbon::parse($partial->remittance_date)->format('d/m/Y') }}</span>
                </div>
            </div>
        </div>
        
        <div class="footer">
            <p>Pour toute question concernant cette quittance, veuillez contacter notre service client.</p>
            <!-- <div class="qr-code">
                <img src="/api/placeholder/100/100" alt="QR Code de vérification">
                
            </div> -->
        </div>
    </div>
</body>
</html>