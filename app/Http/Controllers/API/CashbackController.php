<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Cashback;
use App\Models\Category;
use App\Models\City;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SubCategory;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CashbackController extends Controller
{
    /**
     * @param Request $request
     * @return City[]|Collection
     */
    public function index()
    {
        $cashbacks = Cashback::with('country')->orderBy('id', 'desc')->get();

        $cashbacks->each(function ($cashback) {
            $cashback->categories = Category::whereIn('id', $cashback->category_ids)->select('id', 'type_ar')->get();
            $cashback->sub_categories = SubCategory::whereIn('id', $cashback->sub_category_ids)->select('id', 'type_ar')->get();
            $cashback->customers = Customer::whereIn('id', $cashback->customer_ids)->select('id', 'name', 'mobile')->get();
            $cashback->products = Product::whereIn('id', $cashback->product_ids)->select('id', 'name_ar')->get();
            $cashback->cities = City::whereIn('id', $cashback->city_ids)->select('id', 'name_ar')->get();
        });


        return response()->json([
            'success' => 'true',
            'data' => $cashbacks,
            'message' => 'Cities retrieved successfully',
            'description' => 'list Of Cities',
            'code' => '200'
        ], 200);
    }

    public function show(Cashback $cashback)
    {
        $data = $cashback->load('country');
        return response()->json([
            'success' => 'true',
            'data' => $data,
            'message' => 'City retrieved successfully',
            'description' => ' City Details',
            'code' => '200'
        ], 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        $validateData = $request->validate([
            'cash_back_amount' => 'required|min:1|max:100',
            'cash_back_start_date' => 'required|after_or_equal:today',
            'cash_back_end_date' => 'required|after_or_equal:cash_back_start_date',
            'expired_days' => 'required|numeric',
            'is_active' => 'required|boolean',
            'country_id' => 'required',

            'category_ids' => 'nullable|array',
            'category_ids.*' => 'required|exists:categories,id',

            'sub_category_ids' => 'nullable|array',
            'sub_category_ids.*' => 'required|exists:sub_categories,id',

            'customer_ids' => 'nullable|array',
            'customer_ids.*' => 'required|exists:customers,id',

            'product_ids' => 'nullable|array',
            'product_ids.*' => 'required|exists:products,id',

            'city_ids' => 'nullable|array',
            'city_ids.*' => 'required|exists:cities,id',
        ]);

        if ($request->expired_days) {
            $validateData['expired_at'] = Carbon::parse($request->cash_back_end_date)->addDays($request->expired_days);
        }

        $data = Cashback::create($validateData);

        if ($data) {
            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Successfully Added!',
                'description' => ' Add cashback',
                'code' => '200'
            ], 200);
        }

        return response()->json(['message' => 'Something went wrong!'], 500);
    }
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function destroy(Cashback $cashback)
    {
        if ($cashback->delete()) {

            return response()->json(['message' => 'Successfully Deleted!'], 200);
        }

        return response()->json(['message' => 'Something went wrong!'], 500);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function updateStatus(Cashback  $cashback)
    {
        $cashback->is_active = !$cashback->is_active;
        if ($cashback->update()) {

            return response()->json([
                'success' => true,
                'data' => $cashback,
                'message' => 'Successfully updated!',
                'description' => ' Update Status Cashback',
                'code' => '200'
            ], 200);
        }
        return response()->json(['message' => 'Something went wrong!'], 500);
    }

    public function update(Request $request, Cashback $cashback)
    {

        $validateData = $request->validate([
            'cash_back_amount' => 'required|min:1|max:100',
            'cash_back_start_date' => 'required',
            'cash_back_end_date' => 'required|after_or_equal:cash_back_start_date',
            'is_active' => 'required|boolean',
            'country_id' => 'required',

            'category_ids' => 'nullable|array',
            'category_ids.*' => 'required|exists:categories,id',

            'sub_category_ids' => 'nullable|array',
            'sub_category_ids.*' => 'required|exists:sub_categories,id',

            'customer_ids' => 'nullable|array',
            'customer_ids.*' => 'required|exists:customers,id',

            'product_ids' => 'nullable|array',
            'product_ids.*' => 'required|exists:products,id',

            'city_ids' => 'nullable|array',
            'city_ids.*' => 'required|exists:cities,id',
        ]);

        if ($request->expired_days) {
            $validateData['expired_at'] = Carbon::parse($request->cash_back_end_date)->addDays($request->expired_days);
        }

        if ($cashback->update($validateData)) {

            return response()->json([
                'success' => true,
                'data' => $cashback->load('country'),
                'message' => 'Successfully updated!',
                'description' => ' update City',
                'code' => '200'
            ], 200);
        }
        return response()->json(['message' => 'Something went wrong!'], 500);
    }
}
