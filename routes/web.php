<?php

use App\Models\Customer;
use App\Models\WalletLog;
use Carbon\Carbon;
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
        foodics_create_or_update_customer($value);
        sleep(3);
    }
});

Route::group(
    [
        'namespace' => 'Khairy\LaravelSSEStream\Controllers',
        'prefix' => config('sse.prefix', 'sse')
    ],
    static function () {

        Route::get('sse_stream', 'SSEController@stream')->name('__sse_stream__');
    }
);


Route::get('/', function () {
    // Artisan::call('migrate');

  Artisan::call('cache:clear');
    Artisan::call('config:cache');
    Artisan::call('view:clear');
    Artisan::call('route:clear');
    dd(PHP_VERSION);
    return view('welcome');
});


Route::get('/remove-log', function () {

    $logs = WalletLog::with('customer')
        ->whereDate('expired_at', date('Y-m-d'))->get();

    foreach ($logs as $key => $log) {
        $amount = $log->new_amount - $log->last_amount;
        if (
            $log->customer->orders
            ->where('created_at', '>=',  Carbon::parse($log->created_at)->format('Y-m-d'))
            ->where('created_at', '<=', Carbon::parse($log->expired_at)->format('Y-m-d'))
            ->where('total_amount', '>=', $amount)
            ->count() < 1
        ) {

            $remove = WalletLog::where([
                'action_id' => $log->action_id,
                'customer_id' => $log->customer_id,
                'action' => 'remove',
            ])->first();

            if (!$remove) {

                $new_amount = $log->customer->wallet - $amount;

                $log = WalletLog::create([
                    'user_id' => null,
                    'customer_id' => $log->customer_id,
                    'last_amount' => $log->customer->wallet,
                    'new_amount' => $new_amount,
                    'action_id' =>  $log->action_id,
                    'action' => 'remove',
                    'expired_days' => null,
                    'expired_at' => null,
                    'message_ar' => 'خصم كاش باك ',
                    'message_en' => 'remove cashback'
                ]);

                $log->customer->update(['wallet' => $new_amount]);
            }
        }
    }

    dd('done');
});
