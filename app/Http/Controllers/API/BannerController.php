<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\City;
use App\Models\Category;
use App\Models\Country;
use App\Models\BannerCity;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use App\Http\Resources\BannerListResource;
use App\Http\Resources\CategoryListWithBannerTestResource;
use App\Http\Resources\BannerDetailsResource;
use App\Services\PointLocation;

class BannerController extends Controller
{
    public function __construct()
    {
        if (env('PERMISSIONS', false)) {
            $this->middleware('permission:read-banner', ['only' => ['getBannersDashboard', 'getBannerById']]);
            $this->middleware('permission:create-banner', ['only' => ['createBanner']]);
            $this->middleware('permission:update-banner', ['only' => ['updateBanner']]);
            $this->middleware('permission:delete-banner', ['only' => ['deleteBanner']]);
        }
    }

    public function getBannerById(Banner $banner)
    {
        $banner->load('bannerCategories', 'bannerCities');

        return response()->json([
            'success' => true, 'message' => '', 'description' => "", "code" => "200",
            "data" => new BannerDetailsResource($banner)
        ], 200);
    }

    public function getBannerByCategory(Request $request, $category)
    {

        $active = $request->query('active');

        $category = Category::find($category);
        if ($category == null)
            return response()->json([
                'success' => false, 'data' => null,
                'message' => 'not found', 'description' => '', 'code' => '404'
            ], 404);

        // get by location

        $point = $request->query('longitude') . " " . $request->query('latitude');
        $countryId = $request->query('countryId');
        $country = Country::where('code', $countryId)->get()->first();

        if ($country === null)
            return response()->json([
                'data' => [],
                'success' => true, 'message' => 'success', 'description' => 'this service not available in your country!', 'code' => '200'
            ], 200);

        $currentCity = app(PointLocation::class)->getLocatedCity($country, $point);


        if ($currentCity != null) {

            $bannerIds = BannerCity::where('city_id', $currentCity->id)->distinct()->pluck('banner_id');

            $banners = Banner::whereIn('id', $bannerIds)
                ->whereHas('bannerCategories', function ($q) use ($category) {
                    $q->where('category_id', $category->id);
                })
                ->where('is_active', '1')->orderBy('id', 'DESC')->get();
        } else
            $banners = [];

        $data =  BannerDetailsResource::collection($banners);

        return response()->json([
            'success' => true, 'data' => $data,
            'message' => 'Categories retrieved successfully', 'description' => 'list Of Categories', 'code' => '200'
        ], 200);
    }


    public function getBanners(Request $request)
    {
        $active = $request->query('active');

        if ($active == "1") {
            $banners = Banner::with('bannerCities', 'bannerCategories')->where('is_active', '1')->get();
        } else {
            $banners = Banner::with('bannerCities', 'bannerCategories')->get();
        }

        return response()->json([
            'success' => true, 'message' => '', 'description' => "", "code" => "200",
            "data" => BannerListResource::Collection($banners)
        ], 200);
    }

    public function getBannersDashboard(Request $request)
    {
        $banners = Banner::with('bannerCities', 'bannerCategories')->get();
        return response()->json([
            'success' => true, 'message' => '', 'description' => "", "code" => "200",
            "data" => BannerListResource::Collection($banners)
        ], 200);
    }


    public function createBanner(Request $request)
    {
        $validatedData = $request->validate([
            'title' => 'sometimes',
            'city_ids' => array('required', 'regex:(^([-+] ?)?[0-9]+(,[0-9]+)*$)'),
            'category_ids' => array('required', 'regex:(^([-+] ?)?[0-9]+(,[0-9]+)*$)'),
            'title_color' => 'sometimes',
            'sub_title' => 'sometimes',
            'sub_title_color' => 'sometimes',
            'button_text' => 'sometimes',
            'button_text_color' => 'sometimes',
            'redirect_url' => 'sometimes',
            'redirect_mobile_url' => 'sometimes',
            'is_active' => 'required',
            'type' => 'sometimes',
            'product_id' => 'sometimes|nullable',
        ]);

        if ($request->file('image')) {
            $imageName = $request->file('image')->hashName();
            $validatedData['image'] = $imageName;
        }

        unset($validatedData['city_ids']);
        unset($validatedData['category_ids']);

        if ($request->type == 2 || $request->redirect_url != null) {
            $validatedData['redirect_mobile_url'] = '/ProductDetails';
        }

        $banner = Banner::create($validatedData);

        if ($request->city_ids) {
            $city_ids = explode(',', $request->city_ids);
            $cities = City::whereIn('id', $city_ids)->get();
            $banner->bannerCities()->sync($cities);
        }

        if ($request->category_ids) {
            $category_ids = explode(',', $request->category_ids);
            $categories = Category::whereIn('id', $category_ids)->get();
            $banner->bannerCategories()->sync($categories);
        }


        if ($request->file('image')) {
            $request->file('image')->storeAs('public/marketingBoxImages/' . $banner['id'], $imageName);
        }

        if ($banner) {
            return response()->json([
                'success' => true, 'message' => '', 'description' => "", "code" => "200",
                "data" => $banner
            ], 200);
        } else
            return response()->json([
                'success' => false, 'message' => 'ERROR PLEASE TRY AGAIN LATER', 'description' => "", "code" => "400",
                "data" => $banner
            ], 400);
    }

    public function updateBanner(Banner $banner, Request $request)
    {
        $validatedate = $request->validate([
            'title' => 'sometimes',
            'title_color' => 'sometimes',
            'city_ids' => array('sometimes', 'regex:(^([-+] ?)?[0-9]+(,[0-9]+)*$)'),
            'category_ids' => array('required', 'regex:(^([-+] ?)?[0-9]+(,[0-9]+)*$)'),
            'sub_title' => 'sometimes',
            'sub_title_color' => 'sometimes',
            'button_text' => 'sometimes',
            'button_text_color' => 'sometimes',
            'redirect_url' => 'sometimes',
            'redirect_mobile_url' => 'sometimes',
            'is_active' => 'sometimes',
            'type' => 'sometimes',
            'product_id' => 'sometimes|nullable',
        ]);

        if ($request->file('image')) {
            Storage::delete('marketingBoxImages/' . $banner->id . '/' . $banner->image);
            $imageName = $request->file('image')->hashName();
            $validatedate['image'] = $imageName;
            $request->file('image')->storeAs('public/marketingBoxImages/' . $banner->id, $imageName);
        }

        if ($request->city_ids) {
            $city_ids = explode(',', $validatedate['city_ids']);
            $cities = City::whereIn('id', $city_ids)->get();
            $banner->bannerCities()->sync($cities);
            unset($validatedate['city_ids']);
        }

        if ($request->category_ids) {
            $category_ids = explode(',', $validatedate['category_ids']);
            $categories = Category::whereIn('id', $category_ids)->get();
            $banner->bannerCategories()->sync($categories);
            unset($validatedate['category_ids']);
        }

        if ($request->type == 2 || $request->redirect_url != null) {
            $validatedate['redirect_mobile_url'] = '/ProductDetails';
        }

        if ($banner->update($validatedate)) {



            return response()->json(['massage:' => 'Marketing box has been updated successfully', 'data' => $banner], 200);
        } else {
            return response()->json(['message' => 'ERROR PLEASE TRY AGAIN LATER'], 500);
        }
    }


    public function deleteBanner(Banner $banner)
    {
        $id = $banner->id;

        if ($banner->delete()) {
            Storage::delete('marketingBoxImages/' . $id . '/' . $banner->image);
            return response()->json(['massage:' => 'Marketing box has been deleted successfully'], 200);
        } else {
            return response()->json(['message' => 'ERROR PLEASE TRY AGAIN LATER'], 500);
        }
    }
}
