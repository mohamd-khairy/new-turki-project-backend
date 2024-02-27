<?php

use App\Http\Controllers\API\AuthenticationController;
use App\Http\Controllers\API\BannerController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\CityController;
use App\Http\Controllers\API\CountryController;
use App\Http\Controllers\API\CouponController;
use App\Http\Controllers\API\CustomerController;
use App\Http\Controllers\API\DeliveryPeriodController;
use App\Http\Controllers\API\HomeController;
use App\Http\Controllers\API\LogController;
use App\Http\Controllers\API\NotDeliveryDatePeriodController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\OrderTest2Controller;
use App\Http\Controllers\API\PermissionController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\ProductCutController;
use App\Http\Controllers\API\ProductPreparationController;
use App\Http\Controllers\API\ProductShlwataController;
use App\Http\Controllers\API\ProductSizeController;
use App\Http\Controllers\API\ProductTagController;
use App\Http\Controllers\API\RoleController;
use App\Http\Controllers\API\Store\BankController;
use App\Http\Controllers\API\Store\InvoiceController;
use App\Http\Controllers\API\Store\MoneySafeController;
use App\Http\Controllers\API\Store\StockController;
use App\Http\Controllers\API\Store\StoreController;
use App\Http\Controllers\API\Store\SupplierController;
use App\Http\Controllers\API\SubCategoryController;
use App\Http\Controllers\API\UserController;
use App\Models\OrderState;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use App\Services\TabbyApiService;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix("v2")->group(function () {

    /************************************************** public routes ******************************************************** */
    Route::group([], function () {

        Route::prefix('customers')->middleware('auth:sanctum')->group(function () {

            Route::post('/selected-address/{address}', [\App\Http\Controllers\API\AuthenticationController::class, 'selectedAddressCustomer']);
            Route::get('/get-addresses', [\App\Http\Controllers\API\AuthenticationController::class, 'getAddress']);
            Route::post('/add-address', [\App\Http\Controllers\API\AuthenticationController::class, 'createAddressCustomer']);
            Route::post('/delete-address/{address}', [\App\Http\Controllers\API\AuthenticationController::class, 'deleteAddressCustomer']);
            Route::delete('/delete-customer', [\App\Http\Controllers\API\AuthenticationController::class, 'deleteCustomer']);
            Route::post('/edit-address/{address}', [\App\Http\Controllers\API\AuthenticationController::class, 'editAddressCustomer']);
            Route::post('/edit-profile', [\App\Http\Controllers\API\AuthenticationController::class, 'editProfile']);
            Route::get('/show-profile', [\App\Http\Controllers\API\AuthenticationController::class, 'showProfile']);
            Route::post('/charge-wallet', [\App\Http\Controllers\API\WalletController::class, 'chargeWallet']);
            Route::get('/wallet-logs', [\App\Http\Controllers\API\WalletController::class, 'customerWalletLog']);
            Route::post('/tabby-manual-payment/create', [TabbyApiService::class, 'createManualPayment']);
            Route::post('/tabby-manual-payment/update', [TabbyApiService::class, 'manualResponseUpdate']);
            Route::post('/tabby-manual-payment/updatev2', [TabbyApiService::class, 'manualResponseUpdateV2']);
        });

        /***********************AuthenticationController******************* */
        Route::post('login', [AuthenticationController::class, 'login']);
        Route::post('register', [AuthenticationController::class, 'register']);

        /*********************CategoryController***************** */
        Route::prefix('categories')->group(function () {
            Route::middleware('coordinates')->group(function () {
                Route::get('/categories-app', [CategoryController::class, 'listAppCategories']);
                Route::get('/categories-app-v2', [CategoryController::class, 'listAppCategoriesV2']);
            });
            Route::get('/{category}', [CategoryController::class, 'getById']);
            Route::get('/', [CategoryController::class, 'listCategories']);
        });

        /*********************SubCategoryController***************** */
        Route::prefix('sub-categories')->group(function () {
            Route::get('/{subCategory}', [SubCategoryController::class, 'getById']);
            Route::get('by-category-id/{category}', [SubCategoryController::class, 'listSubCategories']);
            Route::get('/', [SubCategoryController::class, 'list']);
        });

        /*********************BannerController***************** */
        Route::prefix('banners')->group(function () {
            Route::get('/by-category/{category}', [BannerController::class, 'getBannerByCategory']);
            Route::get('/', [BannerController::class, 'getBanners']);
            Route::get('/get-banners', [BannerController::class, 'getBannersDashboard']);
            Route::get('/{banner}', [BannerController::class, 'getBannerById']);
        });

        /*********************CouponController***************** */
        Route::prefix('discounts')->group(function () {
            Route::get('/', [CouponController::class, 'getAll']);
            Route::get('/categories', [CouponController::class, 'listCategories']);
            Route::get('/sub-categories', [CouponController::class, 'listSubCategories']);
            Route::get('/products', [CouponController::class, 'listProduct']);
            Route::get('/{discount}', [CouponController::class, 'getCouponById']);
        });

        /*********************ProductController***************** */
        Route::prefix('products')->group(function () {
            Route::middleware('coordinates')->group(function () {
                Route::get('/by-category/{category}', [ProductController::class, 'getProductByCategory']);
                Route::get('getProduct/{productApp}', [ProductController::class, 'getAppProductById']);
                Route::get('/best-seller', [ProductController::class, 'bestSeller']);
                Route::get('search/{name}', [ProductController::class, 'search']);
            });
            Route::get('/all', [ProductController::class, 'all']);
            Route::get('/', [ProductController::class, 'getAll']);
            Route::get('clicked/{product}', [ProductController::class, 'isClicked']);
            Route::get('/by-subcategory/{subCategory}', [ProductController::class, 'getProductBySubCategory']);
            Route::get('/{product}', [ProductController::class, 'getProductById']);
        });

        /*********************ProductCutController***************** */
        Route::prefix('product-cuts')->group(function () {
            Route::get('/', [ProductCutController::class, 'getAll']);
            Route::get('/{cut}', [ProductCutController::class, 'getById']);
        });

        /*********************ProductPreparationController***************** */
        Route::prefix('Product-preparations')->group(function () {
            Route::get('/', [ProductPreparationController::class, 'getAll']);
            Route::get('/{preparation}', [ProductPreparationController::class, 'getById']);
        });


        /*********************ProductSizeController***************** */
        Route::prefix('product-sizes')->group(function () {
            Route::get('/', [ProductSizeController::class, 'getAll']);
            Route::get('/{size}', [ProductSizeController::class, 'getById']);
            Route::get('/get-active-productSize', [ProductSizeController::class, 'getActiveProductSizes']);
        });

        /*********************ProductShlwataController***************** */
        Route::prefix('product-shlwatas')->group(function () {
            Route::get('/', [ProductShlwataController::class, 'getAll']);
            Route::get('/{shlwata}', [ProductShlwataController::class, 'getById']);
        });

        /*********************CityController***************** */
        Route::prefix('cities')->group(function () {
            Route::get('/get-active-cities', [CityController::class, 'getActiveCities']);
            Route::get('/get-city-ByCountry/{country}', [CityController::class, 'getCityByCountry']);
            Route::get('/', [CityController::class, 'getAll']);
            Route::get('/{city}', [CityController::class, 'getById']);
            Route::get('/{country}', [CityController::class, 'getCityByCountry']);
        });

        /*********************CountryController***************** */
        Route::prefix('countries')->group(function () {
            Route::get('/', [CountryController::class, 'getAll']);
            Route::get('/get-active-countries', [CountryController::class, 'getActiveCountries']);
            Route::get('/get-country-byCity/{city}', [CountryController::class, 'getCountryByCity']);
            Route::get('/{country}', [CountryController::class, 'getById']);
        });



        /*********************DeliveryPeriodController***************** */
        Route::prefix('delivery-period')->group(function () {
            /*********************DeliveryPeriodController***************** */
            Route::middleware('auth:sanctum')->group(function () {
                Route::post('/add-period', [DeliveryPeriodController::class, 'add']);
                Route::post('/update-period', [DeliveryPeriodController::class, 'update']);
                Route::post('/delete/{dpId}', [DeliveryPeriodController::class, 'delete']);
            });
            Route::get('/{dpId}', [DeliveryPeriodController::class, 'getById']);
            Route::get('/', [DeliveryPeriodController::class, 'getAll']);
        });
    });


    /************************************************** auth routes ******************************************************** */
    Route::group(['middleware' => 'auth:sanctum'], function () {
        Route::apiResource('banks',  BankController::class);
        Route::apiResource('money-safes',  MoneySafeController::class);
        Route::apiResource('stores', StoreController::class);
        Route::apiResource('suppliers', SupplierController::class);
        Route::apiResource('stocks', StockController::class);
        Route::apiResource('invoices', InvoiceController::class);
        Route::post('pay-invoice', [InvoiceController::class, 'payInvoice']);
        Route::post('transfer-stock', [StockController::class, 'transferStock']);
        Route::post('transfer-quantity', [StockController::class, 'transferQuantity']);
    });


    Route::group(['middleware' => 'auth:sanctum'], function () {

        Route::get('order-status', [HomeController::class, 'GetOrderStatus']);

        Route::get('all-order-status', fn () => successResponse(OrderState::whereIn('code', handleRoleOrderState(['admin'])['status'])->get()));

        /***********************HomeController******************* */
        Route::get('dashboard', [HomeController::class, 'dashboard']);

        /***********************RoleController******************* */
        Route::apiResource('roles', RoleController::class);

        /***********************UserController******************* */
        Route::apiResource('users', UserController::class);

        /*********************logsController***************** */
        Route::get('logs', [LogController::class, 'index']);

        /*********************ProductTagController***************** */
        Route::apiResource('tags', ProductTagController::class);

        /*********************NotDeliveryDatePeriodController***************** */
        Route::apiResource('not-delivery-date', NotDeliveryDatePeriodController::class);

        /***********************PermissionController******************* */
        Route::get('permissions', [PermissionController::class, 'permissions']);
        Route::apiResource('permission', PermissionController::class);

        /***********************CustomerController******************* */
        Route::get('customers-address/{id}', [CustomerController::class, 'getAddress']);
        Route::post('customers-address/store', [CustomerController::class, 'addAddress']);

        Route::post('customers/store', [CustomerController::class, 'store']);
        Route::get('customers', [CustomerController::class, 'index']);
        Route::get('customers/{customer}', [CustomerController::class, 'show']);
        Route::post('customers/{customer}', [CustomerController::class, 'update']);
        Route::delete('customers/{customer}', [CustomerController::class, 'delete']);


        /*********************CategoryController***************** */
        Route::prefix('categories')->group(function () {
            Route::post('/add-category', [CategoryController::class, 'create']);
            Route::post('/update-category/{categoryId}', [CategoryController::class, 'update']);
            Route::delete('/delete-category/{categoryId}', [CategoryController::class, 'delete']);
        });

        /*********************SubCategoryController***************** */
        Route::prefix('sub-categories')->group(function () {
            Route::post('/add-sub-category', [SubCategoryController::class, 'create']);
            Route::post('/update-sub-category/{subCategoryId}', [SubCategoryController::class, 'update']);
            Route::delete('/delete-sub-category/{subCategoryId}', [SubCategoryController::class, 'delete']);
        });

        /*********************BannerController***************** */
        Route::prefix('banners')->group(function () {
            Route::post('/add-banner', [BannerController::class, 'createBanner']);
            Route::post('/update-banner/{banner}', [BannerController::class, 'updateBanner']);
            Route::delete('/delete-banner/{banner}', [BannerController::class, 'deleteBanner']);
        });

        /*********************OrderController***************** */
        Route::prefix('orders')->middleware('auth:sanctum')->group(function () {
            Route::post('edit-order', [OrderController::class, 'editOrder']); //
            Route::post('create-order', [OrderController::class, 'createOrderForDashboard']); //
            Route::post('add-order-product', [OrderController::class, 'addOrderProducts']); //
            Route::post('edit-order-product', [OrderController::class, 'editOrderProducts']); //
            Route::post('delete-order-product', [OrderController::class, 'deleteOrderProducts']); //
            Route::get('get-order', [OrderController::class, 'getOrdersDashboard']); //getOrderDashboard
            Route::get('get-user-order', [OrderController::class, 'getUserOrdersDashboard']); //getOrderDashboard
            Route::get('get-one-order/{order}', [OrderController::class, 'getOrderDashboard']); //
            Route::get('get-customer-wallet/{customer_id}', [OrderController::class, 'getCustomerWallet']); //getCustomerWallet
            Route::get('take-order/{id}', [OrderController::class, 'takeOrder']); //getCustomerWallet
            Route::post('assign-user-order', [OrderController::class, 'assignUserOrder']); //getCustomerWallet


            Route::get('get-orders-v2', [OrderController::class, 'getOrdersV2']);
            Route::get('/{order}', [OrderController::class, 'getOrderByRefNo']);
            Route::get('/', [OrderController::class, 'getOrders']);
            Route::get('remove-discount/{id}', [OrderController::class, 'removeDiscount']);

            Route::middleware('coordinates')->group(function () {
                Route::post('add-order', [OrderController::class, 'createOrder']);
                Route::post('add-test-order', [OrderTest2Controller::class, 'createOrder']);
            });
        });

        /*********************CouponController***************** */
        Route::prefix('discounts')->group(function () {
            Route::post('/add-discount', [CouponController::class, 'createCoupon']);
            Route::post('/update-discount/{discount}', [CouponController::class, 'updateCoupon']);
            Route::delete('/delete-discount/{discount}', [CouponController::class, 'delete']);
        });

        /*********************ProductCutController***************** */
        Route::prefix('product-cuts')->group(function () {
            Route::post('/add-cut', [ProductCutController::class, 'add']);
            Route::post('/update-cut/{productCut}', [ProductCutController::class, 'update']);
            Route::post('/update-status/{productCut}', [ProductCutController::class, 'updateStatus']);
            Route::post('/delete-productCut/{productCut}', [ProductCutController::class, 'delete']);
        });

        /*********************ProductPreparationController***************** */
        Route::prefix('Product-preparations')->group(function () {
            Route::post('/add-preparations', [ProductPreparationController::class, 'add']);
            Route::post('/update-status/{productPreparation}', [ProductPreparationController::class, 'add']);
            Route::post('/update-preparations/{productPreparation}', [ProductPreparationController::class, 'update']);
            Route::post('/delete-preparations/{productPreparation}', [ProductPreparationController::class, 'delete']);
        });


        /*********************ProductSizeController***************** */
        Route::prefix('product-sizes')->group(function () {
            Route::post('/add-size', [ProductSizeController::class, 'add']);
            Route::post('/update-size/{productSize}', [ProductSizeController::class, 'update']);
            Route::post('/delete-size/{productSize}', [ProductSizeController::class, 'delete']);
        });

        /*********************ProductShlwataController***************** */
        Route::prefix('product-shlwatas')->group(function () {
            Route::post('/add-product-shlwatas', [ProductShlwataController::class, 'add']);
            Route::post('/update-product-shlwatas/{productShlwata}', [ProductShlwataController::class, 'update']);
            Route::post('/delete-product-shlwatas/{productShlwata}', [ProductShlwataController::class, 'delete']);
        });

        /*********************CityController***************** */
        Route::prefix('cities')->group(function () {
            Route::post('/add-cities', [CityController::class, 'add']);
            Route::post('/update-cities/{city}', [CityController::class, 'update']);
            Route::post('/update-status/{city}', [CityController::class, 'updateStatus']);
            Route::post('/delete-city/{city}', [CityController::class, 'delete']);
        });

        /*********************CountryController***************** */
        Route::prefix('countries')->group(function () {
            Route::post('/add-countries', [CountryController::class, 'create']);
            Route::post('/update-status/{country}', [CountryController::class, 'updateStatus']);
            Route::post('/update-country/{country}', [CountryController::class, 'update']);
            Route::post('/delete-country/{country}', [CountryController::class, 'delete']);
        });

        /*********************ProductController***************** */
        Route::prefix('products')->group(function () {
            Route::post('/add-products', [ProductController::class, 'create']);
            Route::post('/update-products/{product}', [ProductController::class, 'update']);
            Route::post('/add-product-images/{productId}', [ProductController::class, 'uploadProductImages']);
            Route::delete('/delete-product-images/{productImage}', [ProductController::class, 'deleteImage']);
            Route::delete('/delete-product/{productId}', [ProductController::class, 'delete']);
            Route::post('/{product}/rating', [ProductController::class, 'ratingProduct']);
        });
    });

    /************************************************** old routes ******************************************************** */
    Route::namespace("API")->group(function () {

        // Auth
        Route::post('/sendOtpCode', [\App\Http\Controllers\API\AuthenticationController::class, 'sendOTP']);
        Route::post('/verfiyOtpCode', [\App\Http\Controllers\API\AuthenticationController::class, 'verfiyOTP']);
        Route::post('/verfiyOtpCode-v2', [\App\Http\Controllers\API\AuthenticationController::class, 'verfiyOTPv2']);
        Route::get('testlogin/{mobile}', [\App\Http\Controllers\API\AuthenticationController::class, 'testLogin']);


        Route::get('/driver/send-driver-otp/{orderId}', [\App\Http\Controllers\API\AuthenticationController::class, 'sendDriverOTP']);
        Route::get('/driver/verify-driver-otp/{orderId}/{otp}', [\App\Http\Controllers\API\AuthenticationController::class, 'verifyDriverOTP']);


        Route::get('/cleareverything', function () {

            $clearcache = Artisan::call('optimize:clear');
            echo "Cache cleared<br>" . $clearcache;
        });

        // Route::get('/amjad-test',[\App\Services\CallOrderNetsuiteApi::class, 'sendOrderToNSV2']);


        Route::post('/tabby-manual-payment/testHash', [TabbyApiService::class, 'testHash']);
        // Public
        Route::get('/final_result', [\App\Services\AlRajhiPaymentService::class, 'show_final_result']);
        Route::post('/final_result', [\App\Services\AlRajhiPaymentService::class, 'Get_Payment_Status_ARB']);


        Route::get('invoicestatus', [\App\Services\MyFatoorahApiService::class, 'Get_Payment_Status']);
        Route::get('invoicestatus_wallet', [\App\Services\MyFatoorahApiService::class, 'GetPaymentStatusWallet']);
        Route::get('invoicestatus/ksa', [\App\Services\MyFatoorahApiService::class, 'Get_Payment_Status_ksa']);
        Route::post('/order-pos-test', [\App\Services\FoodicsApiService::class, 'sendOrderFoodicsToNS']);
        Route::post('/web-hook/foodics/order_create', [\App\Services\FoodicsApiService::class, 'webhookFoodics']);

        Route::post('/webhook/ngenius/payment', [\App\Services\NgeniusPaymentService::class, 'webhookNgenius']);

        Route::post('/my-fatorah-test', [\App\Services\MyFatoorahApiService::class, 'Set_Payment_myfatoora']);

        Route::get('tabby/checkout/response', [\App\Services\TabbyApiService::class, 'response']);

        Route::get('tabby/checkout/success', [\App\Services\TabbyApiService::class, 'response']);
        Route::get('tabby/checkout/cancel', [\App\Services\TabbyApiService::class, 'response']);
        Route::get('tabby/checkout/failure', [\App\Services\TabbyApiService::class, 'response']);

        Route::get('checkout/response ', [\App\Services\TamaraApiService::class, 'response']);
        Route::get('checkout/success', [\App\Services\TamaraApiService::class, 'response']);
        Route::get('checkout/failure', [\App\Services\TamaraApiService::class, 'response']);
        Route::get('checkout/cancel', [\App\Services\TamaraApiService::class, 'response']);

        Route::post('payments/tamarapay ', [\App\Services\TamaraApiService::class, 'tamarapay']);

        Route::get('order-details/{order} ', [\App\Services\TamaraApiService::class, 'orderDetails']);


        Route::post('/checkout-Tamara', [\App\Services\TamaraApiService::class, 'checkoutTamara']);

        Route::prefix('auth')->group(function () {
            Route::middleware('auth:sanctum')->post('/new-user', [\App\Http\Controllers\API\AuthenticationController::class, 'createUser']);
            Route::post('/login', [\App\Http\Controllers\API\AuthenticationController::class, 'login']);
        });

        Route::middleware('auth:sanctum')->get('ordersTest/{order}', [\App\Http\Controllers\API\OrderController::class, 'getOrderByRefNoTest']);



        //Promotion
        Route::prefix('promotions')->group(function () {
            Route::get('/', [\App\Http\Controllers\API\PromotionController::class, 'getPromotions']);
            Route::get('/{promotionId}', [\App\Http\Controllers\API\PromotionController::class, 'getPromotionById']);
        });

        // Setting App
        Route::prefix('setting-app')->group(function () {
            Route::get('/version', [\App\Http\Controllers\API\SettingAppController::class, 'getVersion']);
            Route::get('/version/{version}', [\App\Http\Controllers\API\SettingAppController::class, 'getVersionById']);
        });
        //-----------------------------------------------------------------------------------------------------------------------------------
        // Filters
        Route::prefix('filters')->group(function () {

            Route::get('/', [\App\Http\Controllers\API\DiscoverController::class, 'list']);
            Route::get('/{discover}', [\App\Http\Controllers\API\DiscoverController::class, 'getById']);
            Route::get('/by-category/{category}', [\App\Http\Controllers\API\DiscoverController::class, 'listDiscover']);
            Route::post('/add-filter', [\App\Http\Controllers\API\DiscoverController::class, 'create']);
            Route::post('/update-filter/{discover}', [\App\Http\Controllers\API\DiscoverController::class, 'update']);
            Route::delete('/delete-filter/{discover}', [\App\Http\Controllers\API\DiscoverController::class, 'delete']);
        });

        //-----------------------------------------------------------------------------------------------------------------------------------
        // Min Orders
        Route::prefix('min-orders')->group(function () {

            Route::get('/', [\App\Http\Controllers\API\MinOrderController::class, 'getAll']);
            Route::get('/{minOrder}', [\App\Http\Controllers\API\MinOrderController::class, 'getById']);
            Route::post('/add-min-orders', [\App\Http\Controllers\API\MinOrderController::class, 'add']);
            Route::post('/update-min-orders/{minOrder}', [\App\Http\Controllers\API\MinOrderController::class, 'update']);
            Route::delete('/delete-min-orders/{minOrder}', [\App\Http\Controllers\API\MinOrderController::class, 'delete']);
        });
        //-----------------------------------------------------------------------------------------------------------------------------------


        // GiftCard

        Route::prefix('giftCards')->group(function () {
            Route::get('/', [\App\Http\Controllers\API\GiftCardController::class, 'getAll']);
            Route::get('/{GiftCard}', [\App\Http\Controllers\API\GiftCardController::class, 'getById']);
            Route::post('/add-giftCard', [\App\Http\Controllers\API\GiftCardController::class, 'createGiftCard']);
            Route::post('/update-giftCard/{giftcard}', [\App\Http\Controllers\API\GiftCardController::class, 'updateGiftCard']);
            Route::delete('/delete-giftCard/{giftcard}', [\App\Http\Controllers\API\GiftCardController::class, 'delete']);
            Route::middleware('auth:sanctum')->group(function () {
                Route::post('/redeem-giftCard', [\App\Http\Controllers\API\GiftCardController::class, 'redeemGiftCard']);
            });
        });







        //-----------------------------------------------------------------------------------------------------------------------------------

        //Payment Type
        Route::prefix('payment-types')->group(function () {
            Route::get('/', [\App\Http\Controllers\API\PaymentTypeController::class, 'getAll']);
            Route::get('/get-payment-types-Tamara', [\App\Http\Controllers\API\PaymentTypeController::class, 'getPaymentTypesTamara']);
            Route::get('/{paymentType}', [\App\Http\Controllers\API\PaymentTypeController::class, 'getById']);
            Route::post('/add-payment-type', [\App\Http\Controllers\API\PaymentTypeController::class, 'add']);
            Route::post('/update-payment-type/{paymentType}', [\App\Http\Controllers\API\PaymentTypeController::class, 'update']);
            Route::post('/delete-payment-type/{paymentType}', [\App\Http\Controllers\API\PaymentTypeController::class, 'delete']);
        });

        //-----------------------------------------------------------------------------------------------------------------------------------

        Route::prefix('delivery-date')->group(function () {
            Route::post('/add-not-included-date-bulk', [\App\Http\Controllers\API\DeliveryDatePeriodController::class, 'addCityDateBulk']);
            Route::post('/update/{DateCity}', [\App\Http\Controllers\API\DeliveryDatePeriodController::class, 'update']);
            Route::post('/deleteDDP/{dpId}', [\App\Http\Controllers\API\DeliveryDatePeriodController::class, 'delete']);
            Route::get('/{dpId}', [\App\Http\Controllers\API\DeliveryDatePeriodController::class, 'getById']);
            Route::get('/', [\App\Http\Controllers\API\DeliveryDatePeriodController::class, 'getDeliveryDatePeriod']);
        });


        //Products - ex. api/v2/products/add-product

        Route::middleware('auth:sanctum')->prefix('wishlists')->group(function () {
            Route::get('/', [\App\Http\Controllers\API\ProductController::class, 'getFavoriteProduct']);
            Route::get('/add-to-wishlist/{product}', [\App\Http\Controllers\API\ProductController::class, 'addFavoriteProduct']);
            Route::delete('/remove-from-wishlist/{favorite}', [\App\Http\Controllers\API\ProductController::class, 'removeFavoriteProduct']);
        });





        Route::prefix('carts')->middleware('auth:sanctum')->group(function () {
            Route::get('/', [\App\Http\Controllers\API\CartController::class, 'getCart']);
            Route::post('add-to-cart', [\App\Http\Controllers\API\CartController::class, 'addToCart']);
            Route::post('add-to-cart-v2', [\App\Http\Controllers\API\CartController::class, 'addToCartV2']);
            Route::post('update-cart/{cartId}', [\App\Http\Controllers\API\CartController::class, 'updateCart']);
            Route::delete('delete-cart/{cartId}', [\App\Http\Controllers\API\CartController::class, 'deleteCart']);
            Route::middleware('coordinates')->post('check-coupon', [\App\Http\Controllers\API\CouponController::class, 'checkValidation']);
            Route::post('get-invoice-preview', [\App\Http\Controllers\API\CartController::class, 'getInvoicePreview']);
        });
        Route::get('/e922c951-94d8-4d59-b2cf-c9ed55a9e848', function (Request $request) {
            Artisan::call("test:funny");
        });


        //user
        //customer

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/customers-list', [\App\Http\Controllers\API\CouponController::class, 'listCustomer']);
            Route::post('/wallet', [\App\Http\Controllers\API\WalletController::class, 'updateCustomerWallet']);
            Route::get('/get-wallet', [\App\Http\Controllers\API\WalletController::class, 'getWalletLog']);
            Route::get('/wallet-by-id/{wallet}', [\App\Http\Controllers\API\WalletController::class, 'getWalletLogById']);

            Route::get('/wallet-by-customer/{id}', [\App\Http\Controllers\API\WalletController::class, 'getWalletLogByCustomerId']);
        });





        Route::namespace("API")->prefix('test-location')->middleware('coordinates')->group(function () {

            Route::get('/test', [\App\Http\Controllers\API\ProductController::class, 'getProductBySubCategoryWithLocationTest']);
        });
    });
});
