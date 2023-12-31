<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Promotion;
class PromotionController extends Controller
{

    public function getPromotionById($promotionId)
    {
        return response()->json(['success' => true, 'message' => '', 'description' => "", "code" => "200",
            "data" => Promotion::where([['is_active', 1],['id', $promotionId]])->get()], 200);
    }

    public function getPromotions(Request $request)
    {
         return response()->json(['success' => true, 'message' => '', 'description' => "", "code" => "200",
            "data" => Promotion::where('is_active', '1')->get()], 200);
    }
}
