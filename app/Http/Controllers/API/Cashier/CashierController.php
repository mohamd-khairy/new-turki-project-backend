<?php

namespace App\Http\Controllers\API\Cashier;

use App\Http\Controllers\Controller;
use App\Http\Resources\Dashboard\CashierCategoryResource;
use App\Http\Resources\Dashboard\CashierProductResource;
use App\Http\Resources\Dashboard\CashierSubcategoryResource;
use App\Models\Category;
use App\Models\Product;
use App\Models\SubCategory;
use App\Models\User;
use Illuminate\Http\Request;

class CashierController extends Controller
{
    public function cashierCategories(Request $request)
    {
        $data =  Category::orderBy('id', 'desc')
            ->when(request('country_id'), function ($q) {
                $q->whereHas('categoryCities', function ($q) {
                    $q->where('country_id', request('country_id'));
                });
            })->get();

        return successResponse(CashierCategoryResource::collection($data), 'success');
    }

    public function cashierSubCategories($category_id, Request $request)
    {
        $data =  SubCategory::orderBy('id', 'desc')
            ->where('category_id', $category_id)
            ->when(request('country_id'), function ($q) {
                $q->whereHas('subCategoryCities', function ($q) {
                    $q->where('country_id', request('country_id'));
                });
            })->get();

        return successResponse(CashierSubcategoryResource::collection($data), 'success');
    }

    public function cashierProducts($subcategory_id, Request $request)
    {
        $data =  Product::where('sub_category_id', $subcategory_id)
            ->when(request('country_id'), function ($q) {
                $q->whereHas('productCities', function ($q) {
                    $q->where('country_id', request('country_id'));
                });
            })->get();

        return successResponse(CashierProductResource::collection($data), 'success');
    }
}
