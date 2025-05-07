<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Quittance de reversement</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 14px; }
        .header { text-align: center; margin-bottom: 20px; }
        .content { margin: 0 auto; width: 80%; }
        .line { margin: 8px 0; }
        .bold { font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Quittance de Reversement</h2>
    </div>

    <div class="content">
        <p class="line"><span class="bold">Référence :</span> {{ $partial->reference }}</p>
        <p class="line"><span class="bold">Date :</span> {{ \Carbon\Carbon::parse($partial->remittance_date)->format('d/m/Y') }}</p>
        <p class="line"><span class="bold">Montant :</span> {{ number_format($partial->amount, 0, ',', ' ') }} FCFA</p>
        <p class="line"><span class="bold">Mode de versement :</span> {{ ucfirst($partial->mode_remit) }}</p>
        <hr>
        <p class="line">Cette quittance atteste du reversement effectué à cette date.</p>
    </div>
</body>
</html>
