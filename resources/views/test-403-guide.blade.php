<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Guide de Test - Erreurs 403</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 min-h-screen py-8">
  <div class="max-w-6xl mx-auto px-4">
    <div class="bg-white rounded-lg shadow-lg p-8">
      <h1 class="text-3xl font-bold text-gray-900 mb-8 text-center">
        🚫 Guide de Test - Erreurs 403 (Accès Refusé)
      </h1>

      <div class="mb-8 p-6 bg-red-50 border border-red-200 rounded-lg">
        <h2 class="text-xl font-semibold text-red-900 mb-4">🎯 Objectif du Test</h2>
        <p class="text-red-800">
          Tester que les utilisateurs sont correctement redirigés vers la page de connexion
          avec un message d'erreur approprié quand ils tentent d'accéder à un panel non autorisé.
        </p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Tests Automatiques -->
        <div class="space-y-4">
          <h2 class="text-2xl font-bold text-gray-900 mb-4">🤖 Tests Automatiques</h2>

          <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-blue-800 mb-4">Tests de Simulation 403</h3>
            <p class="text-blue-700 mb-4 text-sm">
              Ces liens simulent une erreur 403 et vous redirigent vers la page de connexion avec le message d'erreur.
            </p>

            <div class="space-y-3">
              <div class="flex items-center justify-between p-3 bg-white rounded border">
                <div>
                  <h4 class="font-medium text-gray-900">Panel Admin</h4>
                  <p class="text-sm text-gray-600">Simule un accès refusé au panel d'administration</p>
                </div>
                <a href="/test-403/admin" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">
                  Tester
                </a>
              </div>

              <div class="flex items-center justify-between p-3 bg-white rounded border">
                <div>
                  <h4 class="font-medium text-gray-900">Panel Super Admin</h4>
                  <p class="text-sm text-gray-600">Simule un accès refusé au panel super administrateur</p>
                </div>
                <a href="/test-403/super-admin" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">
                  Tester
                </a>
              </div>

              <div class="flex items-center justify-between p-3 bg-white rounded border">
                <div>
                  <h4 class="font-medium text-gray-900">Panel Propriétaire</h4>
                  <p class="text-sm text-gray-600">Simule un accès refusé au panel propriétaire</p>
                </div>
                <a href="/test-403/owner" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">
                  Tester
                </a>
              </div>

              <div class="flex items-center justify-between p-3 bg-white rounded border">
                <div>
                  <h4 class="font-medium text-gray-900">Panel Locataire</h4>
                  <p class="text-sm text-gray-600">Simule un accès refusé au panel locataire</p>
                </div>
                <a href="/test-403/tenant" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">
                  Tester
                </a>
              </div>
            </div>
          </div>
        </div>

        <!-- Tests Manuels -->
        <div class="space-y-4">
          <h2 class="text-2xl font-bold text-gray-900 mb-4">👤 Tests Manuels</h2>

          <div class="bg-orange-50 border border-orange-200 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-orange-800 mb-4">Procédure de Test Manuel</h3>

            <ol class="list-decimal list-inside text-orange-700 space-y-3 text-sm">
              <li>
                <strong>Connectez-vous</strong> avec un compte limité :
                <div class="mt-2 p-2 bg-white rounded border">
                  <p class="font-mono text-xs">Email: no-role@test.com</p>
                  <p class="font-mono text-xs">Mot de passe: password</p>
                </div>
              </li>

              <li>
                <strong>Tentez d'accéder directement</strong> aux panels :
                <div class="mt-2 space-y-1">
                  <a href="/admin" class="block text-blue-600 hover:underline font-mono text-xs">/admin</a>
                  <a href="/super-admin" class="block text-blue-600 hover:underline font-mono text-xs">/super-admin</a>
                  <a href="/owner" class="block text-blue-600 hover:underline font-mono text-xs">/owner</a>
                  <a href="/tenant" class="block text-blue-600 hover:underline font-mono text-xs">/tenant</a>
                </div>
              </li>

              <li><strong>Observez</strong> la redirection vers /login avec le message d'erreur</li>

              <li><strong>Testez les actions</strong> dans la notification (boutons)</li>

              <li><strong>Répétez</strong> avec différents comptes utilisateurs</li>
            </ol>
          </div>

          <div class="bg-green-50 border border-green-200 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-green-800 mb-4">Comptes de Test</h3>

            <div class="space-y-2 text-sm">
              <div class="flex justify-between items-center p-2 bg-white rounded border">
                <span class="font-mono text-xs">no-role@test.com</span>
                <span class="text-green-600 text-xs">Aucun rôle → Accès refusé partout</span>
              </div>
              <div class="flex justify-between items-center p-2 bg-white rounded border">
                <span class="font-mono text-xs">no-agency@test.com</span>
                <span class="text-green-600 text-xs">Admin sans agence → Accès limité</span>
              </div>
              <div class="flex justify-between items-center p-2 bg-white rounded border">
                <span class="font-mono text-xs">owner@test.com</span>
                <span class="text-green-600 text-xs">Propriétaire → Accès owner seulement</span>
              </div>
              <div class="flex justify-between items-center p-2 bg-white rounded border">
                <span class="font-mono text-xs">tenant@test.com</span>
                <span class="text-green-600 text-xs">Locataire → Accès tenant seulement</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Résultats Attendus -->
      <div class="mt-8 p-6 bg-yellow-50 border border-yellow-200 rounded-lg">
        <h2 class="text-xl font-semibold text-yellow-900 mb-4">✅ Résultats Attendus</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <h4 class="font-semibold text-yellow-800 mb-2">Comportement Attendu</h4>
            <ul class="text-sm text-yellow-700 space-y-1">
              <li>✓ Redirection automatique vers /login</li>
              <li>✓ Notification d'erreur rouge persistante</li>
              <li>✓ Message explicatif personnalisé</li>
              <li>✓ Actions contextuelles (boutons)</li>
              <li>✓ Pas de boucle de redirection</li>
            </ul>
          </div>
          <div>
            <h4 class="font-semibold text-yellow-800 mb-2">Éléments à Vérifier</h4>
            <ul class="text-sm text-yellow-700 space-y-1">
              <li>• Titre : "Accès Refusé"</li>
              <li>• Message spécifique au panel</li>
              <li>• Bouton "J'ai compris"</li>
              <li>• Bouton "Contacter l'administrateur"</li>
              <li>• Icône de bouclier d'exclamation</li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Navigation -->
      <div class="mt-8 text-center space-x-4">
        <a href="/test-guide" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
          ← Guide Principal
        </a>
        <a href="/login" class="inline-flex items-center px-6 py-3 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors">
          Page de Connexion →
        </a>
      </div>
    </div>
  </div>
</body>

</html>