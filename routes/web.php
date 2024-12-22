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
    dd(PHP_VERSION, 'here');
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

    // $logs = WalletLog::with('customer')
    //     ->whereDate('expired_at', date('Y-m-d', strtotime('-1 day')))
    //     // ->whereIn('action', ['cash_back', 'expiry'])
    //     ->get();
    //     // ->groupBy('action_id');

    $logs = WalletLog::with('customer')
        ->whereNotNull('expired_at')
        ->where('action', 'cash_back')
        ->whereDate('expired_at', '<', date('Y-m-d', strtotime('-1 day')))
        ->get();

    dd($logs->toArray());

    foreach ($logs as $key => $log) {
        $amount = $log->new_amount - $log->last_amount;

        if (
            $log->customer->orders
            ->where('created_at', '>=',  Carbon::parse($log->created_at))
            ->where('created_at', '<=', Carbon::parse($log->expired_at))
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

Route::get('/add-stock', function () {
    $data = [
        ['store_id' => 11, 'product_id' => 1297, 'invoice_id' => null, 'product_name' => 'رول تغليف شفاف', 'quantity' => 12, 'price' => 1],
        ['store_id' => 11, 'product_id' => 1282, 'invoice_id' => null, 'product_name' => 'أكياس معرض حمراء صغيره', 'quantity' => 15, 'price' => 1],
        ['store_id' => 11, 'product_id' => 1281, 'invoice_id' => null, 'product_name' => 'أكياس معرض حمراء كبيره', 'quantity' => 8, 'price' => 1],
        ['store_id' => 11, 'product_id' => 1299, 'invoice_id' => null, 'product_name' => 'أكياس دجاج 900 جم', 'quantity' => 6, 'price' => 1],
        ['store_id' => 11, 'product_id' => 1300, 'invoice_id' => null, 'product_name' => 'أكياس دجاج 1000جم', 'quantity' => 3, 'price' => 1],
        ['store_id' => 11, 'product_id' => 1301, 'invoice_id' => null, 'product_name' => 'أكياس دجاج 1100', 'quantity' => 3, 'price' => 1],
        ['store_id' => 11, 'product_id' => 1298, 'invoice_id' => null, 'product_name' => 'أكياس دجاج 800 جم', 'quantity' => 6, 'price' => 1],
        ['store_id' => 11, 'product_id' => 1284, 'invoice_id' => null, 'product_name' => 'أكياس كرتون صغير', 'quantity' => 37, 'price' => 1],
        ['store_id' => 11, 'product_id' => 1297, 'invoice_id' => null, 'product_name' => 'رول تغليف يدوي', 'quantity' => 33, 'price' => 1],
        ['store_id' => 11, 'product_id' => 1287, 'invoice_id' => null, 'product_name' => 'اطباق بلاستيك مشاوي اسود', 'quantity' => 1, 'price' => 1],
        ['store_id' => 11, 'product_id' => 1279, 'invoice_id' => null, 'product_name' => 'كرتون بني صغير', 'quantity' => 6, 'price' => 1],
        ['store_id' => 11, 'product_id' => 1277, 'invoice_id' => null, 'product_name' => 'كرتون احمر تركي كبير', 'quantity' => 38, 'price' => 1],
        ['store_id' => 11, 'product_id' => 1277, 'invoice_id' => null, 'product_name' => 'كرتون بني كبير', 'quantity' => 25, 'price' => 1],
        ['store_id' => 11, 'product_id' => 1293, 'invoice_id' => null, 'product_name' => 'اطباق فلين اسود صغير', 'quantity' => 17, 'price' => 1],
        ['store_id' => 11, 'product_id' => 1288, 'invoice_id' => null, 'product_name' => 'اطباق بيرغر', 'quantity' => 10, 'price' => 1],
        ['store_id' => 11, 'product_id' => 1294, 'invoice_id' => null, 'product_name' => 'وسائد امتصاص سوائل اللحوم', 'quantity' => 1, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1382, 'invoice_id' => null, 'product_name' => 'جبنة كيري مربعات بالقشطة قابلة للدهن 24 قطعة 800 جرام', 'quantity' => 26, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1324, 'invoice_id' => null, 'product_name' => 'جبنة بوك 500 جرام', 'quantity' => 20, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1354, 'invoice_id' => null, 'product_name' => 'جبنة بوك 130 جرام', 'quantity' => 55, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1374, 'invoice_id' => null, 'product_name' => 'جبن بوك شرائح 10 قطع', 'quantity' => 45, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1330, 'invoice_id' => null, 'product_name' => 'جبنة شيدر كرفت 50 جرام', 'quantity' => 350, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1327, 'invoice_id' => null, 'product_name' => 'جبنة موزريلا المراعي 450 جرام', 'quantity' => 24, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1312, 'invoice_id' => null, 'product_name' => 'قشطة بوك 160 جم', 'quantity' => 206, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1356, 'invoice_id' => null, 'product_name' => 'حليب مبخر أبو قوس 170 جرام', 'quantity' => 63, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1357, 'invoice_id' => null, 'product_name' => 'حليب بودرة أبو قوس 1800 جرام', 'quantity' => 6, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1379, 'invoice_id' => null, 'product_name' => 'حليب سائل بوني 170 جرام', 'quantity' => 35, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1384, 'invoice_id' => null, 'product_name' => 'حليب أبو قوس 1 لتر', 'quantity' => 15, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1313, 'invoice_id' => null, 'product_name' => 'كريمة الخفق بوك 500 مل', 'quantity' => 56, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1311, 'invoice_id' => null, 'product_name' => 'كريمة الطبخ بوك 500 مل', 'quantity' => 29, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1310, 'invoice_id' => null, 'product_name' => 'لبنة بوك 400 جرام', 'quantity' => 50, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1336, 'invoice_id' => null, 'product_name' => 'سمن غنم 1 ك سيدي هشام', 'quantity' => 8, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1355, 'invoice_id' => null, 'product_name' => 'سمن بقري الكرسي الذهبي 800 جرام', 'quantity' => 17, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1326, 'invoice_id' => null, 'product_name' => 'زبدة لور باك', 'quantity' => 70, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1307, 'invoice_id' => null, 'product_name' => 'شطة رنا', 'quantity' => 288, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1323, 'invoice_id' => null, 'product_name' => 'الخردل الأصفر صغير 100 مل', 'quantity' => 46, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1325, 'invoice_id' => null, 'product_name' => 'مايونيز قودي 491 مل', 'quantity' => 100, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1329, 'invoice_id' => null, 'product_name' => 'شطة خضراء حارة رنا 250 مل', 'quantity' => 51, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1323, 'invoice_id' => null, 'product_name' => 'كاتشب هينز 342 جرام', 'quantity' => 36, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1337, 'invoice_id' => null, 'product_name' => 'الدقوس المعتدل 260 جرام', 'quantity' => 21, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1340, 'invoice_id' => null, 'product_name' => 'معجون طماطم السعودية (135*8) جرام', 'quantity' => 26, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1341, 'invoice_id' => null, 'product_name' => 'صلصة الباربكيو الاصلية فرشلي', 'quantity' => 4, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1344, 'invoice_id' => null, 'product_name' => 'مخلل خيار رنا 660 جرام', 'quantity' => 30, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1309, 'invoice_id' => null, 'product_name' => 'ملح ساسا 700 جرام', 'quantity' => 161, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1328, 'invoice_id' => null, 'product_name' => 'ملح الهيمالايا دعزاز 750 مل', 'quantity' => 22, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1334, 'invoice_id' => null, 'product_name' => 'هيل زهرتين 250 جرام', 'quantity' => 103, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1335, 'invoice_id' => null, 'product_name' => 'بهارات الكبسة صرة 200 جرام', 'quantity' => 24, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1342, 'invoice_id' => null, 'product_name' => 'ليمون اسود 500 جرام', 'quantity' => 8.8, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1343, 'invoice_id' => null, 'product_name' => 'ورق غار 100 جرام', 'quantity' => 3.6, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1345, 'invoice_id' => null, 'product_name' => 'قرفة اعواد 250 جرام', 'quantity' => 3.75, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1390, 'invoice_id' => null, 'product_name' => 'مسحوق الكركم', 'quantity' => 224, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1314, 'invoice_id' => null, 'product_name' => 'ارز أبو كاس بسمتي 5 كيلو', 'quantity' => 109, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1363, 'invoice_id' => null, 'product_name' => 'رز بسمتي هندي كلاسيكي عنبر 5 كيلو', 'quantity' => 65, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1368, 'invoice_id' => null, 'product_name' => 'رز الشعلان 5 كيلو', 'quantity' => 96, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1369, 'invoice_id' => null, 'product_name' => 'رز صنوايت 5 كيلو', 'quantity' => 30, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1372, 'invoice_id' => null, 'product_name' => 'مكرونة قودي خواتم 450 جرام', 'quantity' => 150, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1320, 'invoice_id' => null, 'product_name' => 'مكرونة قودي اسباكتي 450 جرام', 'quantity' => 284, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1321, 'invoice_id' => null, 'product_name' => 'مكرونة قودي أقلام 450 جرام', 'quantity' => 270, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1373, 'invoice_id' => null, 'product_name' => 'شعيرية قودي 250 جرام', 'quantity' => 32, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1378, 'invoice_id' => null, 'product_name' => 'دقيق فاخر كويتي 1 كيلو', 'quantity' => 69, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1380, 'invoice_id' => null, 'product_name' => 'كوير شوفان ابيض 500 جرام', 'quantity' => 31, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1308, 'invoice_id' => null, 'product_name' => 'عسل الشفاء 250 جرام', 'quantity' => 100, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1398, 'invoice_id' => null, 'product_name' => 'تونا قودي بزيت دوار الشمس 185 جرام', 'quantity' => 514, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1397, 'invoice_id' => null, 'product_name' => 'تونا جيشا خفيف 185 جرام', 'quantity' => 239, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1375, 'invoice_id' => null, 'product_name' => 'النخلة - حلاوة طحنية 500 جرام', 'quantity' => 37, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1377, 'invoice_id' => null, 'product_name' => 'العلالي - مربى الكرز 400 جرام', 'quantity' => 13, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1325, 'invoice_id' => null, 'product_name' => 'مايونيز قودي 425 جم', 'quantity' => 0, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1395, 'invoice_id' => null, 'product_name' => 'لونا بازيلاء', 'quantity' => 115, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1394, 'invoice_id' => null, 'product_name' => 'فول مدمس حدائق كاليفورنيا', 'quantity' => 0, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1316, 'invoice_id' => null, 'product_name' => 'شاي ليبتون علاق 100 كيس', 'quantity' => 20, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1371, 'invoice_id' => null, 'product_name' => 'شاي الكبوس اسود ناعم 227 جرام', 'quantity' => 166, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1333, 'invoice_id' => null, 'product_name' => 'قهوة ضيافة سعودية 500 جرام', 'quantity' => 22, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1315, 'invoice_id' => null, 'product_name' => 'سكر الاسرة 1 كجم', 'quantity' => 252, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1381, 'invoice_id' => null, 'product_name' => 'سكر مكعبات ابيض 500 جرام', 'quantity' => 44, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1306, 'invoice_id' => null, 'product_name' => 'زيت عافية 1.5 لتر', 'quantity' => 45, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1338, 'invoice_id' => null, 'product_name' => 'زيت زيتون بكر 250 مل', 'quantity' => 50, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1339, 'invoice_id' => null, 'product_name' => 'مكعبات مرقة الدجاج ماجي ( 180*30 )', 'quantity' => 172, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1364, 'invoice_id' => null, 'product_name' => 'خل رنا ابيض 474 جرام', 'quantity' => 15, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1365, 'invoice_id' => null, 'product_name' => 'خل رنا اسود 180 مل', 'quantity' => 38, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1366, 'invoice_id' => null, 'product_name' => 'زيتون اخضر شرائح 114 جرام', 'quantity' => 13, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1367, 'invoice_id' => null, 'product_name' => 'زيتون اسود شرائح 114 جرام', 'quantity' => 8, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1370, 'invoice_id' => null, 'product_name' => 'ذرة حب فرشلي 340 جرام', 'quantity' => 23, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1376, 'invoice_id' => null, 'product_name' => 'طحينة فاخرة سائلة 500 جرام', 'quantity' => 18, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1317, 'invoice_id' => null, 'product_name' => 'بيبسي', 'quantity' => 307, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1318, 'invoice_id' => null, 'product_name' => 'سفن اب', 'quantity' => 380, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1319, 'invoice_id' => null, 'product_name' => 'ميرندا برتقال', 'quantity' => 488, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1383, 'invoice_id' => null, 'product_name' => 'كولا', 'quantity' => 396, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1385, 'invoice_id' => null, 'product_name' => 'عصير المراعي المانجو 200 مل', 'quantity' => 13, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1386, 'invoice_id' => null, 'product_name' => 'عصير المراعي رمان 200 مل', 'quantity' => 8, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1387, 'invoice_id' => null, 'product_name' => 'عصير المراعي الفواكه المشكلة 200 مل', 'quantity' => 22, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1388, 'invoice_id' => null, 'product_name' => 'عصير المراعي تفاح 200 مل', 'quantity' => 22, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1391, 'invoice_id' => null, 'product_name' => 'بيبسي 2.250 عائلة', 'quantity' => 27, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1392, 'invoice_id' => null, 'product_name' => 'سفن أب 2.250 عائلة', 'quantity' => 19, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1399, 'invoice_id' => null, 'product_name' => 'ميرندا برتقال 2.250 عائلة', 'quantity' => 22, 'price' => 1],
        ['store_id' => 12, 'product_id' => 1393, 'invoice_id' => null, 'product_name' => 'ورق قصدير', 'quantity' => 51, 'price' => 1],
        ['store_id' => 9, 'product_id' => 1302, 'invoice_id' => null, 'product_name' => 'لحم اغنام', 'quantity' => 0, 'price' => 1],
        ['store_id' => 9, 'product_id' => 1304, 'invoice_id' => null, 'product_name' => 'عجل', 'quantity' => 0, 'price' => 1],
        ['store_id' => 9, 'product_id' => 1305, 'invoice_id' => null, 'product_name' => 'حاشي', 'quantity' => 0, 'price' => 1],
        ['store_id' => 9, 'product_id' => 645, 'invoice_id' => null, 'product_name' => 'كبدة عجل', 'quantity' => 0, 'price' => 1],
        ['store_id' => 9, 'product_id' => 655, 'invoice_id' => null, 'product_name' => 'كبدة حاشى', 'quantity' => 6.1, 'price' => 1],
        ['store_id' => 9, 'product_id' => 1409, 'invoice_id' => null, 'product_name' => 'لية', 'quantity' => 109, 'price' => 1],
        ['store_id' => 9, 'product_id' => 1410, 'invoice_id' => null, 'product_name' => 'راس سلخ عدد', 'quantity' => 29, 'price' => 1],
        ['store_id' => 9, 'product_id' => 644, 'invoice_id' => null, 'product_name' => 'كبدة غنم', 'quantity' => 20, 'price' => 1],
        ['store_id' => 9, 'product_id' => 646, 'invoice_id' => null, 'product_name' => 'ارجل سلخ عدد', 'quantity' => 725, 'price' => 1],
        ['store_id' => 9, 'product_id' => 1410, 'invoice_id' => null, 'product_name' => 'راس شلوطة عدد', 'quantity' => 15, 'price' => 1],
        ['store_id' => 9, 'product_id' => 646, 'invoice_id' => null, 'product_name' => 'ارجل شلوطة عدد', 'quantity' => 53, 'price' => 1],
        ['store_id' => 9, 'product_id' => 1411, 'invoice_id' => null, 'product_name' => 'شحم بطن', 'quantity' => 0, 'price' => 1],
        ['store_id' => 9, 'product_id' => 1412, 'invoice_id' => null, 'product_name' => 'مصران', 'quantity' => 16, 'price' => 1],
        ['store_id' => 9, 'product_id' => 1413, 'invoice_id' => null, 'product_name' => 'كرشة /عدد', 'quantity' => 302, 'price' => 1],
        ['store_id' => 9, 'product_id' => 1414, 'invoice_id' => null, 'product_name' => 'كلاوى حاشي', 'quantity' => 0, 'price' => 1],
        ['store_id' => 9, 'product_id' => 1415, 'invoice_id' => null, 'product_name' => 'قلب حاشي', 'quantity' => 0, 'price' => 1],
        ['store_id' => 9, 'product_id' => 975, 'invoice_id' => null, 'product_name' => 'كوارع عجل عدد', 'quantity' => 34, 'price' => 1],
        ['store_id' => 9, 'product_id' => 1416, 'invoice_id' => null, 'product_name' => 'كلاوي عجل', 'quantity' => 0, 'price' => 1],
        ['store_id' => 9, 'product_id' => 1417, 'invoice_id' => null, 'product_name' => 'قلب عجل', 'quantity' => 0, 'price' => 1],
        ['store_id' => 9, 'product_id' => 1418, 'invoice_id' => null, 'product_name' => 'كلاوي غنم', 'quantity' => 399, 'price' => 1],
        ['store_id' => 9, 'product_id' => 1419, 'invoice_id' => null, 'product_name' => 'قلوب غنم', 'quantity' => 51, 'price' => 1],
        ['store_id' => 9, 'product_id' => 1420, 'invoice_id' => null, 'product_name' => 'عصاعيص', 'quantity' => 0, 'price' => 1],
        ['store_id' => 9, 'product_id' => 1421, 'invoice_id' => null, 'product_name' => 'سنام', 'quantity' => 13.6, 'price' => 1],
        ['store_id' => 9, 'product_id' => 1422, 'invoice_id' => null, 'product_name' => 'نتر غنم', 'quantity' => 0, 'price' => 1],
        ['store_id' => 9, 'product_id' => 1423, 'invoice_id' => null, 'product_name' => 'نتر عجل', 'quantity' => 0, 'price' => 1],
        ['store_id' => 9, 'product_id' => 1424, 'invoice_id' => null, 'product_name' => 'نتر حاشي', 'quantity' => 0, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1302, 'invoice_id' => null, 'product_name' => 'لحم اغنام', 'quantity' => 430.1, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1164, 'invoice_id' => null, 'product_name' => 'تيس', 'quantity' => 38.4, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1155, 'invoice_id' => null, 'product_name' => 'عجل', 'quantity' => 228.2, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1154, 'invoice_id' => null, 'product_name' => 'حاشي', 'quantity' => 157, 'price' => 1],
        ['store_id' => 10, 'product_id' => 645, 'invoice_id' => null, 'product_name' => 'كبدة عجل', 'quantity' => 4.8, 'price' => 1],
        ['store_id' => 10, 'product_id' => 902, 'invoice_id' => null, 'product_name' => 'كبدة حاشى', 'quantity' => 4.8, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1409, 'invoice_id' => null, 'product_name' => 'لية', 'quantity' => 25, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1410, 'invoice_id' => null, 'product_name' => 'راس سلخ عدد', 'quantity' => 36, 'price' => 1],
        ['store_id' => 10, 'product_id' => 644, 'invoice_id' => null, 'product_name' => 'كبدة غنم عدد', 'quantity' => 98, 'price' => 1],
        ['store_id' => 10, 'product_id' => 646, 'invoice_id' => null, 'product_name' => 'ارجل سلخ عدد', 'quantity' => 164, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1403, 'invoice_id' => null, 'product_name' => 'راس شلوطة عدد', 'quantity' => 0, 'price' => 1],
        ['store_id' => 10, 'product_id' => 646, 'invoice_id' => null, 'product_name' => 'ارجل شلوطة عدد', 'quantity' => 0, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1411, 'invoice_id' => null, 'product_name' => 'شحم بطن', 'quantity' => 0, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1412, 'invoice_id' => null, 'product_name' => 'مصران', 'quantity' => 6, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1413, 'invoice_id' => null, 'product_name' => 'كرشة /عدد', 'quantity' => 40, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1414, 'invoice_id' => null, 'product_name' => 'كلاوى حاشي', 'quantity' => 1, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1415, 'invoice_id' => null, 'product_name' => 'قلب حاشي', 'quantity' => 1.5, 'price' => 1],
        ['store_id' => 10, 'product_id' => 579, 'invoice_id' => null, 'product_name' => 'كوارع عجل عدد', 'quantity' => 0, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1416, 'invoice_id' => null, 'product_name' => 'كلاوي عجل', 'quantity' => 0.9, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1417, 'invoice_id' => null, 'product_name' => 'قلب عجل', 'quantity' => 2, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1418, 'invoice_id' => null, 'product_name' => 'كلاوي غنم', 'quantity' => 110, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1419, 'invoice_id' => null, 'product_name' => 'قلوب غنم', 'quantity' => 50, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1420, 'invoice_id' => null, 'product_name' => 'عصاعيص', 'quantity' => 0, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1421, 'invoice_id' => null, 'product_name' => 'سنام', 'quantity' => 13, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1422, 'invoice_id' => null, 'product_name' => 'نتر غنم', 'quantity' => 0, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1423, 'invoice_id' => null, 'product_name' => 'نتر عجل', 'quantity' => 0, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1424, 'invoice_id' => null, 'product_name' => 'نتر حاشي', 'quantity' => 0, 'price' => 1],
        ['store_id' => 10, 'product_id' => 574, 'invoice_id' => null, 'product_name' => 'مفروم عجل', 'quantity' => 0.5, 'price' => 1],
        ['store_id' => 10, 'product_id' => 904, 'invoice_id' => null, 'product_name' => 'كفتة عجل', 'quantity' => 1, 'price' => 1],
        ['store_id' => 10, 'product_id' => 775, 'invoice_id' => null, 'product_name' => 'مفروم دجاج', 'quantity' => 1.1, 'price' => 1],
        ['store_id' => 10, 'product_id' => 477, 'invoice_id' => null, 'product_name' => 'فاهيتا دجاج', 'quantity' => 0.9, 'price' => 1],
        ['store_id' => 10, 'product_id' => 470, 'invoice_id' => null, 'product_name' => 'شاورما دجاج', 'quantity' => 0.8, 'price' => 1],
        ['store_id' => 10, 'product_id' => 472, 'invoice_id' => null, 'product_name' => 'شاورما لحم', 'quantity' => 0.9, 'price' => 1],
        ['store_id' => 10, 'product_id' => 476, 'invoice_id' => null, 'product_name' => 'فاهيتا لحم', 'quantity' => 0.8, 'price' => 1],
        ['store_id' => 10, 'product_id' => 469, 'invoice_id' => null, 'product_name' => 'دجاج متبل', 'quantity' => 1, 'price' => 1],
        ['store_id' => 10, 'product_id' => 876, 'invoice_id' => null, 'product_name' => 'أوصال غنم', 'quantity' => 0.5, 'price' => 1],
        ['store_id' => 10, 'product_id' => 481, 'invoice_id' => null, 'product_name' => 'كباب دجاج', 'quantity' => 6.4, 'price' => 1],
        ['store_id' => 10, 'product_id' => 482, 'invoice_id' => null, 'product_name' => 'شيش طاؤؤق', 'quantity' => 7.5, 'price' => 1],
        ['store_id' => 10, 'product_id' => 465, 'invoice_id' => null, 'product_name' => 'برجر دجاج', 'quantity' => 6.4, 'price' => 1],
        ['store_id' => 10, 'product_id' => 880, 'invoice_id' => null, 'product_name' => 'كباب لحم', 'quantity' => 6.7, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1359, 'invoice_id' => null, 'product_name' => 'منقل صغير', 'quantity' => 16, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1360, 'invoice_id' => null, 'product_name' => 'سيخ شواء', 'quantity' => 21, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1044, 'invoice_id' => null, 'product_name' => 'أورنكس اعواد خشبية500 حبة', 'quantity' => 8, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1041, 'invoice_id' => null, 'product_name' => 'فحم الشرقية مكعبات 1 كجم', 'quantity' => 6, 'price' => 1],
        ['store_id' => 10, 'product_id' => 934, 'invoice_id' => null, 'product_name' => 'خبز البطاطس', 'quantity' => 5, 'price' => 1],
        ['store_id' => 10, 'product_id' => 935, 'invoice_id' => null, 'product_name' => 'خبز برجر بالسمسم', 'quantity' => 10, 'price' => 1],
        ['store_id' => 10, 'product_id' => 943, 'invoice_id' => null, 'product_name' => 'خبز سندويش صب بالسمسم', 'quantity' => 3, 'price' => 1],
        ['store_id' => 10, 'product_id' => 936, 'invoice_id' => null, 'product_name' => 'خبز هوت دوغ', 'quantity' => 3, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1389, 'invoice_id' => null, 'product_name' => 'خبز التوست', 'quantity' => 4, 'price' => 1],
        ['store_id' => 10, 'product_id' => 838, 'invoice_id' => null, 'product_name' => 'خبز تورتيلا عادي', 'quantity' => 13, 'price' => 1],
        ['store_id' => 10, 'product_id' => 348, 'invoice_id' => null, 'product_name' => 'دجاج 800جرام', 'quantity' => 19, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1196, 'invoice_id' => null, 'product_name' => 'دجاج 900 جرام', 'quantity' => 22, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1197, 'invoice_id' => null, 'product_name' => 'دجاج 1000 جرام', 'quantity' => 21, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1198, 'invoice_id' => null, 'product_name' => 'دجاج 1100 جرام', 'quantity' => 22, 'price' => 1],
        ['store_id' => 10, 'product_id' => 369, 'invoice_id' => null, 'product_name' => 'فيليه صدور دجاج', 'quantity' => 54, 'price' => 1],
        ['store_id' => 10, 'product_id' => 575, 'invoice_id' => null, 'product_name' => 'كبدة غنم', 'quantity' => 5, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1199, 'invoice_id' => null, 'product_name' => 'بيض 30 حبة', 'quantity' => 98, 'price' => 1],
        ['store_id' => 10, 'product_id' => 948, 'invoice_id' => null, 'product_name' => 'بيض 15 حبة', 'quantity' => 40, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1327, 'invoice_id' => null, 'product_name' => 'جبنة موزريلا المراعي 450 جم', 'quantity' => 10, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1310, 'invoice_id' => null, 'product_name' => 'لبنة بوك', 'quantity' => 5, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1326, 'invoice_id' => null, 'product_name' => 'زبدة لورباك', 'quantity' => 10, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1313, 'invoice_id' => null, 'product_name' => 'بوك كريمة الخفق', 'quantity' => 5, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1311, 'invoice_id' => null, 'product_name' => 'بوك كريمة الطبخ', 'quantity' => 5, 'price' => 1],
        ['store_id' => 10, 'product_id' => 829, 'invoice_id' => null, 'product_name' => 'برايد موزريلا مبشورة 400 جم', 'quantity' => 14, 'price' => 1],
        ['store_id' => 10, 'product_id' => 830, 'invoice_id' => null, 'product_name' => 'برايد موزريلا مبشورة 900 جم', 'quantity' => 6, 'price' => 1],
        ['store_id' => 10, 'product_id' => 828, 'invoice_id' => null, 'product_name' => 'جبنة موزريلا برايد مبشورة 180 جم', 'quantity' => 3, 'price' => 1],
        ['store_id' => 10, 'product_id' => 839, 'invoice_id' => null, 'product_name' => 'عجينة سمبوسة سولو لايت', 'quantity' => 41, 'price' => 1],
        ['store_id' => 10, 'product_id' => 840, 'invoice_id' => null, 'product_name' => 'عجينة سمبوسة سولو قلي', 'quantity' => 19, 'price' => 1],
        ['store_id' => 10, 'product_id' => 841, 'invoice_id' => null, 'product_name' => 'عجينة سمبوسة سولو فرن', 'quantity' => 43, 'price' => 1],
        ['store_id' => 10, 'product_id' => 760, 'invoice_id' => null, 'product_name' => 'برتقال', 'quantity' => 6, 'price' => 1],
        ['store_id' => 10, 'product_id' => 763, 'invoice_id' => null, 'product_name' => 'رمان', 'quantity' => 3, 'price' => 1],
        ['store_id' => 10, 'product_id' => 755, 'invoice_id' => null, 'product_name' => 'خوخ', 'quantity' => 3, 'price' => 1],
        ['store_id' => 10, 'product_id' => 360, 'invoice_id' => null, 'product_name' => 'تفاح احمر', 'quantity' => 3, 'price' => 1],
        ['store_id' => 10, 'product_id' => 362, 'invoice_id' => null, 'product_name' => 'تفاح اصفر', 'quantity' => 4, 'price' => 1],
        ['store_id' => 10, 'product_id' => 463, 'invoice_id' => null, 'product_name' => 'تفاح اخضر', 'quantity' => 4, 'price' => 1],
        ['store_id' => 10, 'product_id' => 416, 'invoice_id' => null, 'product_name' => 'يوسف افندي', 'quantity' => 3, 'price' => 1],
        ['store_id' => 10, 'product_id' => 668, 'invoice_id' => null, 'product_name' => 'فلفل الوان', 'quantity' => 1, 'price' => 1],
        ['store_id' => 10, 'product_id' => 443, 'invoice_id' => null, 'product_name' => 'كيوي', 'quantity' => 2, 'price' => 1],
        ['store_id' => 10, 'product_id' => 426, 'invoice_id' => null, 'product_name' => 'ليمون اخضر', 'quantity' => 4, 'price' => 1],
        ['store_id' => 10, 'product_id' => 338, 'invoice_id' => null, 'product_name' => 'جزر محلي', 'quantity' => 2, 'price' => 1],
        ['store_id' => 10, 'product_id' => 339, 'invoice_id' => null, 'product_name' => 'جزر مستورد', 'quantity' => 4, 'price' => 1],
        ['store_id' => 10, 'product_id' => 420, 'invoice_id' => null, 'product_name' => 'ليمون اصفر', 'quantity' => 3, 'price' => 1],
        ['store_id' => 10, 'product_id' => 318, 'invoice_id' => null, 'product_name' => 'خيار', 'quantity' => 3, 'price' => 1],
        ['store_id' => 10, 'product_id' => 340, 'invoice_id' => null, 'product_name' => 'بصل ابيض', 'quantity' => 2, 'price' => 1],
        ['store_id' => 10, 'product_id' => 790, 'invoice_id' => null, 'product_name' => 'خلاص ملكي', 'quantity' => 8, 'price' => 1],
        ['store_id' => 10, 'product_id' => 789, 'invoice_id' => null, 'product_name' => 'خلاص ملكي مجروش', 'quantity' => 3, 'price' => 1],
        ['store_id' => 10, 'product_id' => 374, 'invoice_id' => null, 'product_name' => 'جلاكسي', 'quantity' => 2, 'price' => 1],
        ['store_id' => 10, 'product_id' => 408, 'invoice_id' => null, 'product_name' => 'سكري ملكي', 'quantity' => 7, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1335, 'invoice_id' => null, 'product_name' => 'بهارات كبسة', 'quantity' => 5, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1323, 'invoice_id' => null, 'product_name' => 'الخردل الاصفر', 'quantity' => 5, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1337, 'invoice_id' => null, 'product_name' => 'الدقوس وزنة', 'quantity' => 5, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1312, 'invoice_id' => null, 'product_name' => 'قشطة بوك 160 جم', 'quantity' => 20, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1390, 'invoice_id' => null, 'product_name' => 'مسحوق الكركم', 'quantity' => 10, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1338, 'invoice_id' => null, 'product_name' => 'زيت زيتون', 'quantity' => 3, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1365, 'invoice_id' => null, 'product_name' => 'رنا خل اسود', 'quantity' => 4, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1377, 'invoice_id' => null, 'product_name' => 'العلالي مربى الكرز', 'quantity' => 5, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1375, 'invoice_id' => null, 'product_name' => 'النخلة حلاوة طحينية', 'quantity' => 10, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1382, 'invoice_id' => null, 'product_name' => 'جبنة كيري', 'quantity' => 5, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1334, 'invoice_id' => null, 'product_name' => 'هيل الزهرتين', 'quantity' => 6, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1324, 'invoice_id' => null, 'product_name' => 'جبنة بوك 500 جم', 'quantity' => 5, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1329, 'invoice_id' => null, 'product_name' => 'رنا شطة خضراء حارة', 'quantity' => 5, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1366, 'invoice_id' => null, 'product_name' => 'زيتون شرائح اخضر', 'quantity' => 5, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1367, 'invoice_id' => null, 'product_name' => 'زيتون شرائح اسود', 'quantity' => 5, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1272, 'invoice_id' => null, 'product_name' => 'ملقاط فحم الجود', 'quantity' => 8, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1376, 'invoice_id' => null, 'product_name' => 'طحينة فاخرة', 'quantity' => 5, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1308, 'invoice_id' => null, 'product_name' => 'عسل الشفاء', 'quantity' => 10, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1307, 'invoice_id' => null, 'product_name' => 'شطة رنا', 'quantity' => 9, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1364, 'invoice_id' => null, 'product_name' => 'رنا خل ابيض', 'quantity' => 4, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1344, 'invoice_id' => null, 'product_name' => 'مخلل رنا', 'quantity' => 4, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1384, 'invoice_id' => null, 'product_name' => 'حليب ابو القوس 1 لتر', 'quantity' => 5, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1306, 'invoice_id' => null, 'product_name' => 'زيت عافية', 'quantity' => 5, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1336, 'invoice_id' => null, 'product_name' => 'سمن غنم', 'quantity' => 2, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1355, 'invoice_id' => null, 'product_name' => 'سمن بقري', 'quantity' => 2, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1354, 'invoice_id' => null, 'product_name' => 'جبنة بوك 130جم', 'quantity' => 8, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1396, 'invoice_id' => null, 'product_name' => 'ميرندا', 'quantity' => 10, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1318, 'invoice_id' => null, 'product_name' => 'سفن اب', 'quantity' => 10, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1317, 'invoice_id' => null, 'product_name' => 'بيبسي', 'quantity' => 10, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1383, 'invoice_id' => null, 'product_name' => 'كولا', 'quantity' => 10, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1019, 'invoice_id' => null, 'product_name' => 'فرشلي بهارات البرياني الحارة', 'quantity' => 3, 'price' => 1],
        ['store_id' => 10, 'product_id' => 1024, 'invoice_id' => null, 'product_name' => 'فرشلي بودرة الكاري', 'quantity' => 1, 'price' => 1],
        ['store_id' => 13, 'product_id' => 283, 'invoice_id' => null, 'product_name' => 'كزبرة', 'quantity' => 32, 'price' => 1],
        ['store_id' => 13, 'product_id' => 288, 'invoice_id' => null, 'product_name' => 'بقدونس', 'quantity' => 19, 'price' => 1],
        ['store_id' => 13, 'product_id' => 281, 'invoice_id' => null, 'product_name' => 'نعناع', 'quantity' => 24, 'price' => 1],
        ['store_id' => 13, 'product_id' => 284, 'invoice_id' => null, 'product_name' => 'شبت', 'quantity' => 49, 'price' => 1],
        ['store_id' => 13, 'product_id' => 280, 'invoice_id' => null, 'product_name' => 'جرجير', 'quantity' => 46, 'price' => 1],
        ['store_id' => 13, 'product_id' => 290, 'invoice_id' => null, 'product_name' => 'فجل أحمر', 'quantity' => 9, 'price' => 1],
        ['store_id' => 13, 'product_id' => 707, 'invoice_id' => null, 'product_name' => 'فلفل اخضر حار هندي', 'quantity' => 0, 'price' => 1],
        ['store_id' => 13, 'product_id' => 292, 'invoice_id' => null, 'product_name' => 'ملوخية', 'quantity' => 6, 'price' => 1],
        ['store_id' => 13, 'product_id' => 674, 'invoice_id' => null, 'product_name' => 'قرع بلدي', 'quantity' => 21, 'price' => 1],
        ['store_id' => 13, 'product_id' => 357, 'invoice_id' => null, 'product_name' => 'زهرة', 'quantity' => 12, 'price' => 1],
        ['store_id' => 13, 'product_id' => 661, 'invoice_id' => null, 'product_name' => 'طماطم', 'quantity' => 26, 'price' => 1],
        ['store_id' => 13, 'product_id' => 747, 'invoice_id' => null, 'product_name' => 'موز', 'quantity' => 25, 'price' => 1],
        ['store_id' => 13, 'product_id' => 704, 'invoice_id' => null, 'product_name' => 'باذنجان صغير', 'quantity' => 15, 'price' => 1],
        ['store_id' => 13, 'product_id' => 664, 'invoice_id' => null, 'product_name' => 'باذنجان كبير', 'quantity' => 3, 'price' => 1],
        ['store_id' => 13, 'product_id' => 681, 'invoice_id' => null, 'product_name' => 'ليمون أخضر', 'quantity' => 8, 'price' => 1],
        ['store_id' => 13, 'product_id' => 680, 'invoice_id' => null, 'product_name' => 'ليمون اصفر', 'quantity' => 15, 'price' => 1],
        ['store_id' => 13, 'product_id' => 663, 'invoice_id' => null, 'product_name' => 'كوسه', 'quantity' => 53.7, 'price' => 1],
        ['store_id' => 13, 'product_id' => 459, 'invoice_id' => null, 'product_name' => 'بخاره', 'quantity' => 4.5, 'price' => 1],
        ['store_id' => 13, 'product_id' => 763, 'invoice_id' => null, 'product_name' => 'رمان', 'quantity' => 7.8, 'price' => 1],
        ['store_id' => 13, 'product_id' => 767, 'invoice_id' => null, 'product_name' => 'كمثرى', 'quantity' => 14.2, 'price' => 1],
        ['store_id' => 13, 'product_id' => 755, 'invoice_id' => null, 'product_name' => 'خوخ', 'quantity' => 6, 'price' => 1],
        ['store_id' => 13, 'product_id' => 756, 'invoice_id' => null, 'product_name' => 'كيوي', 'quantity' => 17, 'price' => 1],
        ['store_id' => 13, 'product_id' => 757, 'invoice_id' => null, 'product_name' => 'يوسف افندي', 'quantity' => 4.9, 'price' => 1],
        ['store_id' => 13, 'product_id' => 761, 'invoice_id' => null, 'product_name' => 'برتقال عصير', 'quantity' => 6, 'price' => 1],
        ['store_id' => 13, 'product_id' => 760, 'invoice_id' => null, 'product_name' => 'برتقال', 'quantity' => 16, 'price' => 1],
        ['store_id' => 13, 'product_id' => 703, 'invoice_id' => null, 'product_name' => 'تفاح احمر', 'quantity' => 18, 'price' => 1],
        ['store_id' => 13, 'product_id' => 752, 'invoice_id' => null, 'product_name' => 'عنب اسود', 'quantity' => 4, 'price' => 1],
        ['store_id' => 13, 'product_id' => 753, 'invoice_id' => null, 'product_name' => 'عنب احمر', 'quantity' => 3, 'price' => 1],
        ['store_id' => 13, 'product_id' => 754, 'invoice_id' => null, 'product_name' => 'عنب اخضر', 'quantity' => 1, 'price' => 1],
        ['store_id' => 13, 'product_id' => 746, 'invoice_id' => null, 'product_name' => 'تفاح اصفر', 'quantity' => 13.25, 'price' => 1],
        ['store_id' => 13, 'product_id' => 745, 'invoice_id' => null, 'product_name' => 'تفاح اخضر', 'quantity' => 4.4, 'price' => 1],
        ['store_id' => 13, 'product_id' => 667, 'invoice_id' => null, 'product_name' => 'فلفل احمر حار', 'quantity' => 4.5, 'price' => 1],
        ['store_id' => 13, 'product_id' => 666, 'invoice_id' => null, 'product_name' => 'فلفل اخضر حار طويل', 'quantity' => 10.45, 'price' => 1],
        ['store_id' => 13, 'product_id' => 331, 'invoice_id' => null, 'product_name' => 'فلفل الوان', 'quantity' => 4.5, 'price' => 1],
        ['store_id' => 13, 'product_id' => 759, 'invoice_id' => null, 'product_name' => 'مانجو', 'quantity' => 9.3, 'price' => 1],
        ['store_id' => 13, 'product_id' => 672, 'invoice_id' => null, 'product_name' => 'باميه', 'quantity' => 1.05, 'price' => 1],
        ['store_id' => 13, 'product_id' => 676, 'invoice_id' => null, 'product_name' => 'بصل ابيض', 'quantity' => 26.25, 'price' => 1],
        ['store_id' => 13, 'product_id' => 678, 'invoice_id' => null, 'product_name' => 'بطاطس', 'quantity' => 18.5, 'price' => 1],
        ['store_id' => 13, 'product_id' => 297, 'invoice_id' => null, 'product_name' => 'بصل أخضر', 'quantity' => 22, 'price' => 1],
        ['store_id' => 13, 'product_id' => 677, 'invoice_id' => null, 'product_name' => 'بصل احمر', 'quantity' => 31.4, 'price' => 1],
        ['store_id' => 13, 'product_id' => 665, 'invoice_id' => null, 'product_name' => 'فلفل اخضر بارد', 'quantity' => 10.2, 'price' => 1],
        ['store_id' => 13, 'product_id' => 662, 'invoice_id' => null, 'product_name' => 'خيار', 'quantity' => 12.6, 'price' => 1],
        ['store_id' => 13, 'product_id' => 338, 'invoice_id' => null, 'product_name' => 'جزر محلي', 'quantity' => 10, 'price' => 1],
        ['store_id' => 13, 'product_id' => 339, 'invoice_id' => null, 'product_name' => 'جزر مستورد', 'quantity' => 9.3, 'price' => 1],
        ['store_id' => 13, 'product_id' => 682, 'invoice_id' => null, 'product_name' => 'زنجبيل', 'quantity' => 6.7, 'price' => 1],
        ['store_id' => 13, 'product_id' => 673, 'invoice_id' => null, 'product_name' => 'ثوم', 'quantity' => 13, 'price' => 1],
        ['store_id' => 13, 'product_id' => 338, 'invoice_id' => null, 'product_name' => 'جزر محلي', 'quantity' => 20.4, 'price' => 1],
        ['store_id' => 13, 'product_id' => 297, 'invoice_id' => null, 'product_name' => 'بصل أخضر', 'quantity' => 25, 'price' => 1],
        ['store_id' => 13, 'product_id' => 684, 'invoice_id' => null, 'product_name' => 'فطر', 'quantity' => 4, 'price' => 1],
        ['store_id' => 8, 'product_id' => 1304, 'invoice_id' => null, 'product_name' => 'عجل', 'quantity' => 0, 'price' => 1],
        ['store_id' => 8, 'product_id' => 1305, 'invoice_id' => null, 'product_name' => 'حاشي', 'quantity' => 13, 'price' => 1],
    ];

    foreach ($data as  $value) {
        Stock::create($value);
    }
    dd('done');
});
