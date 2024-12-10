<?php

namespace App\Http\Controllers\API;

use App\Enums\OrderStateEnums;
use App\Http\Controllers\Controller;
use App\Http\Resources\SubcategoryListWithProductResource;
use App\Models\City;
use App\Models\Country;
use App\Models\Customer;
use App\Models\Cut;
use App\Models\ProductImage;
use App\Models\Wishlist;
use App\Models\OrderProduct;
use App\Models\PaymentType;
use App\Models\Preparation;
use App\Models\Product;
use App\Models\Category;
use App\Models\ProductCity;
use App\Models\SubCategoryCity;
use App\Models\Size;
use App\Models\ProductRating;
use App\Models\SubCategory;
use App\Http\Resources\ProductListResource;
use App\Http\Resources\BestSellerResource;
use App\Http\Resources\ProductDetailsResource;
use App\Http\Resources\ProductAppDetailsResource;
use App\Services\Google_Map_API\GeocodingService;
use App\Services\PointLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\ImageManagerStatic as Image;

class ProductController extends Controller
{
    public function __construct()
    {
        if (env('PERMISSIONS', false)) {
            $this->middleware('permission:read-product', ['only' => ['getAll', 'getProductById']]);
            $this->middleware('permission:create-product', ['only' => ['create']]);
            $this->middleware('permission:update-product', ['only' => ['update']]);
            $this->middleware('permission:delete-product', ['only' => ['delete']]);
        }

        if (request()->header('Type') == 'dashboard') {
            $this->middleware('auth:sanctum');
        }
    }

    public function autoAddress(Request $request)
    {

        $latitude = $request->query('lat');
        $longitude = $request->query('long');

        if ($latitude == null || $latitude == "" || $longitude == null || $longitude == "") {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'provide the lang,lat please!',
                'description' => '',
                'code' => '400'
            ], 400);
        }

        $gooMap = app(GeocodingService::class)->searchByCoordination($latitude, $longitude);
        //  dd($gooMap);
        //    if ($a['longitude'] === 0.0000000)
        //        $a->update([
        //            'address' => $gooMap['formatted_address'],
        //            'city_id' => 3,
        //            'longitude' => $gooMap['location']['lng'],
        //            'latitude' => $gooMap['location']['lat'],
        //        ]);

    }

    public function all()
    {
        $all = DB::table('products')
            ->select('products.id', 'products.name_ar')
            ->when(request('country_id'), function ($query) {
                $query->join('product_cities', 'product_cities.product_id', '=', 'products.id')
                    ->join('cities', 'cities.id', '=', 'product_cities.city_id') // Assuming a 'cities' table has 'product_id'
                    ->where('cities.country_id', request('country_id'));
            })
            ->groupBy('products.id')
            ->get();
        return successResponse($all, 'Products retrieved successfully');
    }

    public function getAll(Request $request)
    {
        // get coordinates for query params.
        $latitude = $request->query('latitude');
        $longitude = $request->query('longitude');

        $perPage = 15;
        if ($request->has('per_page'))
            $perPage = $request->get('per_page');

        if ($perPage == 0)
            $perPage = 6;

        if ($request->header('Type', 'dashboard')) {

            $products = Product::with(
                'category',
                'subCategory',
                'cities',
                'productSizes',
                'productCuts',
                'shalwata',
                'productImages',
                'productPreparations',
                'productPaymentTypes'
            )->orderBy('id', 'desc')
                ->when(auth()->check() && !in_array('admin', auth()->user()->roles->pluck('name')->toArray()) && request()->header('Type') == 'dashboard', function ($query) {
                    $query->whereHas('cities', function ($q) {
                        $q->where('country_id', strtolower(auth()->user()->country_code) == 'sa' ? 1 : 4);;
                    });
                });

            if (request('is_active')) {
                $products = $products->where('is_active', request('is_active'));
            }

            if (request('category_id')) {
                $products = $products->where('category_id', request('category_id'));
            }


            if (request('sub_category_id')) {
                $products = $products->where('sub_category_id', request('sub_category_id'));
            }

            if (request('city_id')) {
                $products = $products->whereHas('cities', function ($q) {
                    $q->where('city_id', request('city_id'));
                });
            }

            if (request('search')) {
                $products = $products->where(function ($q) {
                    $q->where('name_ar', 'like', '%' . request('search') . '%')
                        ->orWhere('name_en', 'like', '%' . request('search') . '%');
                });
            }

            $products = request('per_page') == -1 ? $products->get() : $products->paginate($perPage);

            return successResponse($products, 'Products retrieved successfully');
        } else {
            $products = Product::with('category', 'subCategory', 'cities', 'productSizes', 'productCuts', 'shalwata', 'productImages', 'productPreparations', 'productPaymentTypes')
                ->where('is_active', '1')->paginate($perPage);
            return successResponse(ProductListResource::Collection($products), 'Products retrieved successfully');
        }
    }

    public function getProductById(Product $product)
    {
        return response()->json([
            'success' => true,
            'data' => new ProductDetailsResource($product),
            'message' => 'Products retrieved successfully',
            'description' => "",
            'code' => '200'
        ], 200);
    }

    public function getAppProductById(Request $request, $productApp)
    {

        if (Product::find($productApp) === null)
            return response()->json([
                'data' => [],
                'success' => false,
                'message' => 'failed',
                'description' => 'invalid product!',
                'code' => '400'
            ], 400);


        $point = $request->query('longitude') . " " . $request->query('latitude');
        $countryId = $request->query('countryId');
        $country = Country::where('code', $countryId)->get()->first();

        if ($country === null)
            return response()->json([
                'data' => [],
                'success' => false,
                'message' => 'failed',
                'description' => 'this service not available in your country!',
                'code' => '404'
            ], 404);

        $currentCity = null;
        try {
            $currentCity = app(PointLocation::class)->getLocatedCity($country, $point);
        } catch (\Exception $e) {
            return response()->json([
                'data' => [],
                'success' => false,
                'message' => 'failed',
                'description' => 'this service not available in your city, contact support!',
                'code' => '404'
            ], 404);
        }


        if ($currentCity === null)
            return response()->json([
                'data' => [],
                'success' => false,
                'message' => 'failed',
                'description' => 'this service not available in your city!',
                'code' => '404'
            ], 404);

        $productCity = ProductCity::where([['city_id', $currentCity->id], ['product_id', $productApp]])->get()->first();

        if ($productCity === null)
            return response()->json([
                'data' => [],
                'success' => false,
                'message' => 'failed',
                'description' => 'product not found in this city!',
                'code' => '404'
            ], 404);


        return response()->json([
            'success' => true,
            'data' => new ProductAppDetailsResource($productCity->product),
            'message' => 'Products retrieved successfully',
            'description' => 'list Of Products',
            'code' => '200'
        ], 200);
    }

    public function getProductByCategory(Request $request, $category)
    {

        $category = Category::find($category);
        if ($category == null)
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'not found',
                'description' => '',
                'code' => '404'
            ], 404);

        // get by location
        $point = $request->query('longitude') . " " . $request->query('latitude');
        $countryId = $request->query('countryId');
        $country = Country::where('code', $countryId)->get()->first();

        if ($country === null)
            return response()->json([
                'data' => [],
                'success' => false,
                'message' => 'success',
                'description' => 'this service not available in your country!',
                'code' => '400'
            ], 400);

        $currentCity = app(PointLocation::class)->getLocatedCity($country, $point);

        if ($currentCity != null) {
            $subCategoryIds = SubCategoryCity::where('city_id', $currentCity->id)->distinct()->pluck('sub_category_id');
            $subcategories = SubCategory::whereIn('id', $subCategoryIds)->where('category_id', $category->id)->distinct()->orderBy('sort', 'ASC')->get();
            $productIds = ProductCity::where('city_id', $currentCity->id)->distinct()->pluck('product_id');
            foreach ($subcategories as $d) {
                $arr = array_intersect($d->products()->pluck('id')->toArray(), $productIds->toArray());
                $d->products = Product::whereIn('id', $arr)->active()->with('productImages', 'tags')->orderBy('sort', 'asc')->get();
            }
        } else
            $subcategories = [];

        $data = SubcategoryListWithProductResource::Collection($subcategories);

        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => 'Sub-categories retrieved successfully',
            'description' => 'list Of Sub-categories',
            'code' => '200'
        ], 200);
    }

    public function getProductBySubCategory(Request $request, SubCategory $subCategory)
    {
        if ($subCategory === null)
            return response()->json([
                'data' => [],
                'success' => true,
                'message' => 'success',
                'description' => ' no subcategory with this id',
                'code' => '200'
            ], 200);

        $perPage = 6;
        if ($request->has('per_page'))
            $perPage = $request->get('per_page');

        if ($perPage == 0)
            $perPage = 6;

        if (auth()->user() != null) {
            $customer = Customer::find(auth()->user()->id);
            $address = $customer->address()->where('is_default', 1)->get()->last();
            $products = Product::where([['city_id', $address->city_id], ['sub_category_id', $subCategory->id]])->paginate($perPage);
        } else {
            $products = Product::where('sub_category_id', $subCategory->id)->paginate($perPage);
        }

        return response()->json([
            'data' =>  ProductListResource::Collection($products),
            'success' => true,
            'message' => 'success',
            'description' => '',
            'code' => '200'
        ], 200);
    }

    public function create(Request $request)
    {
        try {

            return DB::transaction(function () use ($request) {

                $requestData = Validator::make($request->all(), [
                    'name_ar' => 'required|max:255|unique:products,name_ar',
                    'name_en' => 'required|max:255|unique:products,name_en',
                    'description_ar' => 'required|max:255',
                    'description_en' => 'required|max:255',
                    'weight' => 'required|max:255',
                    'calories' => 'required|max:255',
                    'price' => 'required|numeric',
                    'sale_price' => 'required|numeric',
                    'category_id' => 'required|exists:categories,id',
                    'sub_category_id' => 'nullable|exists:sub_categories,id',
                    'is_active' => 'required|bool',
                    // 'is_available' => 'required|bool',
                    'is_kwar3' => 'required|bool',
                    'is_Ras' => 'required|bool',
                    'is_lyh' => 'required|bool',
                    'is_karashah' => 'required|bool',
                    'is_shalwata' => 'required|bool',
                    'is_delivered' => 'required|bool',
                    'is_picked_up' => 'required|bool',
                    'integrate_id' => 'sometimes|numeric',
                    'preparation_ids' => array('nullable', 'regex:(^([-+] ?)?[0-9]+(,[0-9]+)*$)'),
                    'size_ids' => array('required', 'regex:(^([-+] ?)?[0-9]+(,[0-9]+)*$)'),
                    'cut_ids' => array('nullable', 'regex:(^([-+] ?)?[0-9]+(,[0-9]+)*$)'),
                    'payment_type_ids' => array('required', 'regex:(^([-+] ?)?[0-9]+(,[0-9]+)*$)'),
                    'city_ids' => array('required', 'regex:(^([-+] ?)?[0-9]+(,[0-9]+)*$)'),
                    'images' => 'array|nullable',
                    "images.*"  => "required|image|mimes:png,jpg,jpeg|max:4048",
                ]);

                $validatedData = $requestData->validated();

                $productCreationData = $request->except('integrate_id', 'preparation_ids', 'size_ids', 'cut_ids', 'payment_type_ids', 'city_ids', 'images');

                $product = Product::create($productCreationData);

                if ($product) {

                    if ($validatedData['city_ids']) {
                        $city_ids = is_array($validatedData['city_ids']) ? $validatedData['city_ids'] : explode(',', $validatedData['city_ids']);
                        $cities = City::whereIn('id', $city_ids)->get();
                        $product->productCities()->attach($cities);
                    }

                    if ($validatedData['preparation_ids']) {

                        $preparation_ids = is_array($validatedData['preparation_ids']) ? $validatedData['preparation_ids'] : explode(',', $validatedData['preparation_ids']);
                        $preparations = Preparation::whereIn('id', $preparation_ids)->get();
                        $product->productPreparations()->attach($preparations);
                    }

                    if ($validatedData['size_ids']) {

                        $size_ids = is_array($validatedData['size_ids']) ? $validatedData['size_ids'] : explode(',', $validatedData['size_ids']);
                        $sizes = Size::whereIn('id', $size_ids)->get();
                        $product->productSizes()->attach($sizes);
                    }
                    if ($validatedData['cut_ids']) {

                        $cut_ids = is_array($validatedData['cut_ids']) ? $validatedData['cut_ids'] : explode(',', $validatedData['cut_ids']);
                        $cuts = Cut::whereIn('id', $cut_ids)->get();
                        $product->productCuts()->attach($cuts);
                    }

                    if ($validatedData['payment_type_ids']) {

                        $payment_type_ids = is_array($validatedData['payment_type_ids']) ? $validatedData['payment_type_ids'] : explode(',', $validatedData['payment_type_ids']);
                        $paymentTypes = PaymentType::whereIn('id', $payment_type_ids)->get();
                        $product->productPaymentTypes()->attach($paymentTypes);
                    }
                    Product::uploadProductImages($product->id, $request);

                    //  $res = $this->sendOrderToNS($product, $request);

                    return successResponse($product, 'تم اضافه المنتج بنجاح');
                }

                return failResponse([], 'لم تتم اضافة المنتج');
            });
        } catch (\Throwable $th) {
            // throw $th;

            return failResponse([], $th->getMessage());
        }
    }

    public function update(Request $request, Product $product)
    {
        try {

            return DB::transaction(function () use ($request, $product) {

                $requestData = Validator::make($request->post(), [
                    'name_ar' => 'string|max:255|unique:products,name_ar,' . $product->id,
                    'name_en' => 'string|max:255|unique:products,name_en,' . $product->id,
                    'description_ar' => 'string|max:255',
                    'description_en' => 'string|max:255',
                    'weight' => 'nullable|string|max:255',
                    'calories' => 'nullable|string|max:255',
                    'price' => 'required|numeric',
                    'sale_price' => 'required|numeric',
                    'category_id' => 'required|exists:categories,id',
                    'sub_category_id' => 'nullable|exists:sub_categories,id',
                    'is_active' => 'required|bool',
                    'is_shalwata' => 'required|bool',
                    // 'is_available' => 'required|bool',
                    'is_kwar3' => 'required|bool',
                    'is_Ras' => 'required|bool',
                    'is_lyh' => 'required|bool',
                    'is_karashah' => 'required|bool',
                    'is_delivered' => 'required|bool',
                    'is_picked_up' => 'required|bool',
                    'integrate_id' => 'sometimes|numeric',
                    'preparation_ids' => array('nullable', 'regex:(^([-+] ?)?[0-9]+(,[0-9]+)*$)'),
                    'size_ids' => array('nullable', 'regex:(^([-+] ?)?[0-9]+(,[0-9]+)*$)'),
                    'cut_ids' => array('nullable', 'regex:(^([-+] ?)?[0-9]+(,[0-9]+)*$)'),
                    'payment_type_ids' => array('required', 'regex:(^([-+] ?)?[0-9]+(,[0-9]+)*$)'),
                    'city_ids' => array('required', 'regex:(^([-+] ?)?[0-9]+(,[0-9]+)*$)'),
                    'images' => 'array|nullable',
                    "images.*"  => "required|image|mimes:png,jpg,jpeg|max:4048",
                ]);



                $productCreationData = $request->except('integrate_id', 'preparation_ids', 'size_ids', 'cut_ids', 'payment_type_ids', 'city_ids', 'images');

                $validatedData = $requestData->validated();

                $subCategory = SubCategory::where([['category_id', $validatedData['category_id']], ['id', $validatedData['sub_category_id']]])->get()->first();
                if ($subCategory == null)
                    return response()->json(['success' => false, 'data' => null, 'message' => "failed, check your selected sub category!", 'description' => "", 'code' => "400"], 400);

                $product->update($productCreationData);

                if ($validatedData['city_ids']) {
                    $city_ids = is_array($validatedData['city_ids']) ? $validatedData['city_ids'] : explode(',', $validatedData['city_ids']);
                    $cities = City::whereIn('id', $city_ids)->get();
                    $product->productCities()->sync($cities);
                }

                if ($validatedData['preparation_ids']) {

                    $preparation_ids = is_array($validatedData['preparation_ids']) ? $validatedData['preparation_ids'] : explode(',', $validatedData['preparation_ids']);
                    $preparations = Preparation::whereIn('id', $preparation_ids)->get();
                    $product->productPreparations()->sync($preparations);
                }

                if ($validatedData['size_ids']) {

                    $size_ids = is_array($validatedData['size_ids']) ? $validatedData['size_ids'] : explode(',', $validatedData['size_ids']);
                    $sizes = Size::whereIn('id', $size_ids)->get();
                    $product->productSizes()->sync($sizes);
                }
                if ($validatedData['cut_ids']) {

                    $cut_ids = is_array($validatedData['cut_ids']) ? $validatedData['cut_ids'] : explode(',', $validatedData['cut_ids']);
                    $cuts = Cut::whereIn('id', $cut_ids)->get();
                    $product->productCuts()->sync($cuts);
                }

                if ($validatedData['payment_type_ids']) {

                    $payment_type_ids = is_array($validatedData['payment_type_ids']) ? $validatedData['payment_type_ids'] : explode(',', $validatedData['payment_type_ids']);
                    $paymentTypes = PaymentType::whereIn('id', $payment_type_ids)->get();
                    $product->productPaymentTypes()->sync($paymentTypes);
                }


                Product::uploadProductImages($product->id, $request);

                return response()->json(['data' => $product, 'message' => "success", 'description' => "", 'code' => "200"], 200);
            });
        } catch (\Throwable $th) {
            // throw $th;

            return failResponse([], $th->getMessage());
        }
    }

    public function ratingProduct(Request $request, Product $product)
    {

        if ($product == null) {
            return response()->json(['data' => null, 'success' => false, 'message' => "failed", 'description' => "please, provide a product!", 'code' => "400"], 400);
        }

        $orderProduct = OrderProduct::where('product_id', $product->id)->get();
        $order =  $orderProduct->order;

        if ($order->customer_id !== auth()->id()) {
            return response()->json(['data' => null, 'success' => false, 'message' => "failed", 'description' => "you dont own this product!", 'code' => "400"], 400);
        }

        $orderState = $order->orderState;

        if ($orderState->code !== OrderStateEnums::getKey("delivered")) {
            return response()->json(['data' => null, 'success' => false, 'message' => "failed", 'description' => "you can not rated this product now!", 'code' => "400"], 400);
        }

        $productRating = ProductRating::where([['customer_id', auth()->id()], ['product_id', $product->id]])->get()->first();

        if ($productRating !== null) {
            return response()->json(['data' => null, 'success' => false, 'message' => "failed", 'description' => "you already rate this product!", 'code' => "400"], 400);
        }

        $validate = $request->validate([
            'rating' => 'required|numeric|min:0.0|max:5.0',
            'comment' => 'required|string',
        ]);

        $productRating = ProductRating::create([
            'customer_id' => auth()->id(),
            'product_id' => $product->id,
            'rating' => $validate['rating'],
            'comment' => $validate['comment']
        ]);

        if ($productRating === null)
            return response()->json(['data' => null, 'success' => false, 'message' => "failed", 'description' => "please, try again later!", 'code' => "500"], 500);

        $avgRating = ProductRating::where('product_id', $product->id)->avg('rating');

        $product->no_rating = $avgRating;
        if (!$product->update())
            return response()->json(['message' => 'product rating has not updated'], 500);

        return response()->json(['data' => null, 'success' => true, 'message' => "success", 'description' => "", 'code' => "200"], 200);
    }

    public function addFavoriteProduct($product, Request $request)
    {

        $product = Product::find($product);
        if ($product == null) {
            return response()->json(['data' => null, 'success' => false, 'message' => "failed", 'description' => "please, provide a product!", 'code' => "400"], 400);
        }

        $favorite = Wishlist::where([['customer_id', auth()->user()->id], ['product_id', $product->id]])->get()->first();

        if ($favorite !== null) {
            return response()->json(['data' => null, 'success' => false, 'message' => "failed", 'description' => "you already added this product!", 'code' => "400"], 400);
        }

        Wishlist::create([
            'customer_id' => auth()->user()->id,
            'product_id' => $product->id
        ]);

        return response()->json(['data' => null, 'success' => true, 'message' => "success", 'description' => "", 'code' => "200"], 200);
    }

    public function removeFavoriteProduct($favorite, Request $request)
    {

        $favorite = Wishlist::find($favorite);

        if ($favorite == null) {
            return response()->json(['data' => null, 'success' => false, 'message' => "failed", 'description' => "please, provide a favorite product id!", 'code' => "400"], 400);
        }

        if ($favorite->customer_id !== auth()->user()->id) {
            return response()->json(['data' => null, 'success' => false, 'message' => "failed", 'description' => "bad request!", 'code' => "400"], 400);
        }

        $favorite->delete();

        return response()->json(['data' => null, 'success' => true, 'message' => "success", 'description' => "", 'code' => "200"], 200);
    }

    public function getFavoriteProduct(Request $request)
    {

        $favorite = Wishlist::where('customer_id', auth()->user()->id)->with('product')->paginate(PerPage($request));

        return response()->json(['data' => $favorite, 'success' => true, 'message' => "success", 'description' => "", 'code' => "200"], 200);
    }

    // public function bestSeller(Request $request){

    //     $products = Product::orderBy('no_sale', 'DESC')->take(10)->get();

    //     return response()->json(['data' => BestSellerResource::Collection($products) , 'success' => true, 'message' => "success", 'description' => "", 'code' => "200"], 200);
    // }

    public function bestSeller(Request $request)
    {

        // get by location
        $point = $request->query('longitude') . " " . $request->query('latitude');
        $countryId = $request->query('countryId');
        $country = Country::where('code', $countryId)->get()->first();

        if ($country === null)
            return response()->json([
                'data' => [],
                'success' => true,
                'message' => 'success',
                'description' => 'this service not available in your country!',
                'code' => '200'
            ], 200);

        $currentCity = app(PointLocation::class)->getLocatedCity($country, $point);

        if ($currentCity != null) {
            $productIds = ProductCity::where('city_id', $currentCity->id)->distinct()->pluck('product_id');
            $products = Product::whereIn('id', $productIds)->orderBy('no_sale', 'DESC')->take(10)->get();

            //   $categories =  Category::categoryCities()->whereIn('city_id', $currentCity->id)->distinct()->get();
        } else
            $products = [];

        // $data = Product::orderBy('no_sale', 'DESC')->take(10)->get();

        return response()->json(['data' => BestSellerResource::Collection($products), 'success' => true, 'message' => "success", 'description' => "", 'code' => "200"], 200);
    }

    public function isClicked(Product $product)
    {

        $product->no_clicked += 1;
        if (!$product->update())
            return response()->json([
                'message' => 'course has not updated, contact support',
                'input' => $product->id
            ], 500);

        return response()->json(['message' => 'successfully updated'], 200);
    }


    function search(Request $request, $name)
    {
        $point = $request->query('longitude') . " " . $request->query('latitude');
        $countryId = $request->query('countryId');
        $country = Country::where('code', $countryId)->get()->first();

        if ($country === null)
            return response()->json([
                'data' => [],
                'success' => true,
                'message' => 'success',
                'description' => 'this service not available in your country!',
                'code' => '200'
            ], 200);

        $currentCity = null;
        try {
            $currentCity = app(PointLocation::class)->getLocatedCity($country, $point);
        } catch (\Exception $e) {
            return response()->json([
                'data' => [],
                'success' => true,
                'message' => 'success',
                'description' => 'this service not available in your city, contact support!',
                'code' => '200'
            ], 200);
        }


        if ($currentCity === null)
            return response()->json([
                'data' => [],
                'success' => true,
                'message' => 'success',
                'description' => 'this service not available in your city!',
                'code' => '200'
            ], 200);


        $productIds = ProductCity::where('city_id', $currentCity->id)->distinct()->pluck('product_id');
        $products = Product::where('is_active', '1')->whereIn('id', $productIds)->where('name_ar', 'LIKE', '%' . $name . '%')->orWhere('name_en', 'LIKE', '%' . $name . '%')->with('productImages')->get();


        if (count($products)) {
            return Response()->json($products);
        } else {
            return response()->json(['Result' => 'No Data not found'], 404);
        }
    }


    public function deleteImage(ProductImage $productImage)
    {
        $id = $productImage->id;

        if ($productImage->delete()) {
            Storage::delete('product_image/' . $id . '/' . $productImage->image);
            return response()->json(['massage:' => 'Product Image has been deleted successfully'], 200);
        } else {
            return response()->json(['message' => 'ERROR PLEASE TRY AGAIN LATER'], 500);
        }
    }

    public function delete($productId)
    {
        if (!is_numeric($productId))
            return response()->json(['message' => 'id should be numeric', 'input' => $productId], 400);

        $product = Product::find($productId);
        if (is_null($product))
            return response()->json(['message' => 'no product found!', 'input' => $product], 404);

        if (!$product->delete())
            return response()->json([
                'message' => 'product has not deleted, contact support please',
                'input' => $productId
            ], 500);

        return response()->json(['message' => 'Successfully Deleted!'], 200);
    }
}
