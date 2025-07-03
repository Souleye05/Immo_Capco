<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Quittance de Loyer - CAPCO</title>
  <style>
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

    /* .bailleur-section {
  margin: 20px 0;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
} */

    /* .bailleur-details {
  margin-left: 5%;
  padding-left: 30%;
  line-height: 1.2;
  font-size: 9px;
} */

    /* .bailleur-details p {
  margin: 1px 0;
} */

    .payment-table {
      margin: 20px 0;
      border-collapse: collapse;
      width: 100%;
    }

    .payment-table td {
      border: 1px solid #000;
      padding: 6px 8px;
      font-size: 9px;
      vertical-align: middle;
    }

    .payment-table .label-cell {
      width: 60%;
    }

    .payment-table .amount-cell {
      width: 40%;
      text-align: right;
      font-weight: bold;
    }

    .payment-statement,
    .acquit {
      margin: 12px 0;
      font-style: italic;
      font-size: 9px;
    }

    .footer-text {
      margin: 20px 0;
      font-size: 8px;
      line-height: 1.4;
      text-align: justify;
    }

    .footer-text p {
      margin-bottom: 8px;
    }

    .signature-section {
      margin-top: 30px;
      text-align: center;
    }

    .signature-section p {
      font-size: 9px;
    }
  </style>
</head>

<body>
  <div class="container">
    <!-- LOGO + INFOS -->
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
      <!-- <h2>QUITTANCE DE LOYER</h2> -->
      <h2>QUITTANCE DE {{ $payment->type->getQuittanceLabel() }}</h2>
    </div>

    <!-- INFORMATIONS LOCATIVES -->
    @if(isset($periode_debut) && isset($periode_fin))
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
    @endif


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

    <!-- TABLEAU PAIEMENT -->
    <table class="payment-table">
    <tr>
        <td class="label-cell">{{ $payment->type->getLabelLignePrincipale() }}</td>
        <td class="amount-cell">
            {{ number_format($payment->amount, 0, ',', ' ') }} FCFA
        </td>
    </tr>
    <tr>
        <td class="label-cell">{{ $payment->type->getLabelPaiementEffectue() }}</td>
        <td class="amount-cell">
            {{ number_format($payment->amount_paid, 0, ',', ' ') }} FCFA
        </td>
    </tr>
</table>



    <div class="payment-statement">
      <p><em>Le bailleur donne quittance au locataire de cette somme.</em></p>
    </div>

    <div class="acquit">
      <p><em>Pour acquit,</em></p>
    </div>

    <!-- MENTIONS LEGALES -->
    <div class="footer-text">
      <p>
        Cette quittance vaut pour la période concernée. Elle annule tous les
        reçus qui auraient pu être établis précédemment en cas de paiement
        partiel du montant du présent terme.
        <strong>Elle est à conserver pendant trois ans par le locataire.</strong>
      </p>
      <p>
        Dont quittance, sous réserve de tous suppléments pouvant être dus en
        vertu des lois et conventions applicables et sous réserve de tous les
        droits et actions du propriétaire, de toutes poursuites qui auraient
        pu être engagées et de toutes décisions de justice qui auraient pu
        être obtenues.
      </p>
    </div>

    <!-- SIGNATURE -->
    <div class="signature-section">
      <p class="title"><strong>Le Gérant :</strong></p>
      <p><strong>M. Souleymane NIANG</strong></p>
    </div>
  </div>
</body>

</html>