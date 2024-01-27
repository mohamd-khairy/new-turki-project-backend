<?php

namespace App\Http\Controllers\API\Store;

use App\Models\MoneySafe;
use Illuminate\Http\Request;

class MoneySafeController extends BaseController
{
    public $model = MoneySafe::class;

    public $with = ['city', 'user'];

    public $search = ['name'];

    public function storeValidation()
    {
        return [
            'name' => 'required',
            'currency' => 'required',
            'balance' => 'required',
            'city_id' => 'required|exists:cities,id',
            'user_id'  => 'required|exists:users,id',
        ];
    }

    public function updateValidation()
    {
        return [
            'name' => 'nullable',
            'currency' => 'nullable',
            'balance' => 'nullable',
            'city_id' => 'nullable|exists:cities,id',
            'user_id'  => 'nullable|exists:users,id',
        ];
    }
}
