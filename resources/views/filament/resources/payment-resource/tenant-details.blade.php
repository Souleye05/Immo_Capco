<div class="max-w-full p-6 mx-auto space-y-8">
    <!-- En-tête du profil -->
    <div class="flex flex-col items-center justify-center p-6 border border-blue-100 shadow-md md:flex-row bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl">
        <div class="flex items-center mb-4 md:mb-0">
            <div class="flex items-center justify-center w-16 h-16 mr-4 text-blue-600 bg-blue-100 rounded-full">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
            </div>
            <div>
                <h2 class="text-2xl font-bold text-gray-800">{{ $tenant->name }}</h2>
                <p class="text-gray-500">Client depuis {{ $tenant->created_at->format('d/m/Y') }}</p>
            </div>
        </div>
        <div class="flex space-x-2">
            <button class="flex items-center px-4 py-2 text-white transition-colors bg-blue-500 rounded-lg hover:bg-blue-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                Contact
            </button>
        </div>
    </div>
    
    <!-- Informations du locataire -->
    <div class="p-6 transition-shadow bg-white border border-gray-100 shadow-sm rounded-xl hover:shadow-md">
        <h3 class="flex items-center mb-5 text-lg font-semibold text-gray-800">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-3 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Informations personnelles
        </h3>
        
        <div class="grid gap-6 md:grid-cols-2">
            <div class="space-y-4">
                <div class="p-4 transition-colors rounded-lg group bg-gray-50 hover:bg-blue-50">
                    <span class="text-sm font-medium text-gray-500 transition-colors group-hover:text-blue-500">Nom complet</span>
                    <div class="mt-1 text-base font-medium">{{ $tenant->name }}</div>
                </div>
                
                <div class="p-4 transition-colors rounded-lg group bg-gray-50 hover:bg-blue-50">
                    <span class="text-sm font-medium text-gray-500 transition-colors group-hover:text-blue-500">Téléphone</span>
                    <div class="flex items-center mt-1 text-base font-medium">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-2 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>
                        {{ $tenant->phone }}
                    </div>
                </div>
            </div>
            
            <div class="space-y-4">
                <div class="p-4 transition-colors rounded-lg group bg-gray-50 hover:bg-blue-50">
                    <span class="text-sm font-medium text-gray-500 transition-colors group-hover:text-blue-500">Adresse</span>
                    <div class="flex items-start mt-1 text-base font-medium">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mt-1 mr-2 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span>{{ $tenant->address }}</span>
                    </div>
                </div>
                
                <div class="p-4 transition-colors rounded-lg group bg-gray-50 hover:bg-blue-50">
                    <span class="text-sm font-medium text-gray-500 transition-colors group-hover:text-blue-500">Date d'inscription</span>
                    <div class="flex items-center mt-1 text-base font-medium">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-2 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        {{ $tenant->created_at->format('d/m/Y') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Informations de l'appartement -->
    @if($flat)
    <div class="p-6 transition-shadow bg-white border border-gray-100 shadow-sm rounded-xl hover:shadow-md">
        <h3 class="flex items-center mb-5 text-lg font-semibold text-gray-800">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-3 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            Appartement loué
        </h3>
        
        <div class="grid gap-6 md:grid-cols-2">
            <div class="space-y-4">
                <div class="p-4 transition-colors rounded-lg group bg-gray-50 hover:bg-blue-50">
                    <span class="text-sm font-medium text-gray-500 transition-colors group-hover:text-blue-500">Référence</span>
                    <div class="flex items-center mt-1 text-base font-medium">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-2 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                        </svg>
                        {{ $flat->reference }}
                    </div>
                </div>
                
                @if(isset($flat->type))
                <div class="p-4 transition-colors rounded-lg group bg-gray-50 hover:bg-blue-50">
                    <span class="text-sm font-medium text-gray-500 transition-colors group-hover:text-blue-500">Type de logement</span>
                    <div class="flex items-center mt-1 text-base font-medium">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-2 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        {{ $flat->type }}
                    </div>
                </div>
                @endif
            </div>
            
            <div class="space-y-4">
                <div class="p-4 border border-blue-100 rounded-lg bg-blue-50">
                    <span class="text-sm font-medium text-blue-500">Loyer mensuel</span>
                    <div class="mt-1 text-xl font-bold text-blue-700">
                        {{ number_format($flat->loyer, 0, ',', ' ') }} F CFA
                    </div>
                </div>
                
                @if(isset($flat->etage))
                <div class="p-4 transition-colors rounded-lg group bg-gray-50 hover:bg-blue-50">
                    <span class="text-sm font-medium text-gray-500 transition-colors group-hover:text-blue-500">Étage</span>
                    <div class="flex items-center mt-1 text-base font-medium">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-2 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                        </svg>
                        {{ $flat->etage }}
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif
    
    <!-- Statistiques de paiement -->
    <div class="p-6 transition-shadow bg-white border border-gray-100 shadow-sm rounded-xl hover:shadow-md">
        <h3 class="flex items-center mb-5 text-lg font-semibold text-gray-800">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-3 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Historique des paiements
        </h3>
        
        @php
            // Récupération sécurisée des statistiques de paiement
            $payments = app(\App\Models\Payment::class)->where('tenant_id', $tenant->id)->get();
            $totalPayments = $payments->count();
            $paidPayments = $payments->where('status', true)->count();
            $unpaidPayments = $payments->where('status', false)->count();
            $totalPaidAmount = $payments->where('status', true)->sum('amount');
            
            // Calcul du pourcentage pour le graphique
            $paidPercentage = $totalPayments > 0 ? ($paidPayments / $totalPayments) * 100 : 0;
        @endphp
        
        <div class="h-3 mb-6 overflow-hidden bg-gray-100 rounded-full">
            <!-- <div class="h-full rounded-full bg-gradient-to-r from-blue-500 to-green-500" style="width: {{ $paidPercentage }}%"></div>
              -->
            <div class="h-full rounded-full bg-gradient-to-r from-blue-500 via-blue-300 to-green-500" style="width: {{ $paidPercentage }}%"></div>
        </div>
        
        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
            <div class="p-4 transition-colors border border-gray-100 rounded-xl bg-gray-50 hover:border-blue-200">
                <span class="text-xs font-medium tracking-wider text-gray-500 uppercase">Total factures</span>
                <div class="mt-2 text-2xl font-bold text-gray-800">{{ $totalPayments }}</div>
            </div>
            
            <div class="p-4 transition-colors border border-green-100 rounded-xl bg-green-50 hover:border-green-200">
                <span class="text-xs font-medium tracking-wider text-green-600 uppercase">Factures payées</span>
                <div class="flex items-center mt-2 text-2xl font-bold text-green-600">
                    {{ $paidPayments }}
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            
            <div class="p-4 transition-colors border border-red-100 rounded-xl bg-red-50 hover:border-red-200">
                <span class="text-xs font-medium tracking-wider text-red-600 uppercase">Factures impayées</span>
                <div class="flex items-center mt-2 text-2xl font-bold text-red-600">
                    {{ $unpaidPayments }}
                    @if($unpaidPayments > 0)
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    @endif
                </div>
            </div>
            
            <div class="p-4 transition-colors border border-blue-100 rounded-xl bg-blue-50 hover:border-blue-200">
                <span class="text-xs font-medium tracking-wider text-blue-600 uppercase">Montant total payé</span>
                <div class="mt-2 text-2xl font-bold text-blue-700">
                    {{ number_format($totalPaidAmount, 0, ',', ' ') }} F CFA
                </div>
            </div>
        </div>
        
    </div>
    
    <!-- Boutons d'action -->
    <!--  -->
</div>