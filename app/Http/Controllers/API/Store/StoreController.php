<?php

namespace App\Http\Controllers\API\Store;

use App\Models\Store;
use Illuminate\Http\Request;

class StoreController extends BaseController
{
    public $model = Store::class;

    public $with = ['city', 'user'];

    public $search = ['name'];

    public function storeValidation()
    {
        return [
            'name' => 'required',
            'city_id' => 'required|exists:cities,id',
            'user_id'  => 'required|exists:users,id',
        ];
    }

    public function updateValidation()
    {
        return [
            'name' => 'nullable',
            'city_id' => 'nullable|exists:cities,id',
            'user_id'  => 'nullable|exists:users,id',
        ];
    }
}
