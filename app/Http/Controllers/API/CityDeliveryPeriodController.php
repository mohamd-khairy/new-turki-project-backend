<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\Store\BaseController;
use App\Models\Bank;
use App\Models\City;
use App\Models\CityDay;
use App\Models\CityDeliveryPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CityDeliveryPeriodController extends BaseController
{
    public $model = CityDeliveryPeriod::class;

    public $with = ['city', 'delivery_period'];

    public $search = [];

    public function storeValidation()
    {
        return [
            'delivery_period_id' => 'required',
            'city_id' => 'required|exists:cities,id',
        ];
    }

    public function updateValidation()
    {
        return [
            'delivery_period_id' => 'required',
            'city_id' => 'required|exists:cities,id',
        ];
    }


    public function index()
    {
        $items = City::query()
            ->select(
                'cities.id',
                'cities.name_ar',
                'city_delivery_periods.city_id',
                DB::raw('GROUP_CONCAT(delivery_periods.name_ar) as periods'),
                DB::raw('GROUP_CONCAT(delivery_periods.id) as period_ids'),
            )
            ->join('city_delivery_periods', 'cities.id', '=', 'city_delivery_periods.city_id')
            ->join('delivery_periods', 'delivery_periods.id', '=', 'city_delivery_periods.delivery_period_id')
            ->orderBy('id', 'desc')
            ->groupBy('cities.id')
            ->paginate(request("per_page", 1000000));

        return successResponse($items);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->storeValidation());
        $data['delivery_period_id'] = is_array($data['delivery_period_id']) ? $data['delivery_period_id'] : json_decode($data['delivery_period_id']);

        foreach ($data['delivery_period_id'] as $key => $value) {
            $data['delivery_period_id'] = $value;
            $item = $this->model::create($data);
        }

        return successResponse($item);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate($this->updateValidation());
        $data['delivery_period_id'] = is_array($data['delivery_period_id']) ? $data['delivery_period_id'] : json_decode($data['delivery_period_id']);

        CityDeliveryPeriod::where('city_id', $id)->delete();

        foreach ($data['delivery_period_id'] as $key => $value) {
            $data['delivery_period_id'] = $value;
            $item = $this->model::create($data);
        }

        return successResponse($item);
    }

    public function destroy($id)
    {
        $item = CityDeliveryPeriod::where('city_id', $id)->delete();

        return successResponse($item);
    }
}
