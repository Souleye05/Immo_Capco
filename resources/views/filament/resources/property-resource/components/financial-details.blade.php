<!-- resources/views/filament/resources/property-resource/components/financial-details.blade.php -->
<div class="space-y-6">
    <div class="overflow-hidden bg-white shadow sm:rounded-lg">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg font-medium leading-6 text-gray-900">
                Résumé financier - {{ $property->name }}
            </h3>
            <p class="max-w-2xl mt-1 text-sm text-gray-500">
                Période: {{ ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'][$month - 1] }} {{ $year }}
            </p>
        </div>
        <div class="border-t border-gray-200">
            <dl>
                <div class="px-4 py-5 bg-gray-50 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">
                        Revenus totaux
                    </dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        {{ number_format($stats['total_revenue'], 0, ',', ' ') }} FCFA
                    </dd>
                </div>
                <div class="px-4 py-5 bg-white sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">
                        Commissions totales
                    </dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        {{ number_format($stats['total_commissions'], 0, ',', ' ') }} FCFA
                    </dd>
                </div>
                <div class="px-4 py-5 bg-gray-50 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">
                        Dépenses des appartements
                    </dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        {{ number_format($stats['total_flat_expenses'], 0, ',', ' ') }} FCFA
                    </dd>
                </div>
                <div class="px-4 py-5 bg-white sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">
                        Dépenses spécifiques à la propriété
                    </dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        {{ number_format($stats['property_specific_expenses'], 0, ',', ' ') }} FCFA
                    </dd>
                </div>
                <div class="px-4 py-5 bg-gray-50 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">
                        Total des dépenses
                    </dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        {{ number_format($stats['total_expenses'], 0, ',', ' ') }} FCFA
                    </dd>
                </div>
                <div class="px-4 py-5 bg-white sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-bold text-gray-500">
                        Montant à reverser
                    </dt>
                    <dd class="mt-1 text-sm sm:mt-0 sm:col-span-2 {{ $stats['amount_to_transfer'] > 0 ? 'text-green-600 font-bold' : 'text-red-600 font-bold' }}">
                        {{ number_format($stats['amount_to_transfer'], 0, ',', ' ') }} FCFA
                    </dd>
                </div>
            </dl>
        </div>
    </div>

    <!-- Détails par appartement -->
    <div class="overflow-hidden bg-white shadow sm:rounded-lg">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg font-medium leading-6 text-gray-900">
                Détails par appartement
            </h3>
        </div>
        <div class="border-t border-gray-200">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                            Appartement
                        </th>
                        <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                            Revenus
                        </th>
                        <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                            Commissions
                        </th>
                        <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                            Dépenses
                        </th>
                        <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                            À reverser
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($stats['flat_stats'] as $flatStat)
                    <tr>
                        <td class="px-6 py-4 text-sm font-medium text-gray-900 whitespace-nowrap">
                            {{ $flatStat['flat_type'] ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">
                            {{ number_format($flatStat['revenue'], 0, ',', ' ') }} FCFA
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">
                            {{ number_format($flatStat['commission'], 0, ',', ' ') }} FCFA
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">
                            {{ number_format($flatStat['expenses'], 0, ',', ' ') }} FCFA
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm {{ $flatStat['amount_to_transfer'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ number_format($flatStat['amount_to_transfer'], 0, ',', ' ') }} FCFA
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>