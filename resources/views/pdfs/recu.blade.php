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
  font-size: 10px; /* un peu plus petit */
  line-height: 1.5;
  color: #000;
  background: #fff;
  padding: 25px; /* réduit */
}

.container {
  max-width: 148mm; /* largeur A5 */
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
  font-size: 10px;
  font-style: italic;
  margin-bottom: 10px;
}


.title {
  text-align: center;
  margin-top: 20px;
  margin-bottom: 10px;
}

.title h2 {
  font-size: 16px;
  font-weight: bold;
  letter-spacing: 1px;
}

.info-row {
  display: flex;
  margin-bottom: 6px;
  align-items: baseline;
}

.info-label {
  min-width: 100px;
  font-size: 12px;
}

.info-value {
  flex: 1;
  font-weight: bold;
  min-height: 16px;
  margin-left: 25px;
  font-size: 12px;
}
        

.bailleur-section {
            margin: 25px 0;
        }
        
        .bailleur-section .info-label {
            vertical-align: top;
            padding-top: 2px;
        }
      
        
        
        .invoice-table {
            width: 70%;
            margin-left: 15%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 10px;
        }
        
        .invoice-table th,
        .invoice-table td {
            border: 1px solid #333;
            padding: 6px 8px;
            text-align: center;
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
            font-size: 9px;
            margin-bottom: 15px;
            line-height: 1.4;
        }
        .numero {
            text-align: right;
            font-weight: bold;
            font-size: 12px;
            margin-top: 10px;
            margin-bottom: 10px;
        }
    
        
        .signature-label {
            text-align: right;
            margin-top: 20px;
            font-size: 9px;
            font-weight: bold;
            text-decoration-line: underline;
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
      <h2>REÇU DE PAIEMENT</h2>
    </div>

    <div class="numero">
        <strong>N°</strong> {{ $numero_document ?? '' }}
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
                <span class="info-label">Nous, <strong>Cabinet CAPCO</strong>, attestons avoir reçu en ce jour du Locataire</span>
            </div>
    </div>

    <table class="invoice-table">
    <tr>
        <td>Montant reçu :</td>
        <td class="amount">{{ number_format($montant_verse, 0, ',', ' ') }} F CFA</td>
    </tr>
    </table>


    <div class="footer-notes">
        <p>Ce reçu est délivré <strong>à titre provisoire</strong>, en attendant les vérifications internes nécessaires. </p>
        <p>Une <strong>quittance de loyer définitive</strong> sera remise après validation du paiement effectif et complet.</p>
        </div>

    
        <div class="signature-label">
          <span>La Comptabilité :</span>
        </div>
    </div>
</body>
</html>