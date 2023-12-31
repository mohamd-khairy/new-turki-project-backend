<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Preparation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductPreparationController extends Controller
{
    /**
     * @param Request $request
     * @return Preparation[]|Collection
     */
    public function getAll()
    {
        $preparation = Preparation::all();
        return response()->json(['success' => 'true','data'=> $preparation,
        'message'=> 'Preparations retrieved successfully', 'description'=> 'list Of Preparations', 'code'=>'200'],200);
    }

    public function getById(Preparation $preparation)
    {
        return response()->json(['success' => 'true','data'=> $preparation,
        'message'=> 'Preparation retrieved successfully', 'description'=> 'Preparations Details', 'code'=>'200'],200);
    }

    public function getActiveProductPreparations()
    {
        return response()->json(['data' => Preparation::where('is_active', '1')->get(), 'message' => "success", 'description' => "", 'code' => "200"], 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function add(Request $request)
    {
        $validateDate = $request->validate([
            'price' => 'required|numeric',
            'name_ar' => 'required|max:255',
            'name_en' => 'required|max:255'
        ]);

        $productPreparation = Preparation::create($validateDate);
        if ($productPreparation) {
            
                return response()->json(['success' => 'true','data'=> $productPreparation,
                'message'=> 'Successfully Added!', 'description'=> 'Add Preparations', 'code'=>'200'],200);    
        }

        return response()->json(['message' => 'Something went wrong!'], 500);

    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function delete(Preparation $productPreparation)
    {
        if ($productPreparation->delete()) {

            return response()->json(['message' => 'Successfully Deleted!'], 200);
        }

        return response()->json(['message' => 'Something went wrong!'], 500);

    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function updateStatus(Preparation  $productPreparation)
    {
            $productPreparation->is_active = !$productPreparation->is_active;
            if ($productPreparation->update()) {
             
                return response()->json(['message' => 'Successfully updated!', 'data' => $productPreparation], 200);
            }
        return response()->json(['message' => 'Something went wrong!'], 500);

    }

    public function update(Request $request, Preparation $productPreparation)
    {

        $validateDate = $request->validate([
            'price' => 'nullable|numeric',
            'name_ar' => 'nullable|max:255',
            'name_en' => 'nullable|max:255'
        ]);

        if ($productPreparation->update($validateDate)) {

            return response()->json(['success' => true ,'data'=> $productPreparation,
            'message'=> 'Successfully updated!', 'description'=> 'Update Preparation', 'code'=> 200 ] ,200);
        }
        return response()->json(['message' => 'Something went wrong!'], 500);
    }

}
