<?php

namespace App\Http\Controllers\API\Store;

use App\Models\Stock;
use App\Models\StockLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Expr\FuncCall;

class StockController extends BaseController
{
    public $model = Stock::class;

    public $search = ['product_name'];

    public $with = ['product', 'store', 'invoice.supplier', 'invoice.user'];

    public function storeValidation()
    {
        return [
            'product_name' => 'required',
            'quantity' => 'required',
            'price' => 'required',
            'product_id' => 'required|exists:products,id',
            'store_id' => 'required|exists:stores,id',
            'invoice_id' => 'required|exists:invoices,id',
        ];
    }

    public function updateValidation()
    {
        return [
            'product_name' => 'nullable',
            'quantity' => 'nullable',
            'price' => 'nullable',
            'product_id' => 'nullable|exists:products,id',
            'store_id' => 'nullable|exists:stores,id',
            'invoice_id' => 'required|exists:invoices,id',
        ];
    }

    public function transferStock(Request $request)
    {
        $request->validate([
            'stock_id' => 'required|exists:stocks,id',
            'transfer_quantity' => 'required',
            'store_id' => 'required|exists:stores,id',
            'to_store' => 'required|exists:stores,id',
        ]);

        $stock = Stock::where('id', $request->stock_id)
            ->where('store_id', $request->store_id)
            ->first();

        if (!$stock) {
            return failResponse([], 'المخزون غير موجود');
        }

        if ($stock && $stock->quantity < $request->transfer_quantity) {
            return failResponse([], 'لا يوجد كمية كافية');
        }


        $stock->update([
            'quantity' => ($stock->quantity - $request->transfer_quantity) ?? 0,
        ]);

        $to_stock = Stock::where(
            function ($q) use ($request, $stock) {
                $q->where('product_id', $stock->product_id)
                    ->orWhere('product_name', $stock->product_name);
            }
        )->where('store_id', $request->to_store)->first();

        if ($to_stock) {
            $to_stock->update([
                'quantity' => ($to_stock->quantity + $request->transfer_quantity) ?? 0,
            ]);
        } else {
            $to_stock = Stock::create([
                'product_id' => $stock->product_id,
                'store_id' => $request->to_store,
                'product_name' => $stock->product_name,
                'quantity' => $request->transfer_quantity,
                'price' => $stock->price,
                'invoice_id' => $stock->invoice_id,
            ]);
        }

        return successResponse($stock);
    }

    public function transferQuantity(Request $request)
    {
        $request->validate([
            'stock_id' => 'required|exists:stocks,id',
            'to_stock_id' => 'required|exists:stocks,id',
            'store_id' => 'nullable|exists:stores,id',
            'transfer_quantity' => 'required',
            'to_quantity' => 'required',
            'price' => 'required',
        ]);

        try {
            DB::beginTransaction();

            $stock = Stock::where('id', $request->stock_id)->first();

            $stock->update([
                'quantity' => ($stock->quantity - $request->transfer_quantity) ?? 0,
            ]);

            $to_stock = Stock::where('id', $request->to_stock_id)->first();
            if ($to_stock) {

                $to_stock->update([
                    'quantity' => ($to_stock->quantity + $request->to_quantity) ?? 0,
                ]);
            }

            DB::commit();

            return successResponse($to_stock);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    public function index()
    {
        $per_page = request('search') && !request('per_page') ? 100000 : request("per_page", 10);

        $items = $this->model::with($this->with ?? []);

        $items = $this->filter($items);

        $items = $items->orderBy('id', 'desc')->paginate($per_page ?? 10);

        return successResponse($items);
    }

    public function stockLogs()
    {
        $logs = StockLog::with('product', 'customer');

        $logs->when(request('search'), function ($q) {
            $q->whereHas('product', function ($q) {
                $q->where('name_ar', 'like', '%' . request('search') . '%')
                    ->orWhere('name_en', 'like', '%' . request('search') . '%');
            });
        });

        $logs->when(request('product_id'), function ($q) {
            $q->whereHas('product', function ($q) {
                $q->where('id', request('product_id'));
            });
        });


        $logs = $logs->orderBy('id', 'desc')
            ->paginate(request("per_page", 10));

        return successResponse($logs);
    }
}
