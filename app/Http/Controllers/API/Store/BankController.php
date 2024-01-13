<?php

namespace App\Http\Controllers\API\Store;

use App\Models\Bank;
use Illuminate\Http\Request;

class BankController extends BaseController
{
    public $model = Bank::class;

    public $with = ['city', 'user'];

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
