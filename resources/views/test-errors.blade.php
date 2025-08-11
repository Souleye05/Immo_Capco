<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Test des Erreurs de Redirection</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@heroicons/react@2.0.18/24/outline/index.css">
</head>

<body class="bg-gray-100 min-h-screen py-8">
  <div class="max-w-4xl mx-auto px-4">
    <div class="bg-white rounded-lg shadow-lg p-8">
      <h1 class="text-3xl font-bold text-gray-900 mb-8 text-center">
        🧪 Test du Système de Gestion d'Erreurs
      </h1>

      @if(session('success'))
      <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
        {{ session('success') }}
      </div>
      @endif

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Test No Valid Role -->
        <div class="bg-red-50 border border-red-200 rounded-lg p-6">
          <div class="flex items-center mb-4">
            <svg class="w-8 h-8 text-red-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
            </svg>
            <h3 class="text-lg font-semibold text-red-800">Aucun Rôle</h3>
          </div>
          <p class="text-red-700 mb-4 text-sm">
            Teste l'erreur quand un utilisateur n'a pas de rôle valide assigné.
          </p>
          <a href="/test-error/no-role"
            class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors">
            Tester
          </a>
        </div>

        <!-- Test No Agency Access -->
        <div class="bg-orange-50 border border-orange-200 rounded-lg p-6">
          <div class="flex items-center mb-4">
            <svg class="w-8 h-8 text-orange-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
            </svg>
            <h3 class="text-lg font-semibold text-orange-800">Aucune Agence</h3>
          </div>
          <p class="text-orange-700 mb-4 text-sm">
            Teste l'erreur quand un utilisateur n'a accès à aucune agence.
          </p>
          <a href="/test-error/no-agency"
            class="inline-flex items-center px-4 py-2 bg-orange-600 text-white rounded-md hover:bg-orange-700 transition-colors">
            Tester
          </a>
        </div>

        <!-- Test Invalid Tenant -->
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
          <div class="flex items-center mb-4">
            <svg class="w-8 h-8 text-yellow-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
            </svg>
            <h3 class="text-lg font-semibold text-yellow-800">Agence Invalide</h3>
          </div>
          <p class="text-yellow-700 mb-4 text-sm">
            Teste l'erreur quand une agence invalide est sélectionnée.
          </p>
          <a href="/test-error/invalid-tenant"
            class="inline-flex items-center px-4 py-2 bg-yellow-600 text-white rounded-md hover:bg-yellow-700 transition-colors">
            Tester
          </a>
        </div>

        <!-- Test Redirection Failed -->
        <div class="bg-purple-50 border border-purple-200 rounded-lg p-6">
          <div class="flex items-center mb-4">
            <svg class="w-8 h-8 text-purple-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <h3 class="text-lg font-semibold text-purple-800">Redirection Échouée</h3>
          </div>
          <p class="text-purple-700 mb-4 text-sm">
            Teste l'erreur quand la redirection vers un panel échoue.
          </p>
          <a href="/test-error/redirection-failed"
            class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 transition-colors">
            Tester
          </a>
        </div>

        <!-- Test Tenant Selection Required -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
          <div class="flex items-center mb-4">
            <svg class="w-8 h-8 text-blue-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
            </svg>
            <h3 class="text-lg font-semibold text-blue-800">Sélection Requise</h3>
          </div>
          <p class="text-blue-700 mb-4 text-sm">
            Teste l'erreur quand la sélection d'agence est requise.
          </p>
          <a href="/test-error/tenant-selection"
            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
            Tester
          </a>
        </div>

        <!-- Test Generic Error -->
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-6">
          <div class="flex items-center mb-4">
            <svg class="w-8 h-8 text-gray-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <h3 class="text-lg font-semibold text-gray-800">Erreur Générique</h3>
          </div>
          <p class="text-gray-700 mb-4 text-sm">
            Teste la gestion d'une erreur générique non spécifique.
          </p>
          <a href="/test-error/generic"
            class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors">
            Tester
          </a>
        </div>
      </div>

      <div class="mt-8 p-6 bg-blue-50 border border-blue-200 rounded-lg">
        <h3 class="text-lg font-semibold text-blue-800 mb-3">📋 Instructions de Test</h3>
        <ol class="list-decimal list-inside text-blue-700 space-y-2">
          <li>Cliquez sur un bouton "Tester" pour déclencher une erreur spécifique</li>
          <li>Observez la notification Filament qui apparaît en haut à droite</li>
          <li>Vérifiez les actions disponibles dans la notification (boutons)</li>
          <li>Consultez les logs dans <code class="bg-blue-100 px-2 py-1 rounded">storage/logs/laravel.log</code></li>
          <li>Testez les différents types d'erreurs pour voir les variations</li>
        </ol>
      </div>

      <div class="mt-6 text-center">
        <a href="/login" class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition-colors">
          ← Retour au Login
        </a>
      </div>
    </div>
  </div>

  <!-- Inclure les scripts Filament pour les notifications -->
  @if(app()->environment('local'))
  <script>
    console.log('🧪 Page de test des erreurs chargée');
    console.log('Utilisateur connecté:', @json(auth() - > user() ? - > email ?? 'Non connecté'));
  </script>
  @endif
</body>

</html>