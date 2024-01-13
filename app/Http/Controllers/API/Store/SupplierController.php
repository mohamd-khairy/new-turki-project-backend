<?php

namespace App\Http\Controllers\API\Store;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends BaseController
{
    public $model = Supplier::class;

    public $with = ['city'];

    public function storeValidation()
    {
        return [
            'name' => 'required',
            'mobile' => 'required',
            'balance' => 'required',
            'city_id' => 'required|exists:cities,id',
        ];
    }

    public function updateValidation()
    {
        return [
            'name' => 'nullable',
            'mobile' => 'nullable',
            'balance' => 'nullable',
            'city_id' => 'nullable|exists:cities,id',
        ];
    }
}
