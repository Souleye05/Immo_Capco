<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Invitation - Accès à votre espace locataire</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      line-height: 1.6;
      color: #333;
      max-width: 600px;
      margin: 0 auto;
      padding: 20px;
    }

    .header {
      background-color: #f8f9fa;
      padding: 20px;
      text-align: center;
      border-radius: 8px;
      margin-bottom: 30px;
    }

    .content {
      background-color: #ffffff;
      padding: 30px;
      border-radius: 8px;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .button {
      display: inline-block;
      background-color: #007bff;
      color: white;
      padding: 12px 30px;
      text-decoration: none;
      border-radius: 5px;
      margin: 20px 0;
      font-weight: bold;
    }

    .button:hover {
      background-color: #0056b3;
    }

    .info-box {
      background-color: #e9ecef;
      padding: 15px;
      border-radius: 5px;
      margin: 20px 0;
    }

    .footer {
      text-align: center;
      margin-top: 30px;
      font-size: 12px;
      color: #666;
    }
  </style>
</head>

<body>
  <div class="header">
    <h1>🏠 Bienvenue dans votre espace locataire</h1>
    <p><strong>{{ $agency->name }}</strong></p>
  </div>

  <div class="content">
    <h2>Bonjour {{ $user->name }},</h2>

    <p>Nous avons le plaisir de vous informer qu'un compte locataire a été créé pour vous sur notre plateforme de gestion immobilière.</p>

    <p>Votre agence <strong>{{ $agency->name }}</strong> vous invite à activer votre compte pour accéder à votre espace personnel où vous pourrez :</p>

    <ul>
      <li>📋 Consulter vos contrats de location</li>
      <li>💰 Suivre vos paiements et l'historique</li>
      <li>📄 Télécharger vos documents (quittances, contrats, etc.)</li>
      <li>🔧 Signaler des problèmes de maintenance</li>
      <li>📞 Communiquer avec votre agence</li>
    </ul>

    <div class="info-box">
      <strong>📧 Votre email de connexion :</strong> {{ $user->email }}
    </div>

    <p>Pour activer votre compte et définir votre mot de passe, cliquez sur le bouton ci-dessous :</p>

    <div style="text-align: center;">
      <a href="{{ $activationUrl }}" class="button">
        🔐 Activer mon compte
      </a>
    </div>

    <div class="info-box">
      <strong>⚠️ Important :</strong>
      <ul>
        <li>Ce lien est valable pendant <strong>7 jours</strong></li>
        <li>Vous devrez définir votre propre mot de passe lors de l'activation</li>
        <li>Une fois activé, vous pourrez vous connecter à tout moment</li>
      </ul>
    </div>

    <p>Si vous avez des questions ou rencontrez des difficultés, n'hésitez pas à contacter votre agence :</p>

    <div class="info-box">
      <strong>{{ $agency->name }}</strong><br>
      📧 Email : {{ $agency->email ?? 'Non renseigné' }}<br>
      📞 Téléphone : {{ $agency->phone ?? 'Non renseigné' }}
    </div>

    <p>Nous vous souhaitons une excellente expérience sur notre plateforme !</p>

    <p>Cordialement,<br>
      L'équipe {{ $agency->name }}</p>
  </div>

  <div class="footer">
    <p>Cet email a été envoyé automatiquement. Merci de ne pas y répondre directement.</p>
    <p>Si vous n'êtes pas concerné par cette invitation, vous pouvez ignorer cet email.</p>
  </div>
</body>

</html>