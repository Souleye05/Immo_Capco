<div class="space-y-4">
  <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
      Informations de la facture
    </h3>
    <div class="grid grid-cols-2 gap-4 text-sm">
      <div>
        <span class="font-medium text-gray-700 dark:text-gray-300">Numéro:</span>
        <span class="text-gray-900 dark:text-white">{{ $payment->numero }}</span>
      </div>
      <div>
        <span class="font-medium text-gray-700 dark:text-gray-300">Type:</span>
        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-{{ $payment->getTypeBadgeColor() }}-100 text-{{ $payment->getTypeBadgeColor() }}-800 dark:bg-{{ $payment->getTypeBadgeColor() }}-900 dark:text-{{ $payment->getTypeBadgeColor() }}-200">
          {{ $payment->getTypeLabel() }}
        </span>
      </div>
      <div>
        <span class="font-medium text-gray-700 dark:text-gray-300">Montant dû:</span>
        <span class="text-gray-900 dark:text-white">{{ number_format($payment->amount, 0, ',', ' ') }} F CFA</span>
      </div>
      <div>
        <span class="font-medium text-gray-700 dark:text-gray-300">Date d'échéance:</span>
        <span class="text-gray-900 dark:text-white">{{ $payment->date_payment ? \Carbon\Carbon::parse($payment->date_payment)->format('d/m/Y') : '-' }}</span>
      </div>
      @if($payment->current_month)
      <div>
        <span class="font-medium text-gray-700 dark:text-gray-300">Mois concerné:</span>
        <span class="text-gray-900 dark:text-white">{{ $payment->current_month }}</span>
      </div>
      @endif
      <div>
        <span class="font-medium text-gray-700 dark:text-gray-300">Statut:</span>
        @if($payment->status)
        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
          Payé intégralement
        </span>
        @else
        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
          Non payé intégralement
        </span>
        @endif
      </div>
    </div>
  </div>

  <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700">
    <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
        Historique des versements
      </h3>
      <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
        Total versé: <span class="font-medium">{{ number_format($payment->amount_paid, 0, ',', ' ') }} F CFA</span>
        @if($payment->amount_remaining > 0)
        • Restant: <span class="font-medium text-red-600 dark:text-red-400">{{ number_format($payment->amount_remaining, 0, ',', ' ') }} F CFA</span>
        @endif
      </p>
    </div>

    @if($versements->count() > 0)
    <div class="divide-y divide-gray-200 dark:divide-gray-700">
      @foreach($versements as $versement)
      <div class="px-4 py-3">
        <div class="flex items-center justify-between">
          <div class="flex-1">
            <div class="flex items-center space-x-3">
              <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center">
                  <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                  </svg>
                </div>
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between">
                  <p class="text-sm font-medium text-gray-900 dark:text-white">
                    {{ number_format($versement->amount, 0, ',', ' ') }} F CFA
                  </p>
                  <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $versement->versement_date ? \Carbon\Carbon::parse($versement->versement_date)->format('d/m/Y') : '-' }}
                  </p>
                </div>
                @if($versement->reference)
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                  Réf: {{ $versement->reference }}
                </p>
                @endif
                @if($versement->payment_method)
                <p class="text-xs text-gray-500 dark:text-gray-400">
                  Méthode: {{ $versement->payment_method }}
                </p>
                @endif
              </div>
            </div>
          </div>
        </div>
      </div>
      @endforeach
    </div>

    <div class="px-4 py-3 bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
      <div class="flex justify-between items-center text-sm">
        <span class="font-medium text-gray-700 dark:text-gray-300">
          Total des versements ({{ $versements->count() }})
        </span>
        <span class="font-semibold text-gray-900 dark:text-white">
          {{ number_format($versements->sum('amount'), 0, ',', ' ') }} F CFA
        </span>
      </div>
      @if($payment->amount_remaining > 0)
      <div class="flex justify-between items-center text-sm mt-2 pt-2 border-t border-gray-200 dark:border-gray-600">
        <span class="font-medium text-red-700 dark:text-red-300">
          Montant restant à payer
        </span>
        <span class="font-semibold text-red-900 dark:text-red-100">
          {{ number_format($payment->amount_remaining, 0, ',', ' ') }} F CFA
        </span>
      </div>
      @endif
    </div>
    @else
    <div class="px-4 py-8 text-center">
      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
      </svg>
      <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Aucun versement</h3>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        Aucun versement n'a encore été effectué pour cette facture.
      </p>
    </div>
    @endif
  </div>
</div>