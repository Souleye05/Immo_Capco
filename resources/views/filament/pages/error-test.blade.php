<x-filament-panels::page>
  <div class="space-y-6">
    <!-- Informations utilisateur -->
    <x-filament::section>
      <x-slot name="heading">
        👤 Informations de Test
      </x-slot>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-gray-50 p-4 rounded-lg">
          <h4 class="font-semibold text-gray-900 mb-2">Utilisateur Connecté</h4>
          <p class="text-sm text-gray-600">{{ $user->email ?? 'Non connecté' }}</p>
          <p class="text-sm text-gray-600">ID: {{ $user->id ?? 'N/A' }}</p>
        </div>

        <div class="bg-blue-50 p-4 rounded-lg">
          <h4 class="font-semibold text-blue-900 mb-2">Panel Actuel</h4>
          <p class="text-sm text-blue-600">{{ filament()->getCurrentPanel()?->getId() ?? 'Aucun' }}</p>
          <p class="text-sm text-blue-600">Tenant: {{ filament()->getTenant()?->name ?? 'Aucun' }}</p>
        </div>
      </div>
    </x-filament::section>

    <!-- Instructions -->
    <x-filament::section>
      <x-slot name="heading">
        📋 Instructions de Test
      </x-slot>

      <div class="prose prose-sm max-w-none">
        <ol class="list-decimal list-inside space-y-2">
          <li>Utilisez les boutons d'action dans l'en-tête pour tester différents types d'erreurs</li>
          <li>Observez les notifications Filament qui apparaissent en haut à droite</li>
          <li>Vérifiez les actions disponibles dans chaque notification (boutons)</li>
          <li>Consultez les logs dans <code>storage/logs/laravel.log</code> pour voir les détails</li>
          <li>Testez les différents types d'erreurs pour voir les variations de style et de contenu</li>
        </ol>
      </div>
    </x-filament::section>

    <!-- Statistiques d'erreurs -->
    <x-filament::section>
      <x-slot name="heading">
        📊 Statistiques d'Erreurs
      </x-slot>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-red-50 p-4 rounded-lg border border-red-200">
          <h4 class="font-semibold text-red-900 mb-2">Erreurs Totales</h4>
          <p class="text-2xl font-bold text-red-600">{{ $errorStats['total_errors'] ?? 0 }}</p>
        </div>

        <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
          <h4 class="font-semibold text-yellow-900 mb-2">Dernières 24h</h4>
          <p class="text-2xl font-bold text-yellow-600">{{ $errorStats['last_24h_errors'] ?? 0 }}</p>
        </div>

        <div class="bg-green-50 p-4 rounded-lg border border-green-200">
          <h4 class="font-semibold text-green-900 mb-2">Types d'Erreurs</h4>
          <p class="text-2xl font-bold text-green-600">{{ count($errorStats['error_types'] ?? []) }}</p>
        </div>
      </div>

      @if(!empty($errorStats['error_types']))
      <div class="mt-4">
        <h5 class="font-medium text-gray-900 mb-3">Répartition par Type</h5>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
          @foreach($errorStats['error_types'] as $type => $count)
          <div class="bg-gray-50 p-3 rounded border">
            <div class="flex justify-between items-center">
              <span class="text-sm font-medium text-gray-700">{{ ucfirst(str_replace('_', ' ', $type)) }}</span>
              <span class="text-sm font-bold text-gray-900">{{ $count }}</span>
            </div>
          </div>
          @endforeach
        </div>
      </div>
      @endif
    </x-filament::section>

    <!-- Types d'erreurs disponibles -->
    <x-filament::section>
      <x-slot name="heading">
        🔧 Types d'Erreurs Testables
      </x-slot>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="space-y-3">
          <div class="flex items-start space-x-3 p-3 bg-red-50 rounded-lg border border-red-200">
            <div class="flex-shrink-0">
              <x-heroicon-o-user-minus class="w-5 h-5 text-red-600 mt-0.5" />
            </div>
            <div>
              <h5 class="font-medium text-red-900">NoValidRoleException</h5>
              <p class="text-sm text-red-700">Utilisateur sans rôle valide assigné</p>
            </div>
          </div>

          <div class="flex items-start space-x-3 p-3 bg-orange-50 rounded-lg border border-orange-200">
            <div class="flex-shrink-0">
              <x-heroicon-o-building-office-2 class="w-5 h-5 text-orange-600 mt-0.5" />
            </div>
            <div>
              <h5 class="font-medium text-orange-900">NoAgencyAccessException</h5>
              <p class="text-sm text-orange-700">Utilisateur sans accès à aucune agence</p>
            </div>
          </div>

          <div class="flex items-start space-x-3 p-3 bg-yellow-50 rounded-lg border border-yellow-200">
            <div class="flex-shrink-0">
              <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-yellow-600 mt-0.5" />
            </div>
            <div>
              <h5 class="font-medium text-yellow-900">InvalidTenantException</h5>
              <p class="text-sm text-yellow-700">Agence invalide ou inaccessible sélectionnée</p>
            </div>
          </div>
        </div>

        <div class="space-y-3">
          <div class="flex items-start space-x-3 p-3 bg-purple-50 rounded-lg border border-purple-200">
            <div class="flex-shrink-0">
              <x-heroicon-o-exclamation-circle class="w-5 h-5 text-purple-600 mt-0.5" />
            </div>
            <div>
              <h5 class="font-medium text-purple-900">PanelRedirectionException</h5>
              <p class="text-sm text-purple-700">Échec de redirection vers un panel</p>
            </div>
          </div>

          <div class="flex items-start space-x-3 p-3 bg-blue-50 rounded-lg border border-blue-200">
            <div class="flex-shrink-0">
              <x-heroicon-o-building-office class="w-5 h-5 text-blue-600 mt-0.5" />
            </div>
            <div>
              <h5 class="font-medium text-blue-900">TenantSelectionRequiredException</h5>
              <p class="text-sm text-blue-700">Sélection d'agence requise pour continuer</p>
            </div>
          </div>

          <div class="flex items-start space-x-3 p-3 bg-gray-50 rounded-lg border border-gray-200">
            <div class="flex-shrink-0">
              <x-heroicon-o-exclamation-circle class="w-5 h-5 text-gray-600 mt-0.5" />
            </div>
            <div>
              <h5 class="font-medium text-gray-900">Exception Générique</h5>
              <p class="text-sm text-gray-700">Erreur générique non spécifique</p>
            </div>
          </div>
        </div>
      </div>
    </x-filament::section>

    <!-- Conseils de débogage -->
    <x-filament::section>
      <x-slot name="heading">
        🐛 Conseils de Débogage
      </x-slot>

      <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
        <ul class="space-y-2 text-sm text-blue-800">
          <li class="flex items-start space-x-2">
            <span class="text-blue-600">•</span>
            <span>Ouvrez les outils de développement (F12) pour voir les logs JavaScript</span>
          </li>
          <li class="flex items-start space-x-2">
            <span class="text-blue-600">•</span>
            <span>Consultez <code class="bg-blue-100 px-1 rounded">storage/logs/laravel.log</code> pour les logs serveur</span>
          </li>
          <li class="flex items-start space-x-2">
            <span class="text-blue-600">•</span>
            <span>Les notifications persistent selon leur type (erreurs critiques = persistantes)</span>
          </li>
          <li class="flex items-start space-x-2">
            <span class="text-blue-600">•</span>
            <span>Chaque notification inclut des actions contextuelles (boutons)</span>
          </li>
          <li class="flex items-start space-x-2">
            <span class="text-blue-600">•</span>
            <span>La configuration est dans <code class="bg-blue-100 px-1 rounded">config/redirection.php</code></span>
          </li>
        </ul>
      </div>
    </x-filament::section>
  </div>
</x-filament-panels::page>