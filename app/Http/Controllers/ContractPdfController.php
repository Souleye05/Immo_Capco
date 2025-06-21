<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class ContractPdfController extends Controller
{
    //
    public function generatePdf(Contract $contract)
    {
        $contract->load(['flat', 'tenant']);

        // générer le PDF
        $pdf = Pdf::loadView('contracts.pdf', compact('contract'));

        // nom du fichier
        $filename = "Contrat_{$contract->contract_number}.pdf";
        // return $pdf->stream($filename);
        return $pdf->download($filename);
    }

    public function viewPdf(Contract $contract)
    {
        $contract->load(['flat', 'tenant']);

        // générer le PDF
        $pdf = Pdf::loadView('contracts.pdf', compact('contract'));

        // afficher le PDF dans le navigateur
        return $pdf->stream("Contrat_{$contract->contract_number}.pdf");
    }
}
