<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('/welcomle');
});

Route::get('/admin', function () {
    return redirect('admin');
});

