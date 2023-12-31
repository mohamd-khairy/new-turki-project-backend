<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\DeliveryDate;
use App\Models\DeliveryDatePeriod;
use App\Models\DeliveryPeriod;
use App\Models\NotDeliveryDateCity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotDeliveryDatePeriodController extends Controller
{

    public function __construct()
    {
        if (env('PERMISSIONS', false)) {
            $this->middleware('permission:read-not-delivery-date-city', ['only' => ['index', 'show']]);
            $this->middleware('permission:create-not-delivery-date-city', ['only' => ['store']]);
            $this->middleware('permission:update-not-delivery-date-city', ['only' => ['update']]);
            $this->middleware('permission:delete-not-delivery-date-city', ['only' => ['destroy']]);
        }
    }

    /**
     * @param Request $request
     */
    public function index()
    {
        $ddp = NotDeliveryDateCity::get();

        return successResponse($ddp);
    }

    public function show($ddpId)
    {
        $ddp = NotDeliveryDateCity::find($ddpId);

        return successResponse($ddp);
    }

    public function store(Request $request)
    {
        $validateData = $request->validate([
            'city_id' => 'required|exists:cities,id',
            'delivery_date' => array('required', 'date'),
            //'delivery_period_ids' => array('required', 'regex:(^([-+] ?)?[0-9]+(,[0-9]+)*$)')
        ]);

        $city = City::find($validateData['city_id']);
        if ($request->delivery_period_ids) {
            $delivery_period_ids = explode(',', $validateData['delivery_period_ids']);
            $delivery_periods = DeliveryPeriod::whereIn('id', $delivery_period_ids)->get();
        }
        $dd = NotDeliveryDateCity::create([
            'city_id' => $city->id,
            'delivery_date' => $validateData['delivery_date']
        ]);

        if ($request->delivery_period_ids) {
            $dd->deliveryDatePeriods()->attach($delivery_periods);
        }

        return successResponse($dd);

        //        foreach($delivery_dates as $delivery_date){
        //            foreach($delivery_periods as $delivery_period){
        //                array_push($create, [
        //                    "city_id" => $city->id,
        //                    'date_yyyymmdd' => $delivery_date,
        //                    'delivery_period_id' => $delivery_period->id
        //                ]);
        //            }
        //        }

        //        DeliveryDatePeriod::where('city_id', $city->id)->delete();
        //        $created = DeliveryDatePeriod::insert($create);

    }

    public function update(Request $request, $DateCity)
    {

        $DateCity = NotDeliveryDateCity::find($DateCity);
        $validateData = $request->validate([
            'city_id' => 'nullable',
            'delivery_date' => array('required', 'date'),
            // 'delivery_period_ids' => array('required', 'regex:(^([-+] ?)?[0-9]+(,[0-9]+)*$)')
        ]);

        if ($DateCity->update($validateData)) {

            return response()->json([
                'success' => true, 'data' => $DateCity,
                'message' => 'Successfully updated!', 'description' => 'Update Countries', 'code' => '200'
            ], 200);
        }
        return response()->json(['message' => 'Something went wrong!'], 500);
    }

    public function destroy($ddpId)
    {
        if (!is_numeric($ddpId))
            return response()->json(['message' => 'bad value!'], 400);

        NotDeliveryDateCity::find($ddpId)->delete();

        return response()->json(['message' => 'Successfully Deleted!'], 200);
    }

    public function addCityDateBulk(Request $request)
    {

        $validateData = $request->validate([
            'citites' => array('array'),
            'citites.*.id' => array('required', 'exists:cities,id'),
            'date_yyyymmdd' => array('required', 'date'),
            // 'delivery_period_ids' => array('required', 'regex:(^([-+] ?)?[0-9]+(,[0-9]+)*$)')
        ]);


        // $delivery_periods = DeliveryPeriod::whereIn('id', $delivery_period_ids)->get();
        $created = [];
        foreach ($validateData['citites'] as $city) {
            $dd = NotDeliveryDateCity::create([
                'city_id' => $city['id'],
                'delivery_date' => $validateData['date_yyyymmdd']
            ]);

            array_push($created, $dd);

            // $dd->deliveryDatePeriods()->attach($delivery_periods);
        }

        return response()->json([
            'success' => true, 'data' => $created,
            'message' => 'Successfully Added!', 'description' => '', 'code' => 200
        ], 200);
    }

    public function updateStatus($ddpId)
    {
        if (!is_numeric($ddpId))
            return response()->json(['message' => 'bad value!'], 400);

        $ddp = DeliveryDatePeriod::find($ddpId);
        $ddp->is_active = !$ddp->is_active;
        $ddp->update();

        return response()->json(['message' => 'Successfully updated!', 'data' => $ddp], 200);
    }
}
