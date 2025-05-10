<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class InvoiceService
{
    
    public function generateAndStorePdf(string $view, array $data, string $fileName): string
    {
        $pdf = Pdf::loadView($view, $data);
        $path = 'receipts/' . $fileName;

        Storage::disk('public')->put($path, $pdf->output());

        return $path;
    }
}
