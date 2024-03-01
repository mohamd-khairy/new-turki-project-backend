<?php

namespace App\Http\Controllers\API\Store;

use App\Models\Stock;
use App\Services\UploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockController extends BaseController
{
    public $model = Stock::class;

    public $with = ['product', 'store', 'invoice.supplier', 'invoice.user'];

    public function storeValidation()
    {
        return [
            'product_name' => 'required',
            'quantity' => 'required',
            'price' => 'required',
            'product_id'  => 'required|exists:products,id',
            'store_id'  => 'required|exists:stores,id',
            'invoice_id'  => 'required|exists:invoices,id',
        ];
    }

    public function updateValidation()
    {
        return [
            'product_name' => 'nullable',
            'quantity' => 'nullable',
            'price' => 'nullable',
            'product_id'  => 'nullable|exists:products,id',
            'store_id'  => 'nullable|exists:stores,id',
            'invoice_id'  => 'required|exists:invoices,id',
        ];
    }

    public function transferStock(Request $request)
    {
        $request->validate([
            'stock_id'  => 'required|exists:stocks,id',
            'to_store'  => 'required|exists:stores,id',
            'store_id'  => 'required|exists:stores,id',
        ]);

        $stock = Stock::where('id', $request->stock_id)->update([
            'store_id' => $request->to_store
        ]);

        return successResponse($stock);
    }

    public function transferQuantity(Request $request)
    {
        $request->validate([
            'stock_id'  => 'required|exists:stocks,id',
            'store_id'  => 'nullable|exists:stores,id',
            'transfer_quantity' => 'required',
            'to_quantity' => 'required',
            'price' => 'required'
        ]);

        try {
            DB::beginTransaction();

            $stock = Stock::where('id', $request->stock_id)->first();

            $stock->update([
                'quantity' => $stock->quantity - $request->transfer_quantity
            ]);

            $new_stock = Stock::create([
                'product_id' => $stock->product_id,
                'product_name' => $stock->product_name,
                'quantity'  => $request->to_quantity,
                'price' => $request->invoice_id,
                'invoice_id' => $stock->invoice_id,
                'store_id' => $request->store_id ?? $stock->store_id
            ]);

            DB::commit();

            return successResponse($new_stock);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }
}
