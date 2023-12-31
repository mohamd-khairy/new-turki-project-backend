<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Shalwata;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductShlwataController extends Controller
{
    /**
     * @param Request $request
     * @return Shlwata[]|Collection
     */
    public function getAll()
    {
        $productShlwata = Shalwata::all();
        return response()->json(['success' => true ,'data'=> $productShlwata,
        'message'=> 'Shlwata retrieved successfully', 'description'=> 'List Of Shlwata', 'code'=>'200'],200);
    }

    public function getById(Shalwata $shlwata)
    {
        return response()->json(['data' => $shlwata, 'message' => "success",
            'description' => "", 'code' => "200"], 200);
    }

    public function getActiveProductShlwatas()
    {
        return response()->json(['data' => Shalwata::where('is_active', '1')->get(), 'message' => "success", 'description' => "", 'code' => "200"], 200);
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
        ]);

        $productShlwata = Shalwata::create($validateDate);
        if ($productShlwata) {

            return response()->json(['success' => true ,'data'=> $productShlwata,
                'message'=> 'Successfully Added!', 'description'=> 'Add Shlwata', 'code'=> 200 ],200);
        }

        return response()->json(['message' => 'Something went wrong!'], 500);

    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function delete(Shalwata $productShlwata)
    {
        if ($productShlwata->delete()) {

            return response()->json(['message' => 'Successfully Deleted!'], 200);
        }

        return response()->json(['message' => 'Something went wrong!'], 500);

    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function updateStatus(Shalwata  $productShlwata)
    {
            $productShlwata->is_active = !$productShlwata->is_active;
            if ($productShlwata->update()) {
                return response()->json(['message' => 'Successfully updated!', 'data' => $productShlwata], 200);
            }
        return response()->json(['message' => 'Something went wrong!'], 500);

    }

    public function update(Request $request, Shalwata $productShlwata)
    {

        $validateDate = $request->validate([
            'price' => 'required|numeric',
            'name_ar' => 'required|max:255',
            'name_en' => 'required|max:255'
        ]);

        if ($productShlwata->update($validateDate)) {

            return response()->json(['success' => true ,'data'=> $productShlwata,
            'message'=> 'Successfully updated!', 'description'=> 'Update Shlwata', 'code'=> 200 ],200);

        }
        return response()->json(['message' => 'Something went wrong!'], 500);
    }

}
