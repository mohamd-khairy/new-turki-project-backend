<?php

namespace App\Http\Controllers\API\Store;

use App\Models\Bank;
use App\Models\CityDay;
use Illuminate\Http\Request;

class CityDayController extends BaseController
{
    public $model = CityDay::class;

    public $with = ['city'];

    public $search = ['day'];

    public function storeValidation()
    {
        return [
            'day' => 'required',
            'city_id' => 'required|exists:cities,id',
        ];
    }

    public function updateValidation()
    {
        return [
            'day' => 'required',
            'city_id' => 'required|exists:cities,id',
        ];
    }
}
