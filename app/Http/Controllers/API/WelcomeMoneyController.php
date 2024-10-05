<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\WelcomeMoney;
use App\Rules\DateRangeNotOverlap;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WelcomeMoneyController extends Controller
{
    /**
     * @param Request $request
     */
    public function index()
    {
        $welcomes = WelcomeMoney::with('country')->orderBy('id', 'desc')->get();

        return response()->json([
            'success' => 'true',
            'data' => $welcomes,
            'message' => 'welcomes retrieved successfully',
            'description' => 'list Of welcomes',
            'code' => '200'
        ], 200);
    }

    public function show(WelcomeMoney $welcome)
    {
        $data = $welcome->load('country');
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
            'welcome_amount' => 'required|min:1|max:100',
            'welcome_start_date' => 'required|after_or_equal:today',
            'welcome_end_date' => ['required', 'after_or_equal:welcome_start_date', new DateRangeNotOverlap($request->welcome_start_date, $request->welcome_end_date)],
            'expired_days' => 'required|numeric',
            'is_active' => 'required|boolean',
            'country_id' => 'required',

        ]);

        if ($request->expired_days) {
            $validateData['expired_at'] = Carbon::parse($request->welcome_end_date)->addDays($request->expired_days);
        }

        $data = WelcomeMoney::create($validateData);

        if ($data) {
            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Successfully Added!',
                'description' => ' Add WelcomeMoney',
                'code' => '200'
            ], 200);
        }

        return response()->json(['message' => 'Something went wrong!'], 500);
    }
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function destroy(WelcomeMoney $welcome)
    {
        if ($welcome->delete()) {

            return response()->json(['message' => 'Successfully Deleted!'], 200);
        }

        return response()->json(['message' => 'Something went wrong!'], 500);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function updateStatus(WelcomeMoney  $welcome)
    {
        $welcome->is_active = !$welcome->is_active;
        if ($welcome->update()) {

            return response()->json([
                'success' => true,
                'data' => $welcome,
                'message' => 'Successfully updated!',
                'description' => ' Update Status WelcomeMoney',
                'code' => '200'
            ], 200);
        }
        return response()->json(['message' => 'Something went wrong!'], 500);
    }

    public function update(Request $request, WelcomeMoney $welcome)
    {
        $validateData = $request->validate([
            'welcome_amount' => 'required|min:1|max:100',
            'welcome_start_date' => 'required|after_or_equal:today',
            'welcome_end_date' => ['required', 'after_or_equal:welcome_start_date', new DateRangeNotOverlap($request->welcome_start_date, $request->welcome_end_date)],
            'expired_days' => 'required|numeric',
            'is_active' => 'required|boolean',
            'country_id' => 'required',

        ]);

        if ($request->expired_days) {
            $validateData['expired_at'] = Carbon::parse($request->welcome_end_date)->addDays($request->expired_days);
        }

        if ($welcome->update($validateData)) {

            return response()->json([
                'success' => true,
                'data' => $welcome->load('country'),
                'message' => 'Successfully updated!',
                'description' => ' update City',
                'code' => '200'
            ], 200);
        }
        return response()->json(['message' => 'Something went wrong!'], 500);
    }
}
