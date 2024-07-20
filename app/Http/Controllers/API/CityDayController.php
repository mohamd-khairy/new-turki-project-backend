<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\Store\BaseController;
use App\Models\Bank;
use App\Models\City;
use App\Models\CityDay;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    public function index()
    {
        $items = City::query()
            ->select('cities.id', 'cities.name_ar', 'city_days.city_id', DB::raw('GROUP_CONCAT(city_days.day) as day'))
            ->join('city_days', 'cities.id', '=', 'city_days.city_id')
            ->orderBy('id', 'desc')
            ->groupBy('cities.id')
            ->paginate(request("per_page", 1000000));

        return successResponse($items);
    }


    public function store(Request $request)
    {
        $data = $request->validate($this->storeValidation());
        $data['day'] = is_array($data['day']) ? $data['day'] : json_decode($data['day']);

        foreach ($data['day'] as $key => $value) {
            $data['day'] = $value;
            $item = $this->model::create($data);
        }

        return successResponse($item);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate($this->updateValidation());
        $data['day'] = is_array($data['day']) ? $data['day'] : json_decode($data['day']);

        CityDay::where('city_id', $id)->delete();

        foreach ($data['day'] as $key => $value) {
            $data['day'] = $value;
            $item = $this->model::create($data);
        }

        return successResponse($item);
    }

    public function destroy($id)
    {
        $item = CityDay::where('city_id', $id)->delete();

        return successResponse($item);
    }
}
