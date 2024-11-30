<?php

namespace App\Http\Controllers\API\Cashier;

use App\Http\Controllers\Controller;
use App\Http\Resources\Dashboard\CashierCategoryResource;
use App\Http\Resources\Dashboard\CashierProductResource;
use App\Http\Resources\Dashboard\CashierSubcategoryResource;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Payment;
use App\Models\PaymentType;
use App\Models\Product;
use App\Models\Size;
use App\Models\SubCategory;
use App\Models\WalletLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashierController extends Controller
{
    public function cashierCategories(Request $request)
    {
        $data =  Category::orderBy('id', 'desc')
            ->when(request('country_id', $this->getAuthCountryCode()), function ($q) use ($request) {
                $q->whereHas('categoryCities', function ($q) {
                    $q->where('country_id', request('country_id', $this->getAuthCountryCode()));
                });
            })->get();

        return successResponse(CashierCategoryResource::collection($data), 'success');
    }

    public function cashierSubCategories($category_id, Request $request)
    {
        $data =  SubCategory::orderBy('id', 'desc')
            ->where('category_id', $category_id)
            ->when(request('country_id', $this->getAuthCountryCode()), function ($q) use ($request) {
                $q->whereHas('subCategoryCities', function ($q) {
                    $q->where('country_id', request('country_id', $this->getAuthCountryCode()));
                });
            })->get();

        return successResponse(CashierSubcategoryResource::collection($data), 'success');
    }

    public function cashierProducts($subcategory_id, Request $request)
    {
        $data =  Product::with('productSizes', 'productCuts', 'productPreparations')
            ->where('is_active', 1)->where('sub_category_id', $subcategory_id)
            ->when(request('country_id', $this->getAuthCountryCode()), function ($q) use ($request) {
                $q->whereHas('productCities', function ($q) {
                    $q->where('country_id', request('country_id', $this->getAuthCountryCode()));
                });
            })
            ->when(request('search'), function ($q) use ($request) {
                $q->where('name_ar', 'like', '%' . $request->search . '%');
            })->get();

        return successResponse(CashierProductResource::collection($data), 'success');
    }

    public function cashierPaymentMethods()
    {
        $data = PaymentType::where('active', 1)
            ->whereIn('code',  [
                'POS',
                'COD',
                'later',
                'Transfer',
                'tamara',
                'Tabby',
                'compensation'
            ])->get();
        return successResponse($data, 'success');
    }

    public function cashierOrderDetails($ref_no)
    {
        $order = Order::where('ref_no', $ref_no)
            ->with(
                'paymentType',
                'customer',
                'payment',
                'orderState',
                'user',
                'salesRepresentative'
            )->first();

        $data['order'] = $order;

        $data['products'] = OrderProduct::with('preparation', 'size', 'cut', 'product.productImages')
            ->where('order_ref_no', $order->ref_no)->get()->map(function ($i) {
                $i->shalwata = $i->shalwata ? true : false;
                return $i;
            });

        return \successResponse($data);
    }

    public function cashierDeleteOrder($ref_no)
    {
        $order = Order::where('ref_no', $ref_no)->delete();

        return \successResponse($order);
    }

    public function cashierCreateOrder(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $validated = $this->validateOrderRequest($request);

            $customer = $this->getCustomer($validated['customer_mobile']);
            $totalBeforeDiscount = $request->total_amount;
            $discountAmount = $this->handleDiscountAmount($validated['discount_code'] ?? null, $totalBeforeDiscount);
            $finalTotal = $totalBeforeDiscount - $discountAmount;

            $walletAmountUsed = 0;
            $lastOrderId = Order::max('id') ?? 0;
            $countryCode = $this->getCountryCode($customer);

            $this->ensureWalletBalance($validated, $customer);

            $orderData = $this->prepareOrderData(
                $validated,
                $customer,
                $totalBeforeDiscount,
                $discountAmount,
                $finalTotal,
                $walletAmountUsed,
                $lastOrderId,
                $countryCode
            );

            $AllOrderData = $this->handleWalletUsage($validated, $customer, $finalTotal, $walletAmountUsed, $orderData);
            $order = Order::create($AllOrderData);

            $this->handleWalletLog($order);
            $this->storeOrderProducts($validated['products'], $order);

            return successResponse($order, 'success');
        });
    }

    public function cashierStorePayment(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $request->validate([
                'order_ref_no' => 'required|exists:orders,ref_no',
                'payment_type_id' => 'required|exists:payment_types,id',
                'comment' => 'nullable|string',
            ]);

            $order = Order::where('ref_no', $request->order_ref_no)->first();

            $lastPaymentId = Payment::max('id') ?? 0;

            $payment = Payment::create([
                'ref_no' => GetNextPaymentRefNo('SA', $lastPaymentId + 1),
                'customer_id' => $order->customer_id,
                'order_ref_no' => $order->ref_no,
                'payment_type_id' =>  $request->payment_type_id,
                'price' => $order->total_amount_after_discount,
                'status' => 'Paid',
                'manual' => 1,
                'description' => 'Payment Created',
            ]);

            $order->update([
                'payment_id' => $payment->id,
                'payment_type_id' => $request->payment_type_id,
                'comment' => $request->comment,
                'paid' => 1,
            ]);

            $order = $order->refresh();

            return successResponse($order, 'success');
        });
    }


    public function cashierDiscountCodeDetails(Request $request)
    {
        $request->validate([
            'discount_code' => 'required|exists:discounts,code',
            'total_amount' => 'required|min:1',
        ]);

        $discount = $this->handleDiscountAmount($request->discount_code, $request->total_amount);

        return successResponse($discount, 'order updated successfully');
    }

    public function cashierUserSalesDetails(Request $request)
    {
        DB::statement('SET sql_mode = " "');

        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $data = DB::table('orders')
            ->when($request->start_date && $request->end_date, function ($query) use ($request) {
                $query->whereBetween('created_at', [$request->start_date, $request->end_date]);
            })
            ->join('payment_types', 'orders.payment_type_id', '=', 'payment_types.id')
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->select(
                'users.id as user_id',
                'users.username as user_name',
                'payment_types.name_en as payment_type_en',
                'payment_types.name_ar as payment_type_ar'
            )
            ->selectRaw('SUM(orders.total_amount) as total')
            ->groupBy('users.id', 'users.username', 'payment_type_en', 'payment_type_ar')
            ->get();

        $groupedData = $data->groupBy('user_id')->map(function ($userOrders, $userId) {
            return [
                'user_id' => $userId,
                'user_name' => $userOrders->first()->user_name, // Assuming all rows for this user have the same name
                'payment_types' => $userOrders->map(function ($order) {
                    return [
                        'payment_type_en' => $order->payment_type_en,
                        'payment_type_ar' => $order->payment_type_ar,
                        'total' => $order->total,
                    ];
                })->values(), // Reset keys for the nested array
            ];
        })->values();

        return successResponse($groupedData, 'success');
    }

    /********************************************************************************************** */

    private function validateOrderRequest(Request $request)
    {
        return $request->validate([
            "customer_mobile" => 'required|min:13',
            "comment" => 'nullable|string',
            'discount_code' => 'nullable',
            'notes' => 'nullable',
            'using_wallet' => 'nullable|in:1,0',
            'total_amount' => 'required|min:1',
            'products' => 'required|array',
            'products.*.total_price' => 'required|min:1',
            'products.*.quantity' => 'required|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.preparation_id' => 'nullable|exists:preparations,id',
            'products.*.size_id' => 'nullable|exists:sizes,id',
            'products.*.cut_id' => 'nullable|exists:cuts,id',
            'products.*.is_kwar3' => 'nullable|in:1,0',
            'products.*.is_Ras' =>  'nullable|in:1,0',
            'products.*.is_lyh' =>  'nullable|in:1,0',
            'products.*.is_karashah' =>  'nullable|in:1,0',
            'products.*.shalwata' => 'nullable|in:1,0',
        ]);
    }

    private function getCustomer($customer_mobile)
    {
        $customer = Customer::where('mobile', $customer_mobile)->first();
        if (!$customer) {
            $customer = Customer::create([
                'name' => 'Customer' . rand(0, 1000),
                'mobile' => $customer_mobile,
                'mobile_country_code' => '+966',
                'country_code' => 'SA',
            ]);
        }
        return $customer;
    }

    private function prepareOrderData($validated, $customer, $totalBeforeDiscount, $discountAmount, $finalTotal, $walletAmountUsed, $lastOrderId, $countryCode)
    {
        return [
            'ref_no' => GetNextOrderRefNo($countryCode, $lastOrderId + 1),
            'delivery_fee' => 0,
            'order_subtotal' => $totalBeforeDiscount,
            'total_amount' => $finalTotal,
            'total_amount_after_discount' => $finalTotal,
            'total_amount_before_discount' => $totalBeforeDiscount,
            'discount_applied' => $discountAmount,
            'delivery_date' => now()->toDateString(),
            'using_wallet' => $validated["using_wallet"] ?? 0,
            'wallet_amount_used' => $walletAmountUsed,
            'customer_id' => $customer->id,
            'payment_type_id' => 1,
            'applied_discount_code' => $validated['discount_code'] ?? null,
            'comment' => $validated['notes'] ?? null,
            'sales_representative_id' => auth()->id(),
            'user_id' => auth()->id(),
            'paid' => 0,
            // 'order_state_id' => 300 الاستلم من الفرع
        ];
    }

    private function handleWalletUsage($validated, $customer, &$finalTotal, &$walletAmountUsed, $orderData)
    {
        if ($validated['using_wallet'] && $customer->wallet) {
            $walletAmountUsed = min($customer->wallet, $finalTotal);
            $finalTotal -= $walletAmountUsed;
            $customer->wallet -= $walletAmountUsed;
            $customer->save();

            $orderData['wallet_amount_used'] = $walletAmountUsed;
            $orderData['total_amount_after_discount'] = $finalTotal;
            $orderData['paid'] = $finalTotal == 0 ? 1 : 0;
        }

        return $orderData;
    }

    private function handleWalletLog($order)
    {
        if ($order->using_wallet) {
            WalletLog::create([
                'user_id' => auth()->id(),
                'customer_id' => $order->customer_id,
                'last_amount' => $order->wallet_amount_used + $order->customer->wallet,
                'new_amount' => $order->customer->wallet,
                'action_id' => $order->ref_no,
                'action' => 'new_order',
                'message_en' => 'Payment Order with number ' . $order->ref_no,
                'message_ar' => 'سداد الطلب رقم ' . $order->ref_no,
            ]);
        }
    }

    private function handleDiscountAmount($code, $TotalAmountBeforeDiscount)
    {
        $value = 0;
        if ($code) {
            $discount = Discount::where('code', 'like', '%' . $code . '%')->where('is_active', 1)->first();
            if ($discount && $TotalAmountBeforeDiscount > 0) {
                if ($discount->is_percent) {
                    $value = (($TotalAmountBeforeDiscount * $discount->discount_amount_percent) / 100) ?? 0;
                } else {
                    $value = ($TotalAmountBeforeDiscount - $discount->discount_amount_percent) ?? 0;
                }

                if ($value > $discount->max_discount) {
                    $value = $discount->max_discount;
                }
            }
        }

        return $value;
    }

    private function storeOrderProducts($products, $order)
    {
        $orderProducts = [];
        foreach ($products as $item) {

            array_push($orderProducts, [
                'order_ref_no' => $order->ref_no,
                'total_price' => $item['total_price'],
                'quantity' => $item['quantity'],
                'product_id' => $item['product_id'],
                'preparation_id' => $item['preparation_id'] ?? null,
                'size_id' => $item['size_id'] ?? null,
                'cut_id' => $item['cut_id'] ?? null,
                'is_kwar3' => $item['is_kwar3'] ?? false,
                'is_Ras' => $item['is_Ras'] ?? false,
                'is_lyh' => $item['is_lyh'] ?? false,
                'is_karashah' => $item['is_karashah'] ?? false,
                'shalwata_id' => isset($item['shalwata']) && $item['shalwata'] ? 1 : null,
            ]);

            try {
                Product::find($item['product_id'])->update(['no_sale' => DB::raw('no_sale + 1')]);
            } catch (\Throwable $th) {
                //throw $th;
            }
        }

        return OrderProduct::insert($orderProducts);
    }

    private function getAuthCountryCode()
    {
        return null;
        // return auth()->user()->mobile_country_code === '+966' ? 1 : 4;
    }

    private function getCountryCode($customer)
    {
        return $customer->mobile_country_code === '+966' ? 'SA' : 'AE';
    }

    private function ensureWalletBalance($validated, $customer)
    {
        if ($validated["using_wallet"] == 1 && $customer->wallet == 0) {
            throw new \Exception('Your wallet is empty!', 400);
        }
    }
}
