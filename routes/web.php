<?php

use App\Models\Customer;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/



Route::get('/customers', function () {
    $customers = Customer::whereNull('foodics_integrate_id')->get();

    foreach ($customers as $key => $value) {
        foodics_create_customer($value);
        sleep(3);
    }
});

Route::get('/', function () {
    // Artisan::call('migrate');
    // Artisan::call('cache:clear');
    return view('welcome');
});
