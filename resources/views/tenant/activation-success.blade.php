<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Compte activé avec succès</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
    }

    .success-card {
      background: white;
      border-radius: 15px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
      overflow: hidden;
      text-align: center;
    }

    .card-header {
      background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
      color: white;
      padding: 3rem 2rem;
    }

    .card-body {
      padding: 3rem 2rem;
    }

    .success-icon {
      font-size: 4rem;
      margin-bottom: 1rem;
    }

    .btn-login {
      background: linear-gradient(135deg, #007bff 0%, #6610f2 100%);
      border: none;
      border-radius: 10px;
      padding: 12px 30px;
      font-weight: bold;
      color: white;
      text-decoration: none;
      display: inline-block;
      margin: 10px;
    }

    .btn-login:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
      color: white;
      text-decoration: none;
    }

    .info-box {
      background-color: #e9ecef;
      border-radius: 8px;
      padding: 20px;
      margin: 20px 0;
    }
  </style>
</head>

<body>
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-6 col-lg-5">
        <div class="success-card">
          <div class="card-header">
            <div class="success-icon">🎉</div>
            <h2 class="mb-0">Félicitations !</h2>
            <p class="mb-0 mt-2">Votre compte locataire a été activé avec succès</p>
          </div>

          <div class="card-body">
            <div class="mb-4">
              <h4>✅ Votre compte est maintenant actif</h4>
              <p class="text-muted">
                Vous pouvez désormais accéder à votre espace locataire personnel
                pour gérer vos contrats, paiements et documents.
              </p>
            </div>

            <div class="info-box">
              <h5>🏠 Que pouvez-vous faire maintenant ?</h5>
              <ul class="text-start">
                <li>📋 Consulter vos contrats de location</li>
                <li>💰 Suivre vos paiements et télécharger vos quittances</li>
                <li>📄 Accéder à tous vos documents</li>
                <li>🔧 Signaler des problèmes de maintenance</li>
                <li>📞 Communiquer avec votre agence</li>
              </ul>
            </div>

            <div class="d-grid gap-2 mt-4">
              <a href="{{ route('filament.tenant.auth.login') }}" class="btn-login">
                🔐 Se connecter à mon espace locataire
              </a>
            </div>

            <div class="mt-4">
              <small class="text-muted">
                <strong>💡 Conseil :</strong> Ajoutez cette page à vos favoris pour un accès rapide !<br>
                <strong>🔒 Sécurité :</strong> Ne partagez jamais vos identifiants de connexion.
              </small>
            </div>

            <div class="info-box mt-4">
              <strong>📞 Besoin d'aide ?</strong><br>
              Contactez votre agence immobilière si vous rencontrez des difficultés
              ou si vous avez des questions sur votre espace locataire.
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>

</html>