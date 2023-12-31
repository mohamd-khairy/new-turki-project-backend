<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Country;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CountryController extends Controller
{
    public function __construct()
    {
        if (env('PERMISSIONS', false)) {
            $this->middleware('permission:read-country', ['only' => ['getAll', 'getById']]);
            $this->middleware('permission:create-country', ['only' => ['create']]);
            $this->middleware('permission:update-country', ['only' => ['update']]);
            $this->middleware('permission:delete-country', ['only' => ['delete']]);
        }
    }
    /**
     * @param Request $request
     * @return City[]|Collection
     */
    public function getAll()
    {
        $data = Country::orderBy('id', 'desc')->get();
        return response()->json([
            'success' => true, 'data' => $data,
            'message' => 'Countries retrieved successfully', 'description' => 'list Of Countries', 'code' => '200'
        ], 200);
    }

    public function getById(Country $country)
    {
        $data = $country;
        return response()->json([
            'success' => true, 'data' => $data,
            'message' => 'Country retrieved successfully', 'description' => ' Country Details', 'code' => '200'
        ], 200);
    }

    public function getActiveCountries()
    {
        $ActiveCountries = Country::where('is_active', '1')->get();

        return response()->json([
            'success' => true, 'data' => $ActiveCountries,
            'message' => 'Active Countries retrieved successfully', 'description' => ' List Of Active Countries', 'code' => '200'
        ], 200);
    }

    public function getCountryByCity(City $city)
    {
        $CountryByCity = Country::find($city->country_id);

        return response()->json(['data' => $CountryByCity, 'message' => "success", 'description' => "", 'code' => "200"], 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function create(Request $request)
    {
        $validateDate = $request->validate([
            'name_ar' => 'required|max:150',
            'name_en' => 'required|max:150',
            'currency_ar' => 'required|max:150',
            'currency_en' => 'required|max:150',
            'phone_code' => 'required|max:4',
            'latitude' => 'required',
            'longitude' => 'required',
            'code' => 'required'
        ]);

        $country = Country::create($validateDate);

        if ($country) {
            return response()->json([
                'success' => true, 'data' => $country,
                'message' => 'Successfully Added!', 'description' => 'Add Countries', 'code' => '200'
            ], 200);
        }
        return response()->json(['message' => 'Something went wrong!'], 500);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function delete(Country $country)
    {
        if ($country->delete()) {

            return response()->json(['message' => 'Successfully Deleted!'], 200);
        }

        return response()->json(['message' => 'Something went wrong!'], 500);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function updateStatus(Country $country)
    {
        $country->is_active = !$country->is_active;
        if ($country->update()) {

            return response()->json([
                'success' => true, 'data' => $country,
                'message' => 'Successfully updated!', 'description' => 'Update Status Countries', 'code' => '200'
            ], 200);
        }
        return response()->json(['message' => 'Something went wrong!'], 500);
    }

    public function update(Request $request, Country $country)
    {

        $validateDate = $request->validate([
            'name_ar' => 'required|max:150',
            'name_en' => 'required|max:150',
            'currency_ar' => 'required|max:150',
            'currency_en' => 'required|max:150',
            'phone_code' => 'required|max:4',
            'latitude' => 'required',
            'longitude' => 'required',
            'code' => 'required'
        ]);

        if ($country->update($validateDate)) {

            return response()->json([
                'success' => true, 'data' => $country,
                'message' => 'Successfully updated!', 'description' => 'Update Countries', 'code' => '200'
            ], 200);
        }
        return response()->json(['message' => 'Something went wrong!'], 500);
    }
}
