<?php

use App\Http\Controllers\Api\RfidController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/rfid/scan', [RfidController::class, 'scan']);
