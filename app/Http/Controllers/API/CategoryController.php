<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Country;
use App\Models\CategoryCity;
use App\Models\City;
use Illuminate\Http\Request;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\CategoryAppListRecource;
use App\Http\Resources\CategoryAppListRecourceV2;
use App\Services\PointLocation;


class CategoryController extends Controller
{
    public function __construct()
    {
        if (env('PERMISSIONS', false)) {
            $this->middleware('permission:read-category', ['only' => ['listCategories', 'getById']]);
            $this->middleware('permission:create-category', ['only' => ['create']]);
            $this->middleware('permission:update-category', ['only' => ['update']]);
            $this->middleware('permission:delete-category', ['only' => ['delete']]);
        }
    }

    public function listCategories(Request $request)
    {
        $data =  CategoryResource::collection(Category::orderBy('id', 'desc')
            ->when(request('country_id'), function ($q) {
                $q->whereHas('categoryCities', function ($q) {
                        $q->where('country_id', request('country_id'));
                });
            })->get());
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => 'Categories retrieved successfully',
            'description' => 'list Of Categories',
            'code' => '200'
        ], 200);
    }

    public function listAppCategories(Request $request)
    {

        if (Category::all()->isEmpty())
            return response()->json(['data' => []], 200);

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
            $categoryIds = CategoryCity::where('city_id', $currentCity->id)->distinct()->pluck('category_id');
            $categories = Category::whereIn('id', $categoryIds)->orderBy('sort', 'ASC')->get();

            //   $categories =  Category::categoryCities()->whereIn('city_id', $currentCity->id)->distinct()->get();
        } else
            $categories = [];

        $data =  CategoryAppListRecource::collection($categories);
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => 'Categories retrieved successfully',
            'description' => 'list Of Categories',
            'code' => '200'
        ], 200);
    }

    public function listAppCategoriesV2(Request $request)
    {

        if (Category::all()->isEmpty())
            return response()->json(['data' => []], 200);

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

        $categoryIds = null;
        if ($currentCity != null) {
            $categoryIds = CategoryCity::where('city_id', $currentCity->id)->distinct()->get();

            $categories = Category::whereIn('id', $categoryIds->pluck('category_id'))->orderBy('sort', 'ASC')->get();
            foreach ($categoryIds as $categoryId) {
                foreach ($categories as $category) {
                    $category->active_temp = $category->id == $categoryId->category_id ?  $categoryId->active_temp : 1;
                }
            }
        } else
            $categories = [];

        $data =  CategoryAppListRecourceV2::collection($categories);
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => 'Categories retrieved successfully',
            'description' => 'list Of Categories',
            'code' => '200'
        ], 200);
    }

    public function getById(Category $category)
    {
        $data = $category;
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => 'Category retrieved successfully',
            'description' => 'Category Details',
            'code' => '200'
        ], 200);
    }

    public function create(Request $request)
    {
        $validatedData = $request->validate([
            'type_ar' => 'required|string',
            'type_en' => 'required|string',
            'description' => 'required|string',
            'color' => 'required|string',
            'backgroundColor' => 'required|string',
            'image' => 'required|mimes:png,jpg,jpeg|max:2048',
            'city_ids' => array('nullable', 'regex:(^([-+] ?)?[0-9]+(,[0-9]+)*$)')
        ]);

        if ($request->city_ids) {

            $city_ids = explode(',', $validatedData['city_ids']);
            $cities = City::whereIn('id', $city_ids)->get();

            $validatedData['type_en'] = strtolower($validatedData['type_en']);

            unset($validatedData['city_ids']);
        }

        $hasCreated = Category::create($validatedData);

        if ($cities) {
            $hasCreated->categoryCities()->attach($cities);
        }

        if ($request->image) {

            $hasUploaded = Category::uploadImage($request, $hasCreated, $validatedData);

            if (!$hasCreated->update($hasUploaded))
                return response()->json(['message' => 'Category has not created or image not uploaded,
             contact support please'], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $hasCreated,
            'message' => 'Successfully Added!',
            'description' => 'Add Category',
            'code' => '200'
        ], 200);
    }

    public function update(Request $request, $categoryId)
    {
        if (!is_numeric($categoryId))
            return response()->json(['message' => 'id should be numeric', 'input' => $categoryId], 400);

        $validatedData = $request->validate([
            'type_ar' => 'sometimes|string',
            'type_en' => 'sometimes|string',
            'description' => 'sometimes|string',
            'color' => 'sometimes|string',
            'backgroundColor' => 'sometimes|string',
            'image' => 'sometimes|mimes:png,jpg,jpeg|max:2048',
            'city_ids' => array('sometimes', 'regex:(^([-+] ?)?[0-9]+(,[0-9]+)*$)')
        ]);

        $category = Category::find($categoryId);

        if (is_null($category))
            return response()->json(['message' => 'no category found!', 'input' => $category], 404);

        if ($request->city_ids) {
            $city_ids = explode(',', $validatedData['city_ids']);
            $cities = City::whereIn('id', $city_ids)->get();

            $category->categoryCities()->sync($cities);
        }

        if (isset($validatedData['image'])) {
            $validatedData = Category::uploadImage($request, $category, $validatedData);
        }

        $category->update($validatedData);

        return response()->json([
            'success' => true,
            'data' => $category,
            'message' => 'Successfully updated!',
            'description' => 'Update Category',
            'code' => '200'
        ], 200);
    }

    public function updateSort(Request $request, $categoryId)
    {
        if (!is_numeric($categoryId))
            return response()->json(['message' => 'id should be numeric', 'input' => $categoryId], 400);

        $validatedData = $request->validate([
            'sort' => 'required|numeric',
        ]);

        $category = Category::find($categoryId);
        if (is_null($category))
            return response()->json(['message' => 'no category found!', 'input' => $category], 404);

        $category->update($validatedData);

        return response()->json([
            'success' => true,
            'data' => $category,
            'message' => 'Successfully Added!',
            'description' => 'Add Category',
            'code' => '200'
        ], 200);
    }

    public function delete($categoryId)
    {
        if (!is_numeric($categoryId))
            return response()->json(['message' => 'id should be numeric', 'input' => $categoryId], 400);

        $category = Category::find($categoryId);
        if (is_null($category))
            return response()->json(['message' => 'no category found!', 'input' => $category], 404);

        if (!$category->delete())
            return response()->json([
                'message' => 'category has not deleted, contact support please',
                'input' => $categoryId
            ], 500);

        return response()->json(['message' => 'Successfully Deleted!'], 200);
    }
}
