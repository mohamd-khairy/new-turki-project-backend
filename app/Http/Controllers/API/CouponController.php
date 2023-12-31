<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Country;
use App\Models\Customer;
use App\Models\Shalwata;
use App\Models\Discount;
use App\Models\Product;
use App\Models\Category;
use App\Models\TraceError;
use App\Models\TempCouponProducts;
use App\Models\SubCategory;
use App\Http\Resources\CategoryAppListRecource;
use App\Http\Resources\ProductCouponResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\PointLocation;

class CouponController extends Controller
{

    public function getAll()
    {
        $data = Discount::latest()->get();

        return response()->json([
            'success' => 'true', 'data' => $data,
            'message' => 'retrieved successfully', 'description' => '', 'code' => '200'
        ], 200);
    }

    public function getCouponById(Discount $discount)
    {
        return response()->json([
            'success' => true, 'message' => '', 'description' => "", "code" => "200",
            "data" => $discount
        ], 200);
    }

    public function listCategories(Request $request)
    {

        if (Category::all()->isEmpty())
            return response()->json(['data' => []], 200);

        $data = CategoryAppListRecource::collection(Category::orderBy('sort', 'ASC')->get());
        return response()->json([
            'success' => true, 'data' => $data,
            'message' => 'Categories retrieved successfully', 'description' => 'list Of Categories', 'code' => '200'
        ], 200);
    }

    public function listSubCategories(Request $request)
    {

        $data = SubCategory::all();

        return response()->json([
            'success' => true, 'data' => $data,
            'message' => 'Sub-categories retrieved successfully', 'description' => 'list Of Sub-categories', 'code' => '200'
        ], 200);
    }


    public function listProduct(Request $request)
    {

        $data = Product::all();
        $data = ProductCouponResource::collection($data);
        return response()->json([
            'success' => true, 'data' => $data,
            'message' => 'retrieved successfully', 'description' => '', 'code' => '200'
        ], 200);
    }

    public function listCustomer(Request $request)
    {
        $perPage = 6;
        if ($request->has('per_page'))
            $perPage = $request->get('per_page');

        if ($perPage == 0)
            $perPage = 6;
        $validatedData = $request->validate([
            'mobile' => 'required',

        ]);


        $data = Customer::where([['wallet', '>', 0], ['mobile', 'like', '%' . $validatedData['mobile'] . '%']])->get();

        //  $data = Customer::paginate($perPage);
        return response()->json([
            'success' => true, 'data' => $data,
            'message' => 'retrieved successfully', 'description' => '', 'code' => '200'
        ], 200);

        //       }

        // }


    }

    public function createCoupon(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|unique:discounts,name',
            'code' => array('required', 'regex:(^[a-zA-Z0-9_]*$)', 'min:3', 'max:10', 'unique:discounts,code'),

            'discount_amount_percent' => 'required|numeric',
            'min_applied_amount' => 'nullable|numeric',
            'max_discount' => 'nullable|numeric',
            'is_for_all' => 'required|boolean',
            'is_by_city' => 'required|boolean',
            'is_by_country' => 'required|boolean',
            'is_by_category' => 'required|boolean',
            'is_by_subcategory' => 'required|boolean',
            'is_by_product' => 'required|boolean',
            'for_clients_only' => 'required|boolean',
            'is_percent' => 'required|boolean',
            'is_active' => 'required|boolean',
            'expire_at' => array('required', 'date'),
            'use_times_per_user' => 'required|numeric',

            // 'product_ids' => array('array'),
            'product_ids.*' => array('required_with:product_ids', 'exists:products,id'),
            // 'category_parent_ids' => array('array'),
            'category_parent_ids.*' => array('required_with:category_parent_ids', 'exists:categories,id'),
            // 'category_child_ids' => array('array'),
            'category_child_ids.*' => array('required_with:category_child_ids', 'exists:sub_categories,id'),
            // 'city_ids' => array('array'),
            'city_ids.*' => array('required_with:city_ids', 'exists:cities,id'),
            // 'country_ids' => array('array'),
            'country_ids.*' => array('required_with:country_ids', 'exists:countries,id'),
            // 'client_ids' => array('array'),
            'client_ids.*' => array('required_with:client_ids', 'exists:customers,id'),
        ]);


        $product_ids = isset($request->product_ids) && is_array($request->product_ids) ? $request->product_ids : json_decode($request->product_ids);
        $validatedData['product_ids'] = $product_ids ? implode(",", $product_ids) : null;

        $city_ids = isset($request->city_ids) && is_array($request->city_ids) ? $request->city_ids : json_decode($request->city_ids);
        $validatedData['city_ids'] = $city_ids ? implode(",", $city_ids) : null;

        $client_ids = isset($request->client_ids) && is_array($request->client_ids) ? $request->client_ids : json_decode($request->client_ids);
        $validatedData['client_ids'] = $client_ids ? implode(",", $client_ids) : null;


        $country_ids = isset($request->country_ids) && is_array($request->country_ids) ? $request->country_ids : json_decode($request->country_ids);
        $validatedData['country_ids'] = $country_ids ? implode(",", $country_ids) : null;

        $category_parent_ids = isset($request->category_parent_ids) && is_array($request->category_parent_ids) ? $request->category_parent_ids : json_decode($request->category_parent_ids);
        $validatedData['category_parent_ids'] = $category_parent_ids ? implode(",", $category_parent_ids) : null;

        $category_child_ids = isset($request->category_child_ids) && is_array($request->category_child_ids) ? $request->category_child_ids : json_decode($request->category_child_ids);
        $validatedData['category_child_ids'] = $category_child_ids ? implode(",", $category_child_ids) : null;

        $discount = Discount::create($validatedData);


        return response()->json([
            'success' => true, 'data' => $discount,
            'message' => 'Successfully Added!', 'description' => 'Add Coupon', 'code' => '200'
        ], 200);
    }


    public function updateCoupon(Request $request, Discount $discount)
    {

        $validatedData = $request->validate([


            'discount_amount_percent' => 'sometimes|numeric',
            'min_applied_amount' => 'nullable|numeric',
            'max_discount' => 'nullable|numeric',
            'is_for_all' => 'sometimes|boolean',
            'is_by_city' => 'nullable|boolean',
            'is_by_country' => 'nullable|boolean',
            'is_by_category' => 'nullable|boolean',
            'is_by_subcategory' => 'nullable|boolean',
            'for_clients_only' => 'nullable|boolean',
            'is_by_product' => 'nullable|boolean',
            'is_percent' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
            'expire_at' => array('sometimes', 'date'),
            'use_times_per_user' => 'required|numeric',

            // 'product_ids' => array('array'),
            'product_ids.*' => array('required_with:product_ids', 'exists:products,id'),
            // 'category_parent_ids' => array('nullable', 'array'),
            'category_parent_ids.*' => array('required_with:category_parent_ids', 'exists:categories,id'),
            // 'category_child_ids' => array('nullable', 'array'),
            'category_child_ids.*' => array('required_with:category_child_ids', 'exists:sub_categories,id'),
            // 'city_ids' => array('nullable', 'array'),
            'city_ids.*' => array('required_with:city_ids', 'exists:cities,id'),
            // 'country_ids' => array('nullable', 'array'),
            'country_ids.*' => array('required_with:country_ids', 'exists:countries,id'),
            // 'client_ids' => array('nullable', 'array'),
            'client_ids.*' => array('required_with:client_ids', 'exists:customers,id'),
        ]);

        $product_ids = isset($request->product_ids) && is_array($request->product_ids) ? $request->product_ids : json_decode($request->product_ids);
        $validatedData['product_ids'] = $product_ids ? implode(",", $product_ids) : null;

        $city_ids = isset($request->city_ids) && is_array($request->city_ids) ? $request->city_ids : json_decode($request->city_ids);
        $validatedData['city_ids'] = $city_ids ? implode(",", $city_ids) : null;

        $client_ids = isset($request->client_ids) && is_array($request->client_ids) ? $request->client_ids : json_decode($request->client_ids);
        $validatedData['client_ids'] = $client_ids ? implode(",", $client_ids) : null;


        $country_ids = isset($request->country_ids) && is_array($request->country_ids) ? $request->country_ids : json_decode($request->country_ids);
        $validatedData['country_ids'] = $country_ids ? implode(",", $country_ids) : null;

        $category_parent_ids = isset($request->category_parent_ids) && is_array($request->category_parent_ids) ? $request->category_parent_ids : json_decode($request->category_parent_ids);
        $validatedData['category_parent_ids'] = $category_parent_ids ? implode(",", $category_parent_ids) : null;

        $category_child_ids = isset($request->category_child_ids) && is_array($request->category_child_ids) ? $request->category_child_ids : json_decode($request->category_child_ids);
        $validatedData['category_child_ids'] = $category_child_ids ? implode(",", $category_child_ids) : null;


        $discount->update($validatedData);

        return response()->json([
            'success' => true, 'data' => $validatedData,
            'message' => 'Successfully updated!', 'description' => '', 'code' => '200'
        ], 200);
    }

    public function delete(Discount $discount)
    {
        $id = $discount->id;

        if ($discount->delete()) {

            return response()->json(['massage' => 'Successfully Deleted!'], 200);
        } else {
            return response()->json(['success' => false, 'message' => 'Not exist!'], 500);
        }
    }


    function checkValidation(Request $request)
    {

        $validate = $request->validate([
            'code' => 'required|exists:discounts,code',
        ]);

        TraceError::create(['class_name' => "CouponController::consumer sent data200", 'method_name' => "checkValidation", 'error_desc' => json_encode($request->all())]);

        $point = $request->query('longitude') . " " . $request->query('latitude');
        $countryId = $request->query('countryId');
        $country = Country::where('code', $countryId)->get()->first();

        if ($country === null)
            return response()->json([
                'data' => [],
                'success' => true, 'message' => 'success', 'description' => 'this service not available in your country!', 'code' => '200'
            ], 200);

        $currentCity = app(PointLocation::class)->getLocatedCity($country, $point);

        if ($currentCity === null)
            return response()->json([
                'data' => [],
                'success' => true, 'message' => 'success', 'description' => 'this service not available in your city!', 'code' => '200'
            ], 200);


        $cart = Cart::where([['customer_id', auth()->user()->id], ['city_id', $currentCity->id]])->get();

        if (count($cart) == 0) {
            return response()->json([
                'success' => false, 'data' => [],
                'message' => 'failed', 'description' => 'add itmes to your cart first!', 'code' => '400'
            ], 400);
        }


        $shalwata = Shalwata::first();
        $totalItemsAmount = 0.0;
        $totalAddonsAmount = 0.0;
        $TotalAmountBeforeDiscount = 0.0;
        $TotalAmountAfterDiscount = 0.0;
        $orderProducts = [];
        $discountCode = null;
        $discountAmount = 0;

        list($cartProduct, $discountCode, $totalAddonsAmount, $totalItemsAmount, $orderProducts)
            = app(OrderController::class)->calculateProductsAmount($cart, $validate['code'], $shalwata, $totalAddonsAmount, $totalItemsAmount, $orderProducts);
        TraceError::create(['class_name' => "CouponController::consumer sent data239", 'method_name' => "checkValidation", 'error_desc' => json_encode($discountCode)]);

        $TotalAmountBeforeDiscount = $totalItemsAmount + $totalAddonsAmount;

        list($couponValid, $discountAmount, $TotalAmountAfterDiscount, $couponValidatingResponse, $applicableProductIds) = $this->discountProcess($discountCode, $cart, $TotalAmountBeforeDiscount, $discountAmount, $TotalAmountAfterDiscount, $country->id, $currentCity->id);
        if ($couponValid == null) {
            return response()->json([
                'success' => false, 'data' => Cart::where('customer_id', auth()->user()->id)->get(),
                'message' => $couponValidatingResponse[0] . ":" . $couponValidatingResponse[1], 'description' => 'invalid coupon used', 'code' => '400'
            ], 400);
        }

        TraceError::create(['class_name' => "CouponController::consumer sent data248", 'method_name' => "checkValidation", 'error_desc' => json_encode($discountCode)]);

        return response()->json([
            'success' => true, 'data' => Cart::where('customer_id', auth()->user()->id)->get(),
            'message' => 'valid', 'description' => 'valid coupon used', 'code' => '200'
        ], 200);
    }

    /**
     * @param $discountCode
     * @param $cart
     * @param $TotalAmountBeforeDiscount
     * @param $discountAmount
     * @param $TotalAmountAfterDiscount
     * @return array
     */
    public function discountProcess($discountCode, $cart, $TotalAmountBeforeDiscount, $discountAmount, $TotalAmountAfterDiscount, $countryId, $cityId)
    {
        $coupon = Discount::where([['code', $discountCode], ['is_active', 1]])->get()->first();
        $productIds = $cart->pluck('product_id')->toArray();
        $couponValidatingResponse = Discount::isValidV2($coupon, $discountCode, $productIds, $TotalAmountBeforeDiscount, $countryId, $cityId);

        $couponValid = null;
        $notApplicableProductIds = [];
        switch ($couponValidatingResponse[0]) {
            case 400:
                $couponValid = $couponValidatingResponse[1];
                break;
            case 401:
                $couponValid = $couponValidatingResponse[1];
                $notApplicableProductIds = $couponValidatingResponse[2];
                break;
            default:
                $this->removeCoupon();
                return array(null, null, null, $couponValidatingResponse);
        }

        $totalApplicableItemsAmount = 0.0;
        $totalApplicableAddonsAmount = 0.0;
        $totalNotApplicableItemsAmount = 0.0;
        $totalNotApplicableAddonsAmount = 0.0;
        $notApplicableProductIds != 0;

        $applicableProductIds = array_diff($productIds, $notApplicableProductIds);

        if (count($applicableProductIds) != 0) {
            $applicableProducts = Cart::where([['customer_id', auth()->user()->id], ['city_id', $cityId]])->whereIn('product_id', $applicableProductIds)->get();

            list($totalApplicableItemsAmount, $totalApplicableAddonsAmount) = app(OrderController::class)->getTotalProductsAmount($applicableProducts, Shalwata::first());
        }

        $totalApplicableAmountBeforeDiscount = $totalApplicableItemsAmount + $totalApplicableAddonsAmount;

        if ($couponValid->is_percent == true && $TotalAmountBeforeDiscount != 0.0 && $couponValid->discount_amount_percent != 0) {
            $discountAmount = (($totalApplicableAmountBeforeDiscount * $couponValid->discount_amount_percent) / 100);

            // // if ($discountAmount > $couponValid->max_discount)
            // //     $discountAmount = $couponValid->max_discount;

            $TotalAmountAfterDiscount = $TotalAmountBeforeDiscount - $discountAmount;

            if ($TotalAmountAfterDiscount < 0)
                $TotalAmountAfterDiscount = 0;
        } else if ($couponValid->is_percent == false && $TotalAmountBeforeDiscount != 0.0 && $couponValid->discount_amount_percent != 0) {

            $discountAmount = $couponValid->discount_amount_percent;

            // // if ($discountAmount > $couponValid->max_discount)
            // //     $discountAmount = $couponValid->max_discount;

            if (($totalApplicableAmountBeforeDiscount - $discountAmount) < 0)
                $totalApplicableAmountBeforeDiscount = 0;

            $TotalAmountAfterDiscount = $TotalAmountBeforeDiscount - $totalApplicableAmountBeforeDiscount;

            if ($TotalAmountAfterDiscount < 0)
                $TotalAmountAfterDiscount = 0;
        }

        $this->saveCouponForOrder($discountCode);

        return array($couponValid, $discountAmount, $TotalAmountAfterDiscount, $couponValidatingResponse, $applicableProductIds);
    }

    private function removeCoupon()
    {
        DB::statement("update carts set applied_discount_code = NULL where customer_id = " . auth()->user()->id);
    }

    /**
     * @param $discountCode
     * @return void
     */
    private function saveCouponForOrder($discountCode): void
    {
        DB::statement("update carts set applied_discount_code = '" . $discountCode
            . "' where customer_id = " . auth()->user()->id);
    }

    private function saveCouponForNSOrder($orderId, $discountCode, $applicableProductIds): void
    {
        TempCouponProducts::create([
            "order_id" => $orderId,
            "coupon_code" => $discountCode,
            "product_ids" => json_encode($applicableProductIds)
        ]);
    }
}
