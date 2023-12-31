<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductTagController extends Controller
{
    public function index()
    {
        $tage = Tag::with('products')->get();

        return response()->json([
            'success' => true, 'data' => $tage,
            'message' => 'retrieved successfully', 'description' => '', 'code' => '200'
        ], 200);
    }

    public function show(Tag $tag)
    {
        return response()->json([
            'success' => true, 'data' => $tag,
            'message' => 'retrieved successfully', 'description' => '', 'code' => '200'
        ], 200);
    }

    public function store(Request $request)
    {
        $validateDate = $request->validate([
            'name_ar' => 'required|max:255',
            'name_en' => 'required|max:255',
            'color' => 'required|max:255',
            'product_ids' => 'required|array',
            'product_ids.*' => 'required|exists:products,id',
        ]);

        $productTag = Tag::create($validateDate);
        if ($productTag) {

            if ($request->product_ids) {
                $ids = is_array($request->product_ids) ? $request->product_ids : (json_decode($request->product_ids) ?? []);
                $products = Product::whereIn('id', $ids)->get();
                $productTag->products()->sync($products);
            }

            return response()->json([
                'success' => true, 'data' => $productTag,
                'message' => 'Successfully Added!', 'description' => '', 'code' => '200'
            ], 200);
        }
        return response()->json(['message' => 'Something went wrong!'], 500);
    }

    public function updateStatus(Tag $productTag)
    {
        $productTag->is_active = !$productTag->is_active;
        if ($productTag->update()) {
            return response()->json(['message' => 'Successfully updated!', 'data' => $productTag], 200);
        }
        return response()->json(['message' => 'Something went wrong!'], 500);
    }

    public function update(Request $request, Tag $tag)
    {
        $validateDate = $request->validate([
            'name_ar' => 'nullable|max:255',
            'name_en' => 'nullable|max:255',
            'color' => 'nullable|max:255',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'nullable|exists:products,id',
        ]);

        if ($tag->update($validateDate)) {

            if ($request->product_ids) {
                $ids = is_array($request->product_ids) ? $request->product_ids : (json_decode($request->product_ids) ?? []);
                $tag->products()->sync($ids);
            }
            return response()->json([
                'success' => true, 'data' => $tag,
                'message' => 'Successfully updated!', 'description' => '', 'code' => 200
            ], 200);
        }
        return response()->json(['message' => 'Something went wrong!'], 500);
    }

    public function destroy(Tag $tag)
    {
        if ($tag->delete()) {

            return response()->json(['message' => 'Successfully Deleted!'], 200);
        }

        return response()->json(['message' => 'Something went wrong!'], 500);
    }
}
