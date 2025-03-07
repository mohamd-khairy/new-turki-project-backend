<?php

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\SizeStore;
use App\Models\Stock;
use App\Models\WalletLog;
use App\Services\FirebaseService;
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

Route::group(
    [
        'namespace' => 'Khairy\LaravelSSEStream\Controllers',
        'prefix' => config('sse.prefix', 'sse')
    ],
    static function () {

        Route::get('sse_stream', 'SSEController@stream')->name('__sse_stream__');
    }
);

Route::get('/{ref_no}', function ($ref_no) {
    Artisan::call('cache:clear');
    Artisan::call('config:cache');
    Artisan::call('view:clear');
    Artisan::call('route:clear');
    // dd(PHP_VERSION, 'here');
    // return view('welcome');


    $order = Order::query()
        ->with(
            'paymentType',
            'customer',
            'orderState',
            'deliveryPeriod',
            'selectedAddress',
        )->where('ref_no' , $ref_no)->orderBy('id', 'desc')->first();

    $products = OrderProduct::with('preparation', 'size', 'cut', 'shalwata')
        ->where('order_ref_no', $order->ref_no)
        ->get();


    $new_products = [];
    foreach ($products as $key => $product) {
        #product_1
        if (in_array($product->size_id, [17, 2, 3, 4, 18, 19, 20, 21, 26, 27, 28, 29])) {

            if ($product->is_kwar3 == false) {
                $new_products[] = [
                    'size' =>   [
                        'id' => 1049,
                        'name_ar' => 'كوارع'
                    ],
                    'preparation' =>  (object)[],
                    'cut' =>  (object)[],
                    'shalwata' =>  (object)[],
                    'quantity' => 1,
                ];
            }
            if ($product->is_karashah == false) {
                $new_products[] = [
                    'size' =>   [
                        'id' => 1051,
                        'name_ar' => 'كرشة'
                    ],
                    'preparation' =>  (object)[],
                    'cut' =>  (object)[],
                    'shalwata' =>  (object)[],
                    'quantity' => 1,
                ];
            }
            if ($product->is_lyh == false) {
                $new_products[] = [
                    'size' =>   [
                        'id' => 1054,
                        'name_ar' => 'لية'
                    ],
                    'preparation' =>  (object)[],
                    'cut' =>  (object)[],
                    'shalwata' =>  (object)[],
                    'quantity' => 1,
                ];
            }
            if ($product->is_lyh == false) {
                $new_products[] = [
                    'size' =>   [
                        'id' => 1053,
                        'name_ar' => 'رأس'
                    ],
                    'preparation' =>  (object)[],
                    'cut' =>  (object)[],
                    'shalwata' =>  (object)[],
                    'quantity' => 1,
                ];
            }
        }
        if (in_array($product->size_id, [6, 178, 8, 9, 22, 23, 24, 25, 30, 31, 32, 33])) {

            if ($product->is_kwar3 == false) {
                $new_products[] = [
                    'size' =>   [
                        'id' => 1050,
                        'name_ar' => 'كوارع'
                    ],
                    'preparation' =>  (object)[],
                    'cut' =>  (object)[],
                    'shalwata' =>  (object)[],
                    'quantity' => 1,
                ];
            }
            if ($product->is_karashah == false) {
                $new_products[] = [
                    'size' =>   [
                        'id' => 1052,
                        'name_ar' => 'كرشة'
                    ],
                    'preparation' =>  (object)[],
                    'cut' =>  (object)[],
                    'shalwata' =>  (object)[],
                    'quantity' => 1,
                ];
            }
            if ($product->is_lyh == false) {
                $new_products[] = [
                    'size' =>   [
                        'id' => 1055,
                        'name_ar' => 'لية'
                    ],
                    'preparation' =>  (object)[],
                    'cut' =>  (object)[],
                    'shalwata' =>  (object)[],
                    'quantity' => 1,
                ];
            }
        }
        if ($product->shalwata_id != null) {
            $new_products[] = [
                'size' =>   [
                    'id' => 1056,
                    'name_ar' => 'شلوطة'
                ],
                'preparation' =>  (object)[],
                'cut' =>  (object)[],
                'shalwata' =>  (object)[],
                'quantity' => 1,
            ];
        }
        # code...
        $new_products[] = [
            'size' => $product->size ?  [
                'id' => $product->size->id,
                'name_ar' => $product->size->name_ar
            ] : (object)[],
            'preparation' => $product->preparation ?  [
                'id' => $product->preparation->id,
                'name_ar' => $product->preparation->name_ar
            ] : (object)[],
            'cut' => $product->cut ?  [
                'id' => $product->cut->id,
                'name_ar' => $product->cut->name_ar
            ] : (object)[],
            'shalwata' => $product->shalwata ?  [
                'id' => $product->shalwata->id,
                'name_ar' => $product->shalwata->name_ar
            ] : (object)[],
            'quantity' => $product->quantity,
        ];
    }

    // dd($new_products);

    $result = sendOrderToTurkishop($order, $new_products);
    dd($result);
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
