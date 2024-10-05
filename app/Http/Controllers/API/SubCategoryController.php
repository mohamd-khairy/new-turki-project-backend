<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\APIResponse;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\City;
use App\Models\SubCategory;
use Illuminate\Http\Request;
use App\Http\Resources\SubcategoryListResource;

class SubCategoryController extends Controller
{
    protected $response;

    public function __construct()
    {
        $response = new APIResponse();

        if (env('PERMISSIONS', false)) {
            $this->middleware('permission:read-sub-category', ['only' => ['list']]);
            $this->middleware('permission:create-sub-category', ['only' => ['create']]);
            $this->middleware('permission:update-sub-category', ['only' => ['update']]);
            $this->middleware('permission:delete-sub-category', ['only' => ['delete']]);
        }
    }

    public function list(Request $request)
    {
        $data =  SubCategory::orderBy('id', 'desc')
            ->when(request('country_id'), function ($q) {
                $q->whereHas('subCategoryCities', function ($q) {
                    $q->where('country_id', request('country_id'));
                });
            })->get();

        $data = SubcategoryListResource::Collection($data);

        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => 'Sub-categories retrieved successfully',
            'description' => 'list Of Sub-categories',
            'code' => '200'
        ], 200);
    }

    public function getSubCategories(Request $request)
    {

        if (SubCategory::all()->isEmpty())
            return response()->json(['data' => []], 200);

        $data =  SubCategory::orderBy('id', 'desc')->get();
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => 'Categories retrieved successfully',
            'description' => 'list Of Categories',
            'code' => '200'
        ], 200);
    }

    public function listSubCategories(Category $category)
    {

        if (SubCategory::where('category_id', $category->id)->get()->isEmpty())
            return response()->json([], 200);


        $data = SubCategory::where('category_id', $category->id)->orderBy('sort', 'ASC')->get();

        $data = SubcategoryListResource::Collection($data);

        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => 'Sub-categories retrieved successfully',
            'description' => 'list Of Sub-categories',
            'code' => '200'
        ], 200);
    }

    public function getById(SubCategory $subCategory)
    {
        return response()->json([
            'data' => $subCategory,
            'message' => "success",
            'description' => "",
            'code' => "200"
        ], 200);
    }

    public function create(Request $request)
    {

        $validatedData = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'type_ar' => 'required|string',
            'type_en' => 'required|string',
            'city_ids' => array('nullable', 'regex:(^([-+] ?)?[0-9]+(,[0-9]+)*$)'),
            'description' => 'nullable'
        ]);

        $city_ids = explode(',', $validatedData['city_ids']);
        $cities = City::whereIn('id', $city_ids)->get();

        $validatedData['type_ar'] = strtolower($validatedData['type_ar']);
        $validatedData['type_en'] = strtolower($validatedData['type_en']);

        unset($validatedData['city_ids']);

        $hasCreated = SubCategory::create($validatedData);

        $hasCreated->subCategoryCities()->attach($cities);


        return response()->json([
            'success' => true,
            'data' => $hasCreated,
            'message' => 'Successfully Added!',
            'description' => 'Add sub-category',
            'code' => '200'
        ], 200);
    }

    public function update(Request $request, $subCategoryId)
    {
        if (!is_numeric($subCategoryId))
            return response()->json(['message' => 'id should be numeric', 'input' => $subCategoryId], 400);

        $validatedData = $request->validate([
            'category_id' => 'sometimes|exists:categories,id',
            'type_ar' => 'sometimes|string',
            'type_en' => 'sometimes|string',
            'city_ids' => array('sometimes', 'regex:(^([-+] ?)?[0-9]+(,[0-9]+)*$)'),
            'description' => 'nullable'
        ]);

        $subCategory = SubCategory::find($subCategoryId);
        if (is_null($subCategory))
            return response()->json([
                'data' => null,
                'message' => 'no subCategory found!',
                'description' => ''
            ], 404);

        $city_ids = explode(',', $validatedData['city_ids']);
        $cities = City::whereIn('id', $city_ids)->get();
        // unset($validatedData['city_ids']);

        $subCategory->subCategoryCities()->sync($cities);

        if (!$subCategory->update($validatedData))
            return response()->json([
                'message' => 'subCategory has not updated, contact support please',
                'input' => $subCategoryId
            ], 500);

        return response()->json([
            'success' => true,
            'data' => $subCategory,
            'message' => 'Successfully updated!',
            'description' => 'Update sub-category',
            'code' => 200
        ], 200);
    }

    public function delete($subCategoryId)
    {
        if (!is_numeric($subCategoryId))
            return response()->json(['message' => 'id should be numeric', 'input' => $subCategoryId], 400);

        $subCategory = SubCategory::find($subCategoryId);
        if (is_null($subCategory))
            return response()->json(['message' => 'no subCategory found!', 'input' => $subCategory], 404);

        if (!$subCategory->delete())
            return response()->json([
                'message' => 'subCategory has not deleted, contact support please',
                'input' => $subCategoryId
            ], 500);

        return response()->json(['message' => 'Successfully deleted!'], 200);
    }
}
