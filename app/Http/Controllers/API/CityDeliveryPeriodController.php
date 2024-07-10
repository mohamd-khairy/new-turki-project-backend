<?php

namespace App\Http\Controllers\API\Store;

use App\Models\Bank;
use App\Models\CityDay;
use App\Models\CityDeliveryPeriod;
use Illuminate\Http\Request;

class CityDeliveryPeriodController extends BaseController
{
    public $model = CityDeliveryPeriod::class;

    public $with = ['city', 'delivery_period'];

    public $search = [];

    public function storeValidation()
    {
        return [
            'delivery_period_id' => 'required|exists:delivery_periods,id',
            'city_id' => 'required|exists:cities,id',
        ];
    }

    public function updateValidation()
    {
        return [
            'delivery_period_id' => 'required|exists:delivery_periods,id',
            'city_id' => 'required|exists:cities,id',
        ];
    }
}
