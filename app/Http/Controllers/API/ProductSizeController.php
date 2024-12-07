<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Size;
use App\Models\SizeStore;
use App\Models\Stock;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductSizeController extends Controller
{
    /**
     * @param Request $request
     * @return Size[]|Collection
     */
    public function getAll()
    {
        if (request('pageSize')) {
            $size = Size::with('stores')->where('use_again', true)->get();
        } else {
            $size = Size::with('stores')->get();
        }

        return response()->json([
            'success' => true,
            'data' => $size,
            'message' => 'Sizes retrieved successfully',
            'description' => 'list Of Sizes',
            'code' => '200'
        ], 200);
    }

    public function getById(Size $size)
    {
        return response()->json([
            'success' => true,
            'data' => $size->load('stores'),
            'message' => 'Size retrieved successfully',
            'description' => 'Size Details',
            'code' => '200'
        ], 200);
    }

    public function getActiveProductSizes()
    {
        return response()->json([
            'success' => true,
            'data' => Size::where('is_active', '1')->get(),
            'message' => 'Active Product Sizes retrieved successfully',
            'description' => 'List Of Active Product Sizes',
            'code' => 200
        ], 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function add(Request $request)
    {
        $validateDate = $request->validate([
            'name_ar' => 'required|max:255',
            'name_en' => 'required|max:255',
            'weight' => 'sometimes|max:255',
            // 'calories' => 'sometimes|max:255',
            'price' => 'required|numeric',
            'sale_price' => 'required|numeric',
            'is_available_for_use' => 'required|boolean',
            'foodics_integrate_id' => 'nullable',

            'stores' => 'nullable',
            'stores.*.store_id' => 'required|exists:stores,id',
            'stores.*.product_id' => 'nullable|exists:products,id',
            'stores.*.quantity' => 'required',
        ]);

        $validateDate['use_again'] = request('is_available_for_use') ?? false;
        $productSize = Size::create($validateDate);
        if ($productSize) {

            if ($request->stores) {

                $data = $request->stores;
                foreach ($request->stores as $key => $store) {

                    SizeStore::updateOrCreate([
                        'size_id' => $productSize->id,
                        'product_id' => $store['product_id'] ?? null,
                        'store_id' => $store['store_id'] ?? null,
                        'quantity' => $store['quantity'],
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'data' => $productSize,
                'message' => 'Successfully Added!',
                'description' => 'Add Size',
                'code' => '200'
            ], 200);
        }

        return response()->json(['message' => 'Something went wrong!'], 500);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function delete(Size $productSize)
    {
        if ($productSize->delete()) {

            return response()->json(['message' => 'Successfully Deleted!'], 200);
        }

        return response()->json(['message' => 'Something went wrong!'], 500);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function updateStatus(Size  $productSize)
    {
        $productSize->is_active = !$productSize->is_active;
        if ($productSize->update()) {
            return response()->json(['message' => 'Successfully updated!', 'data' => $productSize], 200);
        }
        return response()->json(['message' => 'Something went wrong!'], 500);
    }

    public function update(Request $request, Size $productSize)
    {
        $validateDate = $request->validate([
            'price' => 'required|numeric',
            'name_ar' => 'sometimes|max:255',
            'name_en' => 'sometimes|max:255',
            'weight' => 'sometimes',
            'sale_price' => 'required|numeric',
            'use_again' => 'nullable|boolean',
            'foodics_integrate_id' => 'nullable',

            'stores' => 'nullable',
            'stores.*.store_id' => 'required|exists:stores,id',
            'stores.*.product_id' => 'nullable|exists:products,id',
            'stores.*.quantity' => 'required',
        ]);

        if ($productSize->update($validateDate)) {

            if ($request->stores) {
                SizeStore::where(['size_id' => $productSize->id])->delete();
                foreach ($request->stores as $key => $store) {

                    SizeStore::create([
                        'size_id' => $productSize->id,
                        'product_id' => $store['product_id'] ?? null,
                        'store_id' => $store['store_id'] ?? null,
                        'quantity' => $store['quantity'],
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'data' => $productSize,
                'message' => 'Successfully updated!',
                'description' => 'Update Size',
                'code' => 200
            ], 200);
        }
        return response()->json(['message' => 'Something went wrong!'], 500);
    }
}
