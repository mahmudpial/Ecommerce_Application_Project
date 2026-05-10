<?php

use Illuminate\Support\Facades\Route;

Route::get('/login', function () {
    return response('Unauthenticated', 401);
})->name('login');