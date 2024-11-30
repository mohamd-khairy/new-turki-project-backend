<?php

namespace App\Http\Controllers\API\Cashier;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index()
    {
        $data = Branch::orderBy('id', 'desc')->get();

        return response()->json([
            'success' => 'true',
            'data' => $data,
            'message' => 'Branch retrieved successfully',
        ], 200);
    }

    public function activeBranches()
    {
        $data = Branch::where('is_active', 1)->orderBy('id', 'desc')->get();

        return response()->json([
            'success' => 'true',
            'data' => $data,
            'message' => 'Branch retrieved successfully',
        ], 200);
    }

    public function show(Branch $branch)
    {
        return response()->json([
            'success' => 'true',
            'data' => $branch,
            'message' => 'Branch retrieved successfully',
        ], 200);
    }

    public function store(Request $request)
    {
        $validateData = $request->validate([
            'is_active' => 'required',
            'name' => 'required', // need regex here
            'mobile' => 'required',
            'address' => 'required',
        ]);

        $branch = Branch::create($validateData);

        if ($branch) {
            return response()->json([
                'success' => true,
                'data' => $branch,
                'message' => 'Successfully Added!',
            ], 200);
        }

        return response()->json(['message' => 'Something went wrong!'], 400);
    }

    public function destroy(Branch $branch)
    {
        if ($branch->delete()) {

            return response()->json(['message' => 'Successfully Deleted!'], 200);
        }

        return response()->json(['message' => 'Something went wrong!'], 400);
    }

    public function updateStatus(Branch  $branch)
    {
        $branch->is_active = !$branch->is_active;
        if ($branch->update()) {

            return response()->json([
                'success' => true,
                'data' => $branch,
                'message' => 'Successfully updated!',
            ], 200);
        }
        return response()->json(['message' => 'Something went wrong!'], 400);
    }

    public function update(Request $request, Branch $branch)
    {
        $validateData = $request->validate([
           'is_active' => 'required',
            'name' => 'required',
            'mobile' => 'required',
            'address' => 'required',
        ]);

        if ($branch->update($validateData)) {

            return response()->json([
                'success' => true,
                'data' => $branch,
                'message' => 'Successfully updated!',
            ], 200);
        }
        return response()->json(['message' => 'Something went wrong!'], 400);
    }
}
