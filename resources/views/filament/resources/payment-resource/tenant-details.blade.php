<div class="p-4">
    <!-- Informations du locataire uniquement -->
    <div class="p-5 bg-white border border-gray-200 rounded-lg shadow-sm">
        <h3 class="mb-4 text-lg font-semibold text-gray-900">
            Informations du locataire
        </h3>
        
        <div class="grid gap-4 md:grid-cols-2">
            <div class="space-y-3">
                <div class="flex flex-col">
                    <span class="text-sm font-medium text-gray-500">Nom complet</span>
                    <span class="text-base font-medium">{{ $tenant->name }}</span>
                </div>
                
                <div class="flex flex-col">
                    <span class="text-sm font-medium text-gray-500">Téléphone</span>
                    <span class="text-base font-medium">{{ $tenant->phone }}</span>
                </div>
            </div>
            
            <div class="space-y-3">
                <div class="flex flex-col">
                    <span class="text-sm font-medium text-gray-500">Adresse</span>
                    <span class="text-base font-medium">{{ $tenant->address }}</span>
                </div>
                
                <div class="flex flex-col">
                    <span class="text-sm font-medium text-gray-500">Client depuis</span>
                    <span class="text-base font-medium">{{ $tenant->created_at->format('d/m/Y') }}</span>
                </div>
            </div>
        </div>
    </div>
</div>