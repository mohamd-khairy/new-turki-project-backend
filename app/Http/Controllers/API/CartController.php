<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Country;
use App\Models\Customer;
use App\Models\DeliveryFee;
use App\Models\MinOrder;
use App\Models\NotDeliveryDateCity;
use App\Models\Product;
use App\Models\Shalwata;
use App\Models\TraceError;
use App\Services\PointLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct()
    {
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function getCart(Request $request)
    {

        $point = $request->query('longitude') . " " . $request->query('latitude');
        $countryId = $request->query('countryId');
        $country = Country::where('code', $countryId)->get()->first();

        if ($country === null) {
            return response()->json([
                'data' => [],
                'success' => true, 'message' => 'success', 'description' => 'this service not available in your country!', 'code' => '200',
            ], 200);
        }

        $currentCity = null;
        $minOrderPerCity = null;
        try {
            $currentCity = app(PointLocation::class)->getLocatedCity($country, $point);
            $minOrder = MinOrder::where('country_id', $country->id)->get();
            $minOrderPerCity = MinOrder::where('city_id', $currentCity->id)->first();
        } catch (\Exception $e) {
            return response()->json([
                'data' => [],
                'success' => true, 'message' => 'success', 'description' => 'this service not available in your city, contact support!', 'code' => '200',
            ], 200);
        }

        // $is_customer_block = Customer::where('id', auth()->user()->id)->pluck('is_code_block');

        // if ($is_customer_block[0] == 1) {
        //     $paymentType = PaymentType::whereNotIn('id', [1, 4])->get();
        // } else {
        //     $productIds = ProductCity::where('city_id', $currentCity->id)->distinct()->pluck('product_id');

        //     // PaymentType
        //     $PaymentTypeIds = PaymentTypeCity::where('city_id', $currentCity->id)->distinct()->pluck('payment_type_id');

        //     $ProductPaymentTypeCity = ProductPaymentType::where('product_id', $productIds)->distinct()->pluck('payment_type_id');

        //     $paymentType = PaymentType::whereIn('id', $PaymentTypeIds)->whereIn('id', $ProductPaymentTypeCity)->get();
        // }

        if ($currentCity === null) {
            return response()->json([
                'data' => [],
                'success' => true, 'message' => 'success', 'description' => 'this service not available in your city!', 'code' => '200',
            ], 200);
        }

        $cart = Cart::where([['city_id', $currentCity->id], ['customer_id', auth()->user()->id]]);
        $cartPaginated = $cart->cartDetails()->paginate(PerPage($request));

        $cartWithDetails = $cart->with(['product.notDeliveryDate.periods' => function ($query) use ($currentCity) {
            $query->where('city_id', $currentCity->id);
        }])->cartDetails()->paginate(PerPage($request));

        $first = $cart->get()->first();
        $validated = [
            "using_wallet" => 0,
            'applied_discount_code' => $first != null ? $first->applied_discount_code : null,
        ];

        $preview = $this->invoicePreview($validated, $cart->get(), $currentCity, $country);

        $list = NotDeliveryDateCity::where('city_id', $currentCity->id)->get(['id', 'delivery_date']);

        return response()->json([
            'success' => true, 'data' => [
                'cart' => $cartWithDetails,
                'min_order' => $minOrder,
                'min_order_per_city' => $minOrderPerCity != null ? $minOrderPerCity->min_order : 0.0,
                //'is_customer_block' => $is_customer_block,
                // 'paymentType' => $paymentType,
                'not_included_dates' => $list,
                'invoice-preview' => $preview,
            ],
            'message' => 'Products retrieved successfully', 'description' => 'list Of Products', 'code' => '200',
        ], 200);
    }

    public function addToCart(Request $request)
    {
        TraceError::create(['class_name' => "CartController:: coming from the app", 'method_name' => "addToCart", 'error_desc' => json_encode($request->all())]);

        $point = $request->query('longitude') . " " . $request->query('latitude');
        $countryId = $request->query('countryId');
        $country = Country::where('code', $countryId)->get()->first();

        if ($country === null) {
            return response()->json([
                'data' => [],
                'success' => true, 'message' => 'success', 'description' => 'this service not available in your country!', 'code' => '200',
            ], 200);
        }

        $currentCity = app(PointLocation::class)->getLocatedCity($country, $point);

        if ($currentCity === null) {
            return response()->json([
                'data' => [],
                'success' => true, 'message' => 'success', 'description' => 'this service not available in your city!', 'code' => '200',
            ], 200);
        }

        Cart::where([['city_id', '<>', $currentCity->id], ['customer_id', auth()->user()->id]])->delete();

        $validated = $request->validate([
            "comment" => 'nullable',
            "product_id" => 'required|exists:products,id',
            "quantity" => 'required|numeric',
            "preparation_id" => array('nullable', 'exists:preparations,id'),
            'size_id' => 'nullable|exists:sizes,id',
            'cut_id' => 'nullable|exists:cuts,id',
            'is_shalwata' => 'required|boolean',
            // 'is_kwar3'  => 'nullable|boolean',
            // 'is_Ras'  => 'nullable|boolean',
            // 'is_lyh'  => 'nullable|boolean',
            // 'is_karashah'  => 'nullable|boolean',
        ]);

        $validated['city_id'] = $currentCity->id;
        $product = Product::find($validated['product_id']);

        if ($validated['quantity'] == 0) {
            return response()->json([
                'success' => false, 'data' => [],
                'message' => 'quantity can not be zero!', 'description' => '1', 'code' => '400',
            ], 400);
        }

        if ($product->is_active == 0) {
            return response()->json([
                'success' => false, 'data' => [],
                'message' => 'product not available!', 'description' => '', 'code' => '400',
            ], 400);
        }

        if ($validated['preparation_id'] != null && $product->productPreparations()->find($validated['preparation_id']) == null) {
            return response()->json([
                'success' => false, 'data' => [],
                'message' => 'invalid preparation used!', 'description' => '', 'code' => '400',
            ], 400);
        }
        if ($validated['size_id'] != null && $product->productSizes()->find($validated['size_id']) == null) {
            return response()->json([
                'success' => false, 'data' => [],
                'message' => 'invalid size used!', 'description' => '', 'code' => '400',
            ], 400);
        }
        if ($validated['cut_id'] != null && $product->productCuts()->find($validated['cut_id']) == null) {
            return response()->json([
                'success' => false, 'data' => [],
                'message' => 'invalid cut used!', 'description' => '', 'code' => '400',
            ], 400);
        }
        if ($validated['is_shalwata'] == 1 && $product->is_shalwata != 1) {

            return response()->json([
                'success' => false, 'data' => [],
                'message' => 'product does not have shalwata!', 'description' => '', 'code' => '400',
            ], 400);
        }
        // if ($validated['is_kwar3'] == 1 && $product->is_kwar3 != 1 ){

        //     return response()->json(['success' => false,'data'=> [],
        //     'message'=> 'product does not have kawar3!', 'description'=> '', 'code'=>'400'],400);
        // }

        // if ($validated['is_Ras'] == 1 && $product->is_Ras != 1 ){

        //     return response()->json(['success' => false,'data'=> [],
        //     'message'=> 'product does not have Ras!', 'description'=> '', 'code'=>'400'],400);
        // }

        // if ($validated['is_lyh'] == 1 && $product->is_lyh != 1 ){

        //     return response()->json(['success' => false,'data'=> [],
        //     'message'=> 'product does not have lyh!', 'description'=> '', 'code'=>'400'],400);
        // }

        // if ($validated['is_karashah'] == 1 && $product->is_karashah != 1 ){

        //     return response()->json(['success' => false,'data'=> [],
        //     'message'=> 'product does not have karashah!', 'description'=> '', 'code'=>'400'],400);
        // }

        $validated['shalwata_id'] = null;
        $validated['customer_id'] = auth()->user()->id;

        $cart = Cart::where([
            ['city_id', $currentCity->id],
            ['customer_id', auth()->user()->id],
            ['product_id', $validated['product_id']],
            ['preparation_id', $validated['preparation_id']],
            ['size_id', $validated['size_id']],
            ['cut_id', $validated['cut_id']],
            ['is_shalwata', $validated['is_shalwata']],
            // ['is_kwar3', $validated['is_kwar3']],
            // ['is_Ras', $validated['is_Ras']],
            // ['is_lyh', $validated['is_lyh']],
            // ['is_karashah', $validated['is_karashah']],
        ])->get()->first();

        if ($cart == null) {
            if ($validated['is_shalwata'] == 1) {
                $validated['shalwata_id'] = Shalwata::first()->id;
            }

            $cart = Cart::create($validated);
        } else {
            $validated['quantity'] = $cart->quantity + $validated['quantity'];
            $cart->update($validated);
        }

        DB::statement("update carts set comment = '" . $validated['comment'] . "' where customer_id = " . $cart->customer_id);

        $validated["using_wallet"] = 0;
        $validated["applied_discount_code"] = $cart->applied_discount_code;
        $allCart = Cart::where([['customer_id', auth()->user()->id], ['city_id', $currentCity->id]])->cartDetails()->get();
        $preview = $this->invoicePreview($validated, $allCart, $currentCity, $country);

        return response()->json([
            'success' => true, 'data' => ['cart' => $cart, 'invoice-preview' => $preview],
            'message' => '', 'description' => '', 'code' => '200',
        ], 200);
    }

    public function invoicePreview($validated, $cartProducts, $currentCity, $country)
    {
        $shalwata = Shalwata::first();
        $itemsAmount = 0.0;
        $addonsAmount = 0.0;
        $TotalAmountBeforeDiscount = 0.0;
        $TotalAmountAfterDiscount = 0.0;
        $orderProducts = [];
        $discountCode = null;
        $discountAmount = 0;
        $comment = null;

        list($cartProduct, $discountCode, $totalAddonsAmount, $totalItemsAmount, $orderProducts)
        = app(OrderController::class)->calculateProductsAmount($cartProducts, $validated['applied_discount_code'], $shalwata, $addonsAmount, $itemsAmount, $orderProducts);

        $TotalAmountBeforeDiscount = $totalItemsAmount + $totalAddonsAmount;

        if ($discountCode != null) {
            list($couponValid, $discountAmount, $TotalAmountAfterDiscount, $couponValidatingResponse, $applicableProductIds) = app(CouponController::class)->discountProcess($discountCode, $cartProducts, $TotalAmountBeforeDiscount, $discountAmount, $TotalAmountAfterDiscount, $country->id, $currentCity->id);
            if ($couponValid == null) {
                return response()->json([
                    'success' => false, 'data' => Cart::where('customer_id', auth()->user()->id)->get(),
                    'message' => $couponValidatingResponse[0] . ":" . $couponValidatingResponse[1], 'description' => 'invalid coupon used', 'code' => '400',
                ], 400);
            }
        } else {
            $TotalAmountAfterDiscount = $TotalAmountBeforeDiscount;
        }

        $customer = Customer::find(auth()->user()->id);
        $delivery = DeliveryFee::where('city_id', $currentCity->id)->get()->first();
        $walletAmountUsed = 0;
        $wallet = $customer->wallet;

        if ($validated["using_wallet"] == 1) {
            if ($TotalAmountAfterDiscount > $wallet) {
                $TotalAmountAfterDiscount = $TotalAmountAfterDiscount - $wallet;
                $walletAmountUsed = $wallet;
                $customer->wallet = 0;
                $customer->save();
            } else {
                $walletAmountUsed = $TotalAmountAfterDiscount;
                $TotalAmountAfterDiscount = $wallet - $TotalAmountAfterDiscount;
                $customer->wallet = $TotalAmountAfterDiscount;
                $customer->save();
            }
        }

        $preview = [
            'delivery_fee' => (float) $delivery,
            'order_subtotal' => (float) $TotalAmountBeforeDiscount,
            'total_amount' => (float) ($TotalAmountBeforeDiscount + $delivery),
            'total_amount_after_discount' => (float) $TotalAmountAfterDiscount + $delivery,
            'discount_applied' => (float) $discountAmount,
            'applied_discount_code' => $validated['applied_discount_code'],
            "using_wallet" => $validated["using_wallet"],
            'wallet_amount_used' => (float) $walletAmountUsed,

        ];

        return $preview;
    }

    public function addToCartV2(Request $request)
    {
        //  TraceError::create(['class_name'=> "CartController:: coming from the app", 'method_name'=>"addToCart", 'error_desc' => json_encode($request->all())]);

        $point = $request->query('longitude') . " " . $request->query('latitude');
        $countryId = $request->query('countryId');
        $country = Country::where('code', $countryId)->get()->first();

        if ($country === null) {
            return response()->json([
                'data' => [],
                'success' => true, 'message' => 'success', 'description' => 'this service not available in your country!', 'code' => '200',
            ], 200);
        }

        $currentCity = app(PointLocation::class)->getLocatedCity($country, $point);

        if ($currentCity === null) {
            return response()->json([
                'data' => [],
                'success' => true, 'message' => 'success', 'description' => 'this service not available in your city!', 'code' => '200',
            ], 200);
        }

        Cart::where([['city_id', '<>', $currentCity->id], ['customer_id', auth()->user()->id]])->delete();

        $validated = $request->validate([
            "comment" => 'nullable',
            "product_id" => 'required|exists:products,id',
            "quantity" => 'required|numeric',
            "preparation_id" => array('nullable', 'exists:preparations,id'),
            'size_id' => 'nullable|exists:sizes,id',
            'cut_id' => 'nullable|exists:cuts,id',
            'is_shalwata' => 'required|boolean',
            'is_kwar3' => 'nullable|boolean',
            'is_Ras' => 'nullable|boolean',
            'is_lyh' => 'nullable|boolean',
            'is_karashah' => 'nullable|boolean',
        ]);

        $validated['city_id'] = $currentCity->id;
        $product = Product::find($validated['product_id']);

        if ($validated['quantity'] == 0) {
            return response()->json([
                'success' => false, 'data' => [],
                'message' => 'quantity can not be zero!', 'description' => '1', 'code' => '400',
            ], 400);
        }

        if ($product->is_available == 0) {
            return response()->json([
                'success' => false, 'data' => [],
                'message' => 'product not available!', 'description' => '', 'code' => '400',
            ], 400);
        }

        if ($validated['preparation_id'] != null && $product->productPreparations()->find($validated['preparation_id']) == null) {
            return response()->json([
                'success' => false, 'data' => [],
                'message' => 'invalid preparation used!', 'description' => '', 'code' => '400',
            ], 400);
        }
        if ($validated['size_id'] != null && $product->productSizes()->find($validated['size_id']) == null) {
            return response()->json([
                'success' => false, 'data' => [],
                'message' => 'invalid size used!', 'description' => '', 'code' => '400',
            ], 400);
        }
        if ($validated['cut_id'] != null && $product->productCuts()->find($validated['cut_id']) == null) {
            return response()->json([
                'success' => false, 'data' => [],
                'message' => 'invalid cut used!', 'description' => '', 'code' => '400',
            ], 400);
        }
        if ($validated['is_shalwata'] == 1 && $product->is_shalwata != 1) {

            return response()->json([
                'success' => false, 'data' => [],
                'message' => 'product does not have shalwata!', 'description' => '', 'code' => '400',
            ], 400);
        }

        if ($validated['is_kwar3'] == 1 && $product->is_kwar3 != 1) {

            return response()->json([
                'success' => false, 'data' => [],
                'message' => 'product does not have kawar3!', 'description' => '', 'code' => '400',
            ], 400);
        }

        if ($validated['is_Ras'] == 1 && $product->is_Ras != 1) {

            return response()->json([
                'success' => false, 'data' => [],
                'message' => 'product does not have Ras!', 'description' => '', 'code' => '400',
            ], 400);
        }

        if ($validated['is_lyh'] == 1 && $product->is_lyh != 1) {

            return response()->json([
                'success' => false, 'data' => [],
                'message' => 'product does not have lyh!', 'description' => '', 'code' => '400',
            ], 400);
        }

        if ($validated['is_karashah'] == 1 && $product->is_karashah != 1) {

            return response()->json([
                'success' => false, 'data' => [],
                'message' => 'product does not have karashah!', 'description' => '', 'code' => '400',
            ], 400);
        }

        $validated['shalwata_id'] = null;
        $validated['customer_id'] = auth()->user()->id;

        $cart = Cart::where([
            ['city_id', $currentCity->id],
            ['customer_id', auth()->user()->id],
            ['product_id', $validated['product_id']],
            ['preparation_id', $validated['preparation_id']],
            ['size_id', $validated['size_id']],
            ['cut_id', $validated['cut_id']],
            ['is_shalwata', $validated['is_shalwata']],
            ['is_kwar3', $validated['is_kwar3']],
            ['is_Ras', $validated['is_Ras']],
            ['is_lyh', $validated['is_lyh']],
            ['is_karashah', $validated['is_karashah']],
        ])->get()->first();

        if ($cart == null) {
            if ($validated['is_shalwata'] == 1) {
                $validated['shalwata_id'] = Shalwata::first()->id;
            }

            $cart = Cart::create($validated);
        } else {
            $validated['quantity'] = $cart->quantity + $validated['quantity'];
            $cart->update($validated);
        }

        DB::statement("update carts set comment = '" . $validated['comment'] . "' where customer_id = " . $cart->customer_id);

        $validated["using_wallet"] = 0;
        $validated["applied_discount_code"] = $cart->applied_discount_code;
        $allCart = Cart::where([['customer_id', auth()->user()->id], ['city_id', $currentCity->id]])->cartDetails()->get();
        $preview = $this->invoicePreview($validated, $allCart, $currentCity, $country);

        return response()->json([
            'success' => true, 'data' => ['cart' => $cart, 'invoice-preview' => $preview],
            'message' => '', 'description' => '', 'code' => '200',
        ], 200);
    }

    public function updateCart($cartId, Request $request)
    {
        $point = $request->query('longitude') . " " . $request->query('latitude');
        $countryId = $request->query('countryId');
        $country = Country::where('code', $countryId)->get()->first();

        if ($country === null) {
            return response()->json([
                'data' => [],
                'success' => true, 'message' => 'success', 'description' => 'this service not available in your country!', 'code' => '200',
            ], 200);
        }

        $currentCity = null;
        try {
            $currentCity = app(PointLocation::class)->getLocatedCity($country, $point);
        } catch (\Exception $e) {
            return response()->json([
                'data' => [],
                'success' => true, 'message' => 'success', 'description' => 'this service not available in your city, contact support!', 'code' => '200',
            ], 200);
        }

        if ($currentCity === null) {
            return response()->json([
                'data' => [],
                'success' => true, 'message' => 'success', 'description' => 'this service not available in your city!', 'code' => '200',
            ], 200);
        }

        $cart = Cart::where([['city_id', $currentCity->id], ['customer_id', auth()->user()->id]]);

        $cartPaginated = $cart->cartDetails()->paginate(PerPage($request));

        if ($cartId == null && !is_numeric($cartId)) {
            return response()->json([
                'success' => false, 'data' => [],
                'message' => 'no items found!', 'description' => '1', 'code' => '400',
            ], 400);
        }

        $validated = $request->validate([
            "comment" => 'nullable',
            "quantity" => 'required|numeric',
        ]);

        if ($validated['quantity'] == 0) {
            return response()->json([
                'success' => false, 'data' => [],
                'message' => 'quantity can not be zero, use delete insted!', 'description' => '1', 'code' => '400',
            ], 400);
        }

        $cart = Cart::where([['customer_id', auth()->user()->id], ['id', $cartId]])->get()->first();

        if ($cart == null) {
            return response()->json([
                'success' => false, 'data' => [],
                'message' => 'no items found!', 'description' => '1', 'code' => '400',
            ], 400);
        }

        $cart->update($validated);

        DB::statement("update carts set comment = '" . $validated['comment'] . "' where customer_id = " . $cart->customer_id);

        $validated["using_wallet"] = 0;
        $validated["applied_discount_code"] = $cart->applied_discount_code;
        $allCart = Cart::where('customer_id', auth()->user()->id)->cartDetails()->get();

        $preview = $this->invoicePreview($validated, $allCart, $currentCity, $country);

        return response()->json([
            'success' => true, 'data' => ['cart' => $cart, 'invoice-preview' => $preview],
            'message' => '', 'description' => '', 'code' => '200',
        ], 200);
    }

    public function deleteCart($cartId, Request $request)
    {
        $point = $request->query('longitude') . " " . $request->query('latitude');
        $countryId = $request->query('countryId');
        $country = Country::where('code', $countryId)->get()->first();

        $currentCity = null;
        if ($country === null) {
            return response()->json([
                'data' => [],
                'success' => true, 'message' => 'success', 'description' => 'this service not available in your country!', 'code' => '200',
            ], 200);
        }

        try {
            $currentCity = app(PointLocation::class)->getLocatedCity($country, $point);
        } catch (\Exception $e) {
            return response()->json([
                'data' => [],
                'success' => true, 'message' => 'success', 'description' => 'this service not available in your city, contact support!', 'code' => '200',
            ], 200);
        }

        if ($currentCity === null) {
            return response()->json([
                'data' => [],
                'success' => true, 'message' => 'success', 'description' => 'this service not available in your city!', 'code' => '200',
            ], 200);
        }

        if ($cartId == null && !is_numeric($cartId)) {
            return response()->json([
                'success' => false, 'data' => [],
                'message' => 'no items found!', 'description' => '1', 'code' => '400',
            ], 400);
        }

        Cart::where([['customer_id', auth()->user()->id], ['id', $cartId]])->delete();

        $cart = Cart::where([['customer_id', auth()->user()->id], ['city_id', $currentCity->id]]);

        $first = $cart->get()->first();
        $validated = [
            "using_wallet" => 0,
            'applied_discount_code' => $first != null ? $first->applied_discount_code : null,
        ];

        $preview = $this->invoicePreview($validated, $cart->get(), $currentCity, $country);

        return response()->json([
            'success' => true, 'data' => ['cart' => $cart->cartDetails()->get(), 'invoice-preview' => $preview],
            'message' => '', 'description' => '', 'code' => '200',
        ], 200);
    }

    public function getInvoicePreview(Request $request)
    {

        $validated = $request->validate([
            "using_wallet" => 'required|bool',
            'applied_discount_code' => 'nullable|exists:discounts,code',
        ]);

        $cartProducts = Cart::where('customer_id', auth()->user()->id);

        if ($cartProducts->get()->first() == null) {
            return response()->json([
                'success' => false, 'data' => [],
                'message' => 'failed', 'description' => 'add itmes to your cart first!', 'code' => '400',
            ], 400);
        }

        $preview = [];
        return response()->json([
            'success' => true, 'data' => $preview,
            'message' => '', 'description' => '', 'code' => '200',
        ], 200);
    }
}
