<?php

use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    return response()->json(['Não disponível', 400]);
});
