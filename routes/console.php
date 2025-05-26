<?php

use App\Http\Controllers\DocumentController;
use Illuminate\Foundation\Inspiring;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


