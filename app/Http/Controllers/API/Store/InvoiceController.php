<?php

namespace App\Http\Controllers\API\Store;

use App\Models\Bank;
use App\Models\Category;
use App\Models\Invoice;
use App\Models\MoneySafe;
use App\Models\Product;
use App\Models\Stock;
use App\Services\UploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends BaseController
{
    public $model = Invoice::class;

    public $with = ['user', 'supplier', 'stocks'];

    public function storeValidation()
    {
        return [
            'invoice' => 'required|image',
            'invoice_price' => 'required',
            'notes' => 'required',
            'date' => 'required|date',
            'user_id'  => 'required|exists:users,id',
            'supplier_id'  => 'required|exists:suppliers,id',
            'city_id' => 'required|exists:cities,id',
            'tax' => 'required|in:1,0',

            'stocks' => 'required|array',
            'stocks.*.product_id'  => 'required_without:stocks.*.product_name|exists:products,id',
            'stocks.*.product_name' => 'required_without:stocks.*.product_id',
            'stocks.*.quantity' => 'required|min:1',
            'stocks.*.price' => 'required|min:1',
            'stocks.*.store_id'  => 'required|exists:stores,id',
        ];
    }

    public function updateValidation()
    {
        return [
            'invoice' => 'nullable|image',
            'invoice_price' => 'required',
            'notes' => 'nullable',
            'date' => 'required|date',
            'user_id'  => 'required|exists:users,id',
            'supplier_id'  => 'required|exists:suppliers,id',
            'city_id' => 'required|exists:cities,id',
            'tax' => 'required|in:1,0',

            'stocks' => 'required|array',
            'stocks.*.product_id'  => 'required_without:stocks.*.product_name|exists:products,id',
            'stocks.*.product_name' => 'required_without:stocks.*.product_id',
            'stocks.*.quantity' => 'required|min:1',
            'stocks.*.price' => 'required|min:1',
            'stocks.*.store_id'  => 'required|exists:stores,id',
        ];
    }

    public function payInvoice(Request $request)
    {
        try {
            DB::beginTransaction();

            $request->validate([
                'invoice_id'  => 'required|exists:invoices,id',
                'bank_id'  => 'nullable|exists:banks,id',
                'money_safe_id'  => 'nullable|exists:money_safes,id',
            ]);

            $invoice = Invoice::find($request->invoice_id);

            $invoice->update(['paid' => 1]);

            $invoice->supplier()->update(['balance' => $invoice->supplier->balance - $invoice->invoice_price]);

            if ($request->bank_id) {
                $bank = Bank::find($request->bank_id);
                $bank->update(['balance' => $bank->balance - $invoice->invoice_price]);
            } else {

                if ($request->money_safe_id) {
                    $money_safe = MoneySafe::find($request->money_safe_id);
                    $money_safe->update(['balance' => $money_safe->balance - $invoice->invoice_price]);
                }
            }

            DB::commit();

            return successResponse($invoice->refresh());
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $data = $this->handleData($request, 'store');

            $invoice = $this->model::create($data);

            $this->Stocks($invoice, $request->stocks); //

            $this->createSupplierBalance($invoice);

            DB::commit();

            return successResponse($invoice->refresh());
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    protected function handleData($request, $type = 'store')
    {
        $data = $request->validate($type == 'store' ? $this->storeValidation() : $this->updateValidation());

        if ($request->tax) {
            $data['tax'] = round($request->invoice_price - $request->invoice_price / 1.15, 2);
        }

        if ($request->invoice) {
            $data['invoice'] = UploadService::store($request->invoice, 'invoices');;
        }

        unset($data['stocks']);

        return $data;
    }

    protected function createSupplierBalance($invoice)
    {
        $invoice->supplier()->update(['balance' => $invoice->supplier->balance + $invoice->invoice_price]);
    }

    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $data = $this->handleData($request, 'update'); //

            $invoice = $this->model::where('id', $id)->first(); //

            $this->Stocks($invoice, $request->stocks); //

            $this->updateSupplierBalance($invoice, $request->invoice_price); //

            $invoice->update($data); //

            DB::commit();

            return successResponse($invoice->refresh());
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    protected function Stocks($invoice, $stocks)
    {
        foreach ($stocks as $key => $stock) {
            $product = $this->createProduct($stock);


            $item = Stock::where('product_id',  $product->id)
                ->where('store_id', $stock['store_id'])->first();

            if (isset($item)) {

                $data = [
                    'price' => ($stock['price'] + $item->price) / 2,
                    'quantity' => $item->quantity + $stock['quantity'],
                ];

                $item->update($data);
            } else {

                $data = [
                    'product_id' => $product->id,
                    'product_name' => $product->name_ar,
                    'quantity' => $stock['quantity'],
                    'price' => $stock['price'],
                    'invoice_id' => $invoice->id,
                    'store_id' => $stock['store_id'],
                ];
                Stock::create($data);
            }
        }
    }

    protected function createProduct($stock)
    {
        if (isset($stock['product_id'])) {
            $product = Product::where('id', $stock['product_id'])->first();
        }

        if ($product) {
            return $product;
        } else {
            $product = Product::where(function ($query) use ($stock) {
                $query->where('name_ar', $stock['product_name'])
                    ->orWhere('name_en', $stock['product_name']);
            })->first();
            if (!$product) {
                $product = Product::create([
                    'description_en' => $stock['product_name'],
                    'description_ar' => $stock['product_name'],
                    'name_ar' => $stock['product_name'],
                    'name_en' => $stock['product_name'],
                    'price' => $stock['price'],
                    'sale_price' => $stock['price'],
                    'is_active' => 0,
                    'is_available' => 0,
                    'category_id' => Category::first('id')->id,
                ]);
            }
            return $product->refresh();
        }
    }

    protected function updateSupplierBalance($invoice, $new_invoice_price)
    {
        $invoice->supplier()->update(['balance' => (($invoice->supplier->balance - $invoice->invoice_price) + $new_invoice_price)]);
    }
}
