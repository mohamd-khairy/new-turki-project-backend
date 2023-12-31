<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Country;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CityController extends Controller
{
    public function __construct()
    {
        if (env('PERMISSIONS', false)) {
            $this->middleware('permission:read-city', ['only' => ['getAll', 'getById']]);
            $this->middleware('permission:create-city', ['only' => ['add']]);
            $this->middleware('permission:update-city', ['only' => ['update']]);
            $this->middleware('permission:delete-city', ['only' => ['delete']]);
        }
    }
    /**
     * @param Request $request
     * @return City[]|Collection
     */
    public function getAll()
    {
        $data = City::with('country')->orderBy('id', 'desc')->get();

        return response()->json([
            'success' => 'true', 'data' => $data,
            'message' => 'Cities retrieved successfully', 'description' => 'list Of Cities', 'code' => '200'
        ], 200);
    }

    public function getById(City $city)
    {
        $data = $city->load('country');
        return response()->json([
            'success' => 'true', 'data' => $data,
            'message' => 'City retrieved successfully', 'description' => ' City Details', 'code' => '200'
        ], 200);
    }

    public function getActiveCities()
    {
        return response()->json([
            'success' => 'true', 'data' => City::with('country')->where('is_active', '1')->get(),
            'message' => 'Active Cities retrieved successfully', 'description' => ' List Of Active Cities', 'code' => '200'
        ], 200);
    }

    public function getCityByCountry(Country $country)
    {
        // dd($country->id);
        return response()->json([
            'success' => 'true', 'data' => City::with('country')->where('country_id', $country->id)->get(),
            'message' => 'City By Country retrieved successfully', 'description' => 'List Of City By Country', 'code' => '200'
        ], 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function add(Request $request)
    {
        $validateData = $request->validate([
            'country_id' => 'required|exists:countries,id',
            'name_ar' => 'required|max:150',
            'name_en' => 'required|max:150',
            'integrate_id' => 'sometimes|numeric',
            'is_available_for_delivery' => 'required',
            'polygon' => 'required' // need regex here
        ]);

        $polygonList = is_array($validateData['polygon']) ? $validateData['polygon'] : json_decode($validateData['polygon']);
        if ($polygonList) {
            $newPolygonList = [];

            // long lat format
            foreach ($polygonList as $polygon) {
                array_push($newPolygonList, $polygon[0] . " " . $polygon[1]);
            }

            $validateData['polygon'] = json_encode($newPolygonList);
        }

        $city = City::create($validateData);

        if ($city) {
            return response()->json([
                'success' => true, 'data' => $city,
                'message' => 'Successfully Added!', 'description' => ' Add City', 'code' => '200'
            ], 200);
        }

        return response()->json(['message' => 'Something went wrong!'], 500);
    }
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function delete(City $city)
    {
        if ($city->delete()) {

            return response()->json(['message' => 'Successfully Deleted!'], 200);
        }

        return response()->json(['message' => 'Something went wrong!'], 500);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function updateStatus(City  $city)
    {
        $city->is_active = !$city->is_active;
        if ($city->update()) {

            return response()->json([
                'success' => true, 'data' => $city,
                'message' => 'Successfully updated!', 'description' => ' Update Status City', 'code' => '200'
            ], 200);
        }
        return response()->json(['message' => 'Something went wrong!'], 500);
    }

    public function update(Request $request, City $city)
    {

        $validateData = $request->validate([
            'country_id' => 'sometimes|exists:countries,id',
            'name_ar' => 'sometimes|max:150',
            'name_en' => 'sometimes|max:150',
            'integrate_id' => 'sometimes|numeric',
            'is_available_for_delivery' => 'sometimes',
            'polygon' => 'sometimes' // need regex here
        ]);

        $polygonList = is_array($validateData['polygon']) ? $validateData['polygon'] : json_decode($validateData['polygon']);

        if ($polygonList) {
            $newPolygonList = [];

            // long lat format
            foreach ($polygonList as $polygon) {
                array_push($newPolygonList, $polygon[0] . " " . $polygon[1]);
            }

            $validateData['polygon'] = json_encode($newPolygonList);
        }

        if ($city->update($validateData)) {

            return response()->json([
                'success' => true, 'data' => $city->load('country'),
                'message' => 'Successfully updated!', 'description' => ' update City', 'code' => '200'
            ], 200);
        }
        return response()->json(['message' => 'Something went wrong!'], 500);
    }
}
