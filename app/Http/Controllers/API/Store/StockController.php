<?php

namespace App\Http\Controllers\API\Store;

use App\Models\Stock;
use App\Services\UploadService;
use Illuminate\Http\Request;

class StockController extends BaseController
{
    public $model = Stock::class;

    public $with = ['store', 'user','supplier' , 'product'];

    public function storeValidation()
    {
        return [
            'product_name' => 'required',
            'quantity' => 'required',
            'price' => 'required',
            'tax' => 'required',
            'invoice' => 'required|image',
            'invoice_price' => 'required',
            'price' => 'required',
            'notes' => 'required',
            'user_id'  => 'required|exists:users,id',
            'supplier_id'  => 'required|exists:suppliers,id',
            'store_id'  => 'required|exists:stores,id',
            'product_id'  => 'required|exists:products,id',
        ];
    }

    public function updateValidation()
    {
        return [
            'product_name' => 'nullable',
            'quantity' => 'nullable',
            'price' => 'nullable',
            'tax' => 'nullable',
            'invoice' => 'nullable|image',
            'invoice_price' => 'nullable',
            'price' => 'nullable',
            'notes' => 'nullable',
            'user_id'  => 'nullable|exists:users,id',
            'supplier_id'  => 'nullable|exists:suppliers,id',
            'store_id'  => 'nullable|exists:stores,id',
            'product_id'  => 'nullable|exists:products,id',
        ];
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->storeValidation());

        if ($request->invoice) {
            $data['invoice'] = UploadService::store($request->invoice, 'invoices');
        }
        $item = $this->model::create($data);

        return successResponse($item);
    }
}
