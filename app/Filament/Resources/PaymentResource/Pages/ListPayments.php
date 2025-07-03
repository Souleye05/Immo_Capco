<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Filament\Resources\PaymentResource;
use App\Filament\Resources\PaymentResource\Widgets\PaymentStatsOverview;
use App\Models\Payment;
use App\Models\Tenant;
use Filament\Actions;

use Filament\Resources\Pages\ListRecords;
use Filament\Pages\Actions\Action;
use Coolsam\FilamentFlatpickr\Forms\Components\Flatpickr;
use Filament\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ListPayments extends ListRecords
{
    protected static string $resource = PaymentResource::class;

    public $timestamps = true;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),

            Actions\Action::make('generate')
                ->label('Générer')
                ->action('openSettingModal')
                ->form( [
                        Flatpickr::make('current_month')
                            ->monthSelect(),
                    ]
                    ),
        ];
    }

    public function openSettingsModal($data): void
    {
        // $this->dispatchBrowserEvent('open-settings-modal');
        // dd($data['current_month']);

        $pymt = DB::table('flats')
        ->join('payments', 'payments.flat_id', 'flats.id')
        // ->whereBetween('current_month',[$dateS,$dateE])
        ->where('current_month',$data['current_month'])
        ->select('flats.id')
        ->groupBy('flats.id')
        ->get();
        $filter = array_map(fn($value): int => $value->id, $pymt->toArray());
        // dd($filter);

        $pymte = DB::table('flats')
        // ->join('tenants', 'tenants.flat_id', 'flats.id')
        // ->whereBetween('current_month',[$dateS,$dateE])
        ->whereNotIn('flats.id',$filter)
        ->whereNotNull('tenant_id')
        ->select()
        // ->groupBy('flats.id')
        ->get();
        // $pymt = DB::table('payments')
        // ->join('flats', 'payments.flat_id', 'flats.id')
        // ->select('flat_id')
        // // ->groupBy('flats.id')
        // ->get();
        // dd($pymte);
        $insertValue = array();
        // dd($pymte);
        
        foreach ($pymte as $value) {
            // dd($value->tenant_id);
            $array = array(
                'tenant_id' => $value->tenant_id,
                'flat_id' => $value->id,
                'numero' => 'FAC-' . random_int(100000, 999999),
                'current_month' => $data['current_month'],
                'amount' => $value->loyer,
                'status' => 0,
                'date_payment' => null,
                'payment_method' => null,
                'created_at' => new \DateTime(),
            );
            dd($array);
            array_push($insertValue,$array );                   
        }

        $bad = Payment::insert($insertValue);
        //  dd($insertValue); 
        Notification::make()
                    ->success()
                    ->title('Facture(s) générée(s)')
                    ->body("Rafraîchissez la page s'il vous plaît.")
                    ->send();        
    }


    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nouvelle facture')
                ->icon('heroicon-o-plus')
                ,
            Actions\Action::make('GenerateMonthlyPayments')
                ->label('Générer les factures du mois')
                ->icon('heroicon-o-document-text')
                ->color('success')
                ->action(function () {
    $output = Artisan::call('payments:generate');
    $exitCode = Artisan::output();

    if (str_contains($exitCode, 'Aucune nouvelle facture')) {
        Notification::make()
            ->title('Déjà générées')
            ->body('Les factures de ce mois existent déjà.')
            ->warning()
            ->send();
    } else {
        Notification::make()
            ->title('Factures générées')
            ->body('Les factures du mois ont été créées avec succès.')
            ->success()
            ->send();
    }

    $this->dispatch('refresh');
})
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [

            PaymentStatsOverview::class,

        ];
    }
    
    public function getTitle(): string
    {
        return 'Gestion des Factures';
    }

    public function getSubheading(): string
    {
        return 'Gérez vos factures et leurs Types';
    }
}