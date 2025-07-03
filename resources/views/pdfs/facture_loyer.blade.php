<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Facture de Loyer</title>
  <style>
    @page {
      size: A5;
      margin: 15mm;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: "Arial", sans-serif;
      font-size: 10px;
      /* un peu plus petit */
      line-height: 1.5;
      color: #000;
      background: #fff;
      padding: 25px;
      /* réduit */
    }

    .container {
      max-width: 148mm;
      /* largeur A5 */
      margin: 0 auto;
    }

    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 5px;
    }

    .logo-section {
      display: flex;
      align-items: center;
    }

    .logo {
      width: 45px;
      height: 45px;
      margin-right: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .logo img {
      max-width: 100%;
      max-height: 100%;
      object-fit: contain;
    }

    .company-info {
      line-height: 1.3;
    }

    .company-info h1 {
      font-size: 10px;
      font-weight: bold;
    }

    .company-info p {
      font-size: 8px;
      color: #333;
    }

    .company-info .email {
      text-decoration: underline;
    }

    .date-section {
      text-align: right;
      font-size: 9px;
      margin-bottom: 10px;
    }


    .title {
      text-align: center;
      margin: 20px 0;
    }

    .title h2 {
      font-size: 14px;
      font-weight: bold;
      letter-spacing: 1px;
    }

    .info-row {
      display: flex;
      margin-bottom: 6px;
      align-items: baseline;
    }

    .info-label {
      font-weight: bold;
      min-width: 100px;
      font-size: 9px;
    }

    .info-value {
      flex: 1;
      min-height: 16px;
      margin-left: 8px;
      font-size: 9px;
    }

    .info-row.period-row .info-value {
      width: 80px;
      text-align: center;
    }


    .bailleur-section {
      margin: 25px 0;
    }

    .bailleur-section .info-label {
      vertical-align: top;
      padding-top: 2px;
    }

    .bailleur-details {
      margin-left: 50px;
      line-height: 1.3;
      font-weight: bold;
    }

    .bailleur-details p {
      margin: 2px 0;
    }

    .invoice-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 15px;
      font-size: 9px;
    }

    .invoice-table th,
    .invoice-table td {
      border: 1px solid #333;
      padding: 6px 8px;
      text-align: left;
    }

    .invoice-table th {
      background-color: #f0f0f0;
      font-weight: bold;
    }

    .invoice-table .amount {
      text-align: right;
      font-weight: bold;
    }

    .total-row {
      background-color: #f8f9fa;
      font-weight: bold;
    }

    .footer-notes {
      font-size: 8px;
      font-style: italic;
      color: #666;
      margin-bottom: 15px;
      line-height: 1.4;
    }

    .signature {
      text-align: right;
      margin-top: 20px;
    }

    .signature-label {
      font-size: 9px;
      font-weight: bold;
    }

    .signature-name {
      font-size: 10px;
      font-weight: bold;
      margin-top: 2px;
    }
  </style>
</head>

<body>
  <div class="header">
    <div class="logo-section">
      <div class="logo">
        <!-- <img src="images/logo.png" alt="Logo CAPCO" /> -->
        <img src="{{ public_path('images/image.png') }}" alt="Logo CAPCO" style="height: 60px;">

      </div>
      <div class="company-info">
        <h1>
          CAPITAL CONSEIL dit CAPCO, Cabinet de Recouvrement de Créances
        </h1>
        <p>
          Hann Maristes Villa 5/15, 2ème étage à Gauche, Dakar Sénégal |
          +221.77.333.28.81
        </p>
        <p class="email">
          email : souleyniang@outlook.com N.I.N.E.A 00501854 | RCCM
          SN.DKR.2015.M.20920
        </p>
      </div>
    </div>
  </div>

  <!-- DATE À DROITE SOUS LE HEADER -->
  <div class="date-section">
    <p>
      <strong>Le </strong><span id="date_emission">{{$date_generation->format('d/m/Y')}}</span>
    </p>
  </div>

  <!-- TITRE -->
  <div class="title">
    <h2>FACTURE DE LOYER</h2>
  </div>

  <!-- INFORMATIONS LOCATIVES -->
  <div class="info-row period-row">
    <span class="info-label">Pour la période du :</span>
    <span class="info-value" id="periode_debut">
      {{ $periode_debut }}
    </span>
    <span>au</span>
    <span class="info-value" id="periode_fin">
      {{ $periode_fin }}
    </span>
  </div>

  <div class="info-row">
    <span class="info-label">Adresse du bien loué :</span>
    <span class="info-value" id="adresse_bien">
      {{ $flat->property->address ?? 'Inconnue' }}
    </span>
  </div>

  <div class="info-row">
    <span class="info-label">Locataire :</span>
    <span class="info-value" id="nom_locataire">{{ $tenant->name }}</span>
  </div>

  <!-- BAILLEUR -->

  <div class="bailleur-section">
    <div style="display: flex; align-items: flex-start;">
      <span class="info-label">Quittance fournie par le bailleur :</span>
      <div class="bailleur-details">
        <p><strong>CAPCO</strong></p>
        <p>Lot 18, Liberté 6 Extension</p>
        <p>Immeuble Numéro UNO</p>
        <p>DAKAR</p>
        <p>789172858 - 775700918</p>
        <p style="text-decoration: underline;">K.top@capco.sn</p>
      </div>
    </div>
  </div>

  <table class="invoice-table">
    <tr>
      <td>Loyer pour la période</td>
      <td class="amount">{{ number_format($flat->loyer, 0, ',', ' ') }} F CFA</td>
    </tr>

    @if($montant_restant > 0)
    <tr>
      <td>Arriérés de loyer dus par le locataire</td>
      <td class="amount">{{ number_format($arrieres, 0, ',', ' ') }} F CFA</td>
    </tr>
    @endif

    <tr class="total-row">
      <td><strong>Total à payer</strong></td>
      <td class="amount"><strong>{{ number_format($montant_total, 0, ',', ' ') }} F CFA</strong></td>
    </tr>
  </table>



  <div class="footer-notes">
    {{ $footer_note1 ?? 'Sauf erreur ou omission, nous vous prions de trouver ci-dessus la situation du ou des loyer(s) du pour le bien que vous occupez.' }}
    <br><br>
    {{ $footer_note2 ?? 'Nous vous prions de procéder au règlement de votre facture dans les meilleurs délais.' }}
  </div>

  <div class="signature">
    <div class="signature-label">{{ $signature_title ?? 'Le Gérant' }} :</div>
    <div class="signature-name">{{ $manager_name ?? 'M. Souleymane NIANG' }}</div>
  </div>
</body>

</html>