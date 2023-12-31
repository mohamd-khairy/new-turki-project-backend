<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Models\Country;
use App\Models\MinOrder;

class MinOrderController extends Controller
{
    public function getAll()
    {
        $minOrder = minOrder::all();

        return response()->json(['success' => true ,'data'=> $minOrder,
        'message'=> 'retrieved successfully', 'description'=> '', 'code'=>'200'],200);
    }

    public function getById(minOrder $minOrder)
    {
        return response()->json(['success' => true ,'data'=> $minOrder,
        'message'=> 'retrieved successfully', 'description'=> '', 'code'=>'200'],200);
    }

    public function add(Request $request)
    {
        $validateData = $request->validate([
            'country_id' => 'required|exists:countries,id',
            'min_order' => 'required|max:255',
        ]);

        $minOrder = minOrder::create($validateData);
        if ($minOrder) {

           return response()->json(['success' => true ,'data'=> $minOrder,
            'message'=> 'Successfully Added!', 'description'=> '', 'code'=>'200'],200);
        }
        return response()->json(['message' => 'Something went wrong!'], 500);
    }

    public function update(Request $request, minOrder $minOrder)
    {
        $validateDate = $request->validate([
            'country_id' => 'sometimes|exists:countries,id',
            'min_order' => 'sometimes|max:255',
        ]);

        if ($minOrder->update($validateDate)) {

            return response()->json(['success' => true ,'data'=> $minOrder,
            'message'=> 'Successfully updated!', 'description'=> '', 'code'=> 200 ],200);
           
        }
        return response()->json(['message' => 'Something went wrong!'], 500);
    }

    public function delete(minOrder $minOrder)
    {
        if ($minOrder->delete()) {
            
            return response()->json(['message' => 'Successfully Deleted!'], 200);
        }

        return response()->json(['message' => 'Something went wrong!'], 500);

    }

}
