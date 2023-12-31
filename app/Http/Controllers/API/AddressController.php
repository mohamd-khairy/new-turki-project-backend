<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\City;
use App\Models\Category;
use App\Models\Region;
use App\Services\Google_Map_API\GeocodingService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\BannerListResource;
class AddressController extends Controller
{

    // 1 => SA, 2 => UAE
    public function getCountries()
    {
        return response()->json(['success' => true, 'data' => Region::all(), 'message' => "success", 'description' => "", 'code' => "200"], 200);
    }

    // set current user location using goecoding
    public function getSelectedCustomerLocation(Request $request)
    {
        $data = $request->validate([
            'latitude' => 'required',
            'longitude' => 'required',
            'countryId' => 'required'
        ]);

        $gooMap = app(GeocodingService::class)->searchByCoordination($data['latitude'], $data['longitude']);

        return response()->json(['data' => null, 'message' => "success", 'description' => "", 'code' => "200"], 200);
    }
}
