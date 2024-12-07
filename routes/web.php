<?php

use App\Models\Customer;
use App\Models\Order;
use App\Models\SizeStore;
use App\Models\Stock;
use App\Models\WalletLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
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

    $stocks = Stock::get();

    foreach ($stocks as $key => $value) {
        SizeStore::where('stock_id', $value->id)->update(['product_id' => $value->product_id]);
    }

    Artisan::call('cache:clear');
    Artisan::call('config:cache');
    Artisan::call('view:clear');
    Artisan::call('route:clear');
    dd(PHP_VERSION);
    return view('welcome');
});


Route::get('/add-new-status', function () {

    DB::table('order_states')->insert(
        [
            // [
            //     'code' => "100",
            //     'new_code' => "100",
            //     'state_en' => "order received",
            //     'state_ar' => "تم استلام الطلب",
            //     'customer_state_en' => "order received",
            //     'customer_state_ar' => "تم استلام الطلب",
            //     'is_active' => 1
            // ],
            // [
            //     'code' => "101",
            //     'new_code' => "101",
            //     'state_en' => "order confirmed",
            //     'state_ar' => "تم تأكيد الطلب",
            //     'customer_state_en' => "order confirmed",
            //     'customer_state_ar' => "تم تأكيد الطلب",
            //     'is_active' => 1
            // ],
            // [
            //     'code' => "102",
            //     'new_code' => "104",
            //     'state_en' => "preparing order",
            //     'state_ar' => "جاري التجهيز",
            //     'customer_state_en' => "preparing order",
            //     'customer_state_ar' => "جاري التجهيز",
            //     'is_active' => 1
            // ],
            // [
            //     'code' => "103",
            //     'new_code' => "105",
            //     'state_en' => "quality assurance",
            //     'state_ar' => "اختبار الجودة",
            //     'customer_state_en' => "Quality assurance",
            //     'customer_state_ar' => "اختبار الجودة",
            //     'is_active' => 1
            // ],
            // [
            //     'code' => "104",
            //     'new_code' => "106",
            //     'state_en' => "out for delivery",
            //     'state_ar' => "جاري التوصيل",
            //     'customer_state_en' => "Out for delivery",
            //     'customer_state_ar' => "جاري التوصيل",
            //     'is_active' => 1
            // ],

            // [
            //     'code' => "107",
            //     'new_code' => "107",
            //     'state_en' => "returned",
            //     'state_ar' => "تم الارجاع",
            //     'customer_state_en' => "returned",
            //     'customer_state_ar' => "تم الارجاع",
            //     'is_active' => 1
            // ],
            // [
            //     'code' => "108",
            //     'new_code' => "108",
            //     'state_en' => "partialy returned",
            //     'state_ar' => "تم الارجاع بشكل جزئي",
            //     'customer_state_en' => "partialy returned",
            //     'customer_state_ar' => "تم الارجاع بشكل جزئي",
            //     'is_active' => 1
            // ],
            // [
            //     'code' => "109",
            //     'new_code' => "109",
            //     'state_en' => "problem in delivered",
            //     'state_ar' => "يوجد مشكلة في التوصيل",
            //     'customer_state_en' => "problem in delivered",
            //     'customer_state_ar' => "يوجد مشكلة في التوصيل",
            //     'is_active' => 1
            // ],
            // [
            //     'code' => "200",
            //     'new_code' => "200",
            //     'state_en' => "delivered",
            //     'state_ar' => "تم التوصيل",
            //     'customer_state_en' => "delivered",
            //     'customer_state_ar' => "تم التوصيل",
            //     'is_active' => 1
            // ],
            // [
            //     'code' => "300",
            //     'new_code' => "301",
            //     'state_en' => "Pickup",
            //     'state_ar' => "الاستلام من الفرع",
            //     'customer_state_en' => "Pickup",
            //     'customer_state_ar' => "الاستلام من الفرع",
            //     'is_active' => 1
            // ],
            // [
            //     'code' => "4000",
            //     'new_code' => "103",
            //     'state_en' => "canceled",
            //     'state_ar' => "ملغي",
            //     'customer_state_en' => "canceled",
            //     'customer_state_ar' => "تم الالغاء",
            //     'is_active' => 1
            // ],
            // [
            //     'code' => "4001",
            //     'new_code' => "102",
            //     'state_en' => "pending",
            //     'state_ar' => "معلق",
            //     'customer_state_en' => "pending",
            //     'customer_state_ar' => "معلق",
            //     'is_active' => 1
            // ],
            /************************************************************************* */

            [
                'code' => "204",
                'new_code' => "204",
                'state_en' => "order changed",
                'state_ar' => "تم تعديل الطلب ",
                'customer_state_en' => "order changed",
                'customer_state_ar' => "تم تعديل الطلب ",
                'is_active' => 1
            ],

            [
                'code' => "208",
                'new_code' => "208",
                'state_en' => "order postponed",
                'state_ar' => "تم تأجيل الطلب",
                'customer_state_en' => "order postponed",
                'customer_state_ar' => "تم تأجيل الطلب",
                'is_active' => 1
            ],

            /************************************************************************* */

            [
                'code' => "201",
                'new_code' => "201",
                'state_en' => "order recieved from branch",
                'state_ar' => "تم استلام الطلب من الفرع",
                'customer_state_en' => "order recieved from branch",
                'customer_state_ar' => "تم استلام الطلب من الفرع",
                'is_active' => 1
            ],
            [
                'code' => "202",
                'new_code' => "202",
                'state_en' => "order preparing from branch",
                'state_ar' => "جاري تجهيز الطلب من الفرع",
                'customer_state_en' => "order preparing from branch",
                'customer_state_ar' => "جاري تجهيز الطلب من الفرع",
                'is_active' => 1
            ],
            [
                'code' => "203",
                'new_code' => "203",
                'state_en' => "order delivered from branch",
                'state_ar' => "تم تسليم الطلب من الفرع",
                'customer_state_en' => "order delivered from branch",
                'customer_state_ar' => "تم تسليم الطلب من الفرع",
                'is_active' => 1
            ],
            [
                'code' => "205",
                'new_code' => "205",
                'state_en' => "new order from branch",
                'state_ar' => "تم اضافة طلب من الفرع",
                'customer_state_en' => "new order from branch",
                'customer_state_ar' => "تم اضافة طلب من الفرع",
                'is_active' => 1
            ],
            [
                'code' => "206",
                'new_code' => "206",
                'state_en' => "order cancelled from branch",
                'state_ar' => "تم الغاء الطلب من الفرع",
                'customer_state_en' => "order cancelled from branch",
                'customer_state_ar' => "تم الغاء الطلب من الفرع",
                'is_active' => 1
            ],

            [
                'code' => "207",
                'new_code' => "207",
                'state_en' => "order returned to branch",
                'state_ar' => "تم ارجاع الطلب الي الفرع",
                'customer_state_en' => "order returned to branch",
                'customer_state_ar' => "تم ارجاع الطلب الي الفرع",
                'is_active' => 1
            ],

            [
                'code' => "209",
                'new_code' => "209",
                'state_en' => "why order cancelled ?",
                'state_ar' => "لماذا تم الغاء الطلب",
                'customer_state_en' => "why order cancelled ?",
                'customer_state_ar' => "لماذا تم الغاء الطلب",
                'is_active' => 1
            ],
        ]
    );
});


Route::get('/remove-log', function () {

    $logs = WalletLog::with('customer')
        ->whereDate('expired_at', date('Y-m-d', strtotime('-1 day')))->get();

    dd($logs->toArray());

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
                'action' => 'expiry',
            ])->first();

            if (!$remove) {

                $new_amount = $log->customer->wallet - $amount;

                if ($new_amount > 0) {
                    $log = WalletLog::create([
                        'user_id' => null,
                        'customer_id' => $log->customer_id,
                        'last_amount' => $log->customer->wallet,
                        'new_amount' => $new_amount > 0 ? $new_amount : 0,
                        'action_id' =>  $log->action_id,
                        'action' => 'expiry',
                        'expired_days' => null,
                        'expired_at' => null,
                        'message_ar' => ' تسوية رصيد منتهي الصلاحية',
                        'message_en' => 'Expired balance settlement '
                    ]);

                    $log->customer->update(['wallet' => $new_amount]);
                }
            }
        }
    }

    dd('done');
});
