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

class DeliveryDatePeriodController extends Controller
{
    /**
     * @param Request $request
     */
    public function getDeliveryDatePeriod()
    {
        $ddp = NotDeliveryDateCity::get();

        return response()->json(['success' => true ,'data'=> $ddp,
        'message'=> 'retrieved successfully', 'description'=> '', 'code'=>'200'],200);
    }

    public function getById($ddpId)
    {
        if(!is_numeric($ddpId))
            return response()->json(['message' => 'bad value!'], 400);

        $ddp = NotDeliveryDateCity::where([['id', $ddpId]])->get()->first();
         return response()->json(['success' =>  true ,'data'=> $ddp,
            'message'=> 'retrieved successfully', 'description'=> '', 'code'=>'200'],200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function addOrUpdate(Request $request)
    {
        $validateData = $request->validate([
            'city_id' => 'required|exists:cities,id',
          'date_yyyymmdd' => array('required', 'date'),
       //     'delivery_period_ids' => array('required', 'regex:(^([-+] ?)?[0-9]+(,[0-9]+)*$)')
        ]);

        $city = City::find($validateData['city_id']);
      dd($city);
        $delivery_period_ids = explode(',', $validateData['delivery_period_ids']);
        $delivery_periods = DeliveryPeriod::whereIn('id', $delivery_period_ids)->get();

        $dd = DeliveryDate::create([
            'city_id' => $city->id, 
            'date_yyyymmdd' => $validateData['date_yyyymmdd']]);

        $dd->deliveryDatePeriods()->attach($delivery_periods);

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
        return response()->json(['success' => true ,'data'=> $dd->with('deliveryDatePeriods'),
            'message'=> 'Successfully Added!', 'description'=> '', 'code'=> 200 ],200);

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
        foreach($validateData['citites'] as $city){
            $dd = NotDeliveryDateCity::create([
            'city_id' => $city['id'], 
            'delivery_date' => $validateData['date_yyyymmdd']
            ]);
            
            array_push($created, $dd);

            // $dd->deliveryDatePeriods()->attach($delivery_periods);
        }
        
        return response()->json(['success' => true ,'data'=> $created,
            'message'=> 'Successfully Added!', 'description'=> '', 'code'=> 200 ],200);

    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function delete($ddpId)
    {
        if(!is_numeric($ddpId))
            return response()->json(['message' => 'bad value!'], 400);

        NotDeliveryDateCity::find($ddpId)->delete();

            return response()->json(['message' => 'Successfully Deleted!'], 200);
    }
    
     public function update(Request $request, NotDeliveryDateCity $DateCity )
    {
    
      $validateData = $request->validate([
          
            'delivery_date' => array('required', 'date'),
            // 'delivery_period_ids' => array('required', 'regex:(^([-+] ?)?[0-9]+(,[0-9]+)*$)')
        ]);
   
       if ($DateCity->update($validateData)) {

            return response()->json(['success' => true ,'data'=> $DateCity,
            'message'=> 'Successfully updated!', 'description'=> 'Update Countries', 'code'=>'200'],200);

        }
        return response()->json(['message' => 'Something went wrong!'], 500);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function updateStatus($ddpId)
    {
        if(!is_numeric($ddpId))
            return response()->json(['message' => 'bad value!'], 400);

        $ddp = DeliveryDatePeriod::find($ddpId);
        $ddp->is_active = !$ddp->is_active;
        $ddp->update();

        return response()->json(['message' => 'Successfully updated!', 'data' => $ddp], 200);


    }
}
