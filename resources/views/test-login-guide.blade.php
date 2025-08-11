<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Guide de Test - Système de Gestion d'Erreurs</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 min-h-screen py-8">
  <div class="max-w-6xl mx-auto px-4">
    <div class="bg-white rounded-lg shadow-lg p-8">
      <h1 class="text-3xl font-bold text-gray-900 mb-8 text-center">
        🧪 Guide de Test - Système de Gestion d'Erreurs
      </h1>

      <div class="mb-8 p-6 bg-blue-50 border border-blue-200 rounded-lg">
        <h2 class="text-xl font-semibold text-blue-900 mb-4">📋 Instructions Générales</h2>
        <ol class="list-decimal list-inside text-blue-800 space-y-2">
          <li>Utilisez les comptes de test ci-dessous pour vous connecter</li>
          <li>Mot de passe pour tous les comptes : <code class="bg-blue-100 px-2 py-1 rounded font-mono">password</code></li>
          <li>Observez les notifications Filament qui apparaissent</li>
          <li>Testez les actions dans les notifications (boutons)</li>
          <li>Consultez les logs : <code class="bg-blue-100 px-2 py-1 rounded font-mono">tail -f storage/logs/laravel.log</code></li>
        </ol>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Scénarios d'Erreur -->
        <div class="space-y-4">
          <h2 class="text-2xl font-bold text-gray-900 mb-4">🚨 Scénarios d'Erreur</h2>

          <!-- Utilisateur sans rôle -->
          <div class="bg-red-50 border border-red-200 rounded-lg p-6">
            <div class="flex items-center mb-4">
              <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mr-4">
                <span class="text-red-600 font-bold">1</span>
              </div>
              <div>
                <h3 class="text-lg font-semibold text-red-800">Utilisateur Sans Rôle</h3>
                <p class="text-red-600 text-sm">NoValidRoleException</p>
              </div>
            </div>
            <div class="bg-white p-4 rounded border">
              <p class="font-mono text-sm mb-2"><strong>Email:</strong> no-role@test.com</p>
              <p class="font-mono text-sm mb-2"><strong>Mot de passe:</strong> password</p>
              <p class="text-sm text-gray-600 mb-3"><strong>Résultat attendu:</strong> Notification d'erreur "Votre compte n'a pas de rôle assigné"</p>
              <a href="/login" class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors text-sm">
                🔗 Tester la Connexion
              </a>
            </div>
          </div>

          <!-- Utilisateur sans agence -->
          <div class="bg-orange-50 border border-orange-200 rounded-lg p-6">
            <div class="flex items-center mb-4">
              <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center mr-4">
                <span class="text-orange-600 font-bold">2</span>
              </div>
              <div>
                <h3 class="text-lg font-semibold text-orange-800">Utilisateur Sans Agence</h3>
                <p class="text-orange-600 text-sm">NoAgencyAccessException</p>
              </div>
            </div>
            <div class="bg-white p-4 rounded border">
              <p class="font-mono text-sm mb-2"><strong>Email:</strong> no-agency@test.com</p>
              <p class="font-mono text-sm mb-2"><strong>Mot de passe:</strong> password</p>
              <p class="text-sm text-gray-600 mb-3"><strong>Résultat attendu:</strong> Notification "Vous n'avez accès à aucune agence"</p>
              <a href="/login" class="inline-flex items-center px-4 py-2 bg-orange-600 text-white rounded-md hover:bg-orange-700 transition-colors text-sm">
                🔗 Tester la Connexion
              </a>
            </div>
          </div>

          <!-- Utilisateur multi-agences -->
          <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
            <div class="flex items-center mb-4">
              <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mr-4">
                <span class="text-blue-600 font-bold">3</span>
              </div>
              <div>
                <h3 class="text-lg font-semibold text-blue-800">Utilisateur Multi-Agences</h3>
                <p class="text-blue-600 text-sm">TenantSelectionRequired</p>
              </div>
            </div>
            <div class="bg-white p-4 rounded border">
              <p class="font-mono text-sm mb-2"><strong>Email:</strong> multi-agency@test.com</p>
              <p class="font-mono text-sm mb-2"><strong>Mot de passe:</strong> password</p>
              <p class="text-sm text-gray-600 mb-3"><strong>Résultat attendu:</strong> Page de sélection d'agence</p>
              <a href="/login" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors text-sm">
                🔗 Tester la Connexion
              </a>
            </div>
          </div>
        </div>

        <!-- Scénarios de Succès -->
        <div class="space-y-4">
          <h2 class="text-2xl font-bold text-gray-900 mb-4">✅ Scénarios de Succès</h2>

          <!-- Super Admin -->
          <div class="bg-purple-50 border border-purple-200 rounded-lg p-6">
            <div class="flex items-center mb-4">
              <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center mr-4">
                <span class="text-purple-600 font-bold">👑</span>
              </div>
              <div>
                <h3 class="text-lg font-semibold text-purple-800">Super Administrateur</h3>
                <p class="text-purple-600 text-sm">Accès direct</p>
              </div>
            </div>
            <div class="bg-white p-4 rounded border">
              <p class="font-mono text-sm mb-2"><strong>Email:</strong> super-admin@test.com</p>
              <p class="font-mono text-sm mb-2"><strong>Mot de passe:</strong> password</p>
              <p class="text-sm text-gray-600 mb-3"><strong>Résultat attendu:</strong> Redirection directe vers /super-admin</p>
              <a href="/login" class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 transition-colors text-sm">
                🔗 Tester la Connexion
              </a>
            </div>
          </div>

          <!-- Utilisateur une agence -->
          <div class="bg-green-50 border border-green-200 rounded-lg p-6">
            <div class="flex items-center mb-4">
              <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mr-4">
                <span class="text-green-600 font-bold">✓</span>
              </div>
              <div>
                <h3 class="text-lg font-semibold text-green-800">Utilisateur Une Agence</h3>
                <p class="text-green-600 text-sm">Sélection automatique</p>
              </div>
            </div>
            <div class="bg-white p-4 rounded border">
              <p class="font-mono text-sm mb-2"><strong>Email:</strong> one-agency@test.com</p>
              <p class="font-mono text-sm mb-2"><strong>Mot de passe:</strong> password</p>
              <p class="text-sm text-gray-600 mb-3"><strong>Résultat attendu:</strong> Redirection automatique vers son panel</p>
              <a href="/login" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors text-sm">
                🔗 Tester la Connexion
              </a>
            </div>
          </div>

          <!-- Propriétaire -->
          <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-6">
            <div class="flex items-center mb-4">
              <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center mr-4">
                <span class="text-indigo-600 font-bold">🏠</span>
              </div>
              <div>
                <h3 class="text-lg font-semibold text-indigo-800">Propriétaire</h3>
                <p class="text-indigo-600 text-sm">Panel owner</p>
              </div>
            </div>
            <div class="bg-white p-4 rounded border">
              <p class="font-mono text-sm mb-2"><strong>Email:</strong> owner@test.com</p>
              <p class="font-mono text-sm mb-2"><strong>Mot de passe:</strong> password</p>
              <p class="text-sm text-gray-600 mb-3"><strong>Résultat attendu:</strong> Redirection vers /owner/agence-test-1</p>
              <a href="/login" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition-colors text-sm">
                🔗 Tester la Connexion
              </a>
            </div>
          </div>

          <!-- Locataire -->
          <div class="bg-teal-50 border border-teal-200 rounded-lg p-6">
            <div class="flex items-center mb-4">
              <div class="w-12 h-12 bg-teal-100 rounded-full flex items-center justify-center mr-4">
                <span class="text-teal-600 font-bold">🏠</span>
              </div>
              <div>
                <h3 class="text-lg font-semibold text-teal-800">Locataire</h3>
                <p class="text-teal-600 text-sm">Panel tenant</p>
              </div>
            </div>
            <div class="bg-white p-4 rounded border">
              <p class="font-mono text-sm mb-2"><strong>Email:</strong> tenant@test.com</p>
              <p class="font-mono text-sm mb-2"><strong>Mot de passe:</strong> password</p>
              <p class="text-sm text-gray-600 mb-3"><strong>Résultat attendu:</strong> Redirection vers /tenant/agence-test-1</p>
              <a href="/login" class="inline-flex items-center px-4 py-2 bg-teal-600 text-white rounded-md hover:bg-teal-700 transition-colors text-sm">
                🔗 Tester la Connexion
              </a>
            </div>
          </div>
        </div>
      </div>

      <!-- Tests Avancés -->
      <div class="mt-8 p-6 bg-gray-50 border border-gray-200 rounded-lg">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">🔧 Tests Avancés</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <h4 class="font-semibold text-gray-800 mb-2">Tests Manuels d'Erreurs</h4>
            <ul class="text-sm text-gray-600 space-y-1">
              <li>• <a href="/test-errors" class="text-blue-600 hover:underline">Page de test HTML simple</a></li>
              <li>• <a href="/login" class="text-blue-600 hover:underline">Page de test Filament (après connexion)</a></li>
              <li>• Modifier les rôles/agences via Tinker</li>
              <li>• Simuler des pannes de redirection</li>
            </ul>
          </div>
          <div>
            <h4 class="font-semibold text-gray-800 mb-2">Commandes Utiles</h4>
            <ul class="text-sm text-gray-600 space-y-1 font-mono">
              <li>• <code>tail -f storage/logs/laravel.log</code></li>
              <li>• <code>php artisan session:flush</code></li>
              <li>• <code>php artisan cache:clear</code></li>
              <li>• <code>php artisan tinker</code></li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Checklist -->
      <div class="mt-8 p-6 bg-yellow-50 border border-yellow-200 rounded-lg">
        <h2 class="text-xl font-semibold text-yellow-900 mb-4">📋 Checklist de Test</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <h4 class="font-semibold text-yellow-800 mb-2">Notifications</h4>
            <ul class="text-sm text-yellow-700 space-y-1">
              <li>□ Titre approprié selon l'erreur</li>
              <li>□ Message explicatif clair</li>
              <li>□ Actions contextuelles (boutons)</li>
              <li>□ Icônes et couleurs correctes</li>
              <li>□ Persistance selon le type</li>
            </ul>
          </div>
          <div>
            <h4 class="font-semibold text-yellow-800 mb-2">Comportement</h4>
            <ul class="text-sm text-yellow-700 space-y-1">
              <li>□ Redirection appropriée</li>
              <li>□ Session préservée</li>
              <li>□ Logs générés correctement</li>
              <li>□ Pas de boucles infinies</li>
              <li>□ Application stable</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>

</html>