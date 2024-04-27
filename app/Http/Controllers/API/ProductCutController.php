<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Cut;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductCutController extends Controller
{
    /**
     * @param Request $request
     * @return Cut[]|Collection
     */
    public function getAll()
    {
        $cuts = Cut::all();
        return response()->json([
            'success' => true, 'data' => $cuts,
            'message' => 'Cuts retrieved successfully', 'description' => 'list Of Cuts', 'code' => '200'
        ], 200);
    }

    public function getById(Cut $cut)
    {
        return response()->json([
            'success' =>  true, 'data' => $cut,
            'message' => 'Cut retrieved successfully', 'description' => 'Cut Details', 'code' => '200'
        ], 200);
    }

    public function getActiveProductCuts()
    {
        return response()->json(['data' => Cut::where('is_active', '1')->get(), 'message' => "success", 'description' => "", 'code' => "200"], 200);
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
            'price' => 'required|numeric',
            'foodics_integrate_id' => 'nullable',
        ]);

        $productCut = Cut::create($validateDate);
        if ($productCut) {

            return response()->json([
                'success' => true, 'data' => $productCut,
                'message' => 'Successfully Added!', 'description' => 'Add Cut', 'code' => 200
            ], 200);
        }

        return response()->json(['message' => 'Something went wrong!'], 500);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function delete(Cut $productCut)
    {
        if ($productCut->delete()) {

            return response()->json(['message' => 'Successfully Deleted!'], 200);
        }

        return response()->json(['message' => 'Something went wrong!'], 500);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function updateStatus(Cut $productCut)
    {
        $productCut->is_active = !$productCut->is_active;
        if ($productCut->update()) {
            return response()->json(['message' => 'Successfully updated!', 'data' => $productCut], 200);
        }
        return response()->json(['message' => 'Something went wrong!'], 500);
    }

    public function update(Request $request, Cut $productCut)
    {

        $validateDate = $request->validate([
            'price' => 'nullable|numeric',
            'name_ar' => 'nullable|max:255',
            'name_en' => 'nullable|max:255',
            'foodics_integrate_id' => 'nullable',
        ]);

        if ($productCut->update($validateDate)) {

            return response()->json([
                'success' => true, 'data' => $productCut,
                'message' => 'Successfully updated!', 'description' => 'Update Cut', 'code' => 200
            ], 200);
        }
        return response()->json(['message' => 'Something went wrong!'], 500);
    }
}
