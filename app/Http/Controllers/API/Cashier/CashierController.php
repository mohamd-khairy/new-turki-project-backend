<?php

namespace App\Http\Controllers\API\Cashier;

use App\Http\Controllers\API\CouponController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\Controller;
use App\Http\Resources\Dashboard\CashierCategoryResource;
use App\Http\Resources\Dashboard\CashierProductCodeResource;
use App\Http\Resources\Dashboard\CashierProductResource;
use App\Http\Resources\Dashboard\CashierSubcategoryResource;
use App\Models\CashierPayment;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Cut;
use App\Models\Discount;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Payment;
use App\Models\PaymentType;
use App\Models\Preparation;
use App\Models\Product;
use App\Models\Shalwata;
use App\Models\Size;
use App\Models\SubCategory;
use App\Models\WalletLog;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CashierController extends Controller
{
    public function cashierCategories(Request $request)
    {
        $data =  Category::whereHas('subCategories')->orderBy('id', 'desc')
            ->when(request('country_id', $this->getAuthCountryCode()), function ($q) use ($request) {
                $q->whereHas('categoryCities', function ($q) {
                    $q->where('country_id', request('country_id', $this->getAuthCountryCode()))
                        ->where('city_id', auth()->user()->branch->city_id)
                        ->where('is_active', 1);
                });
            })
            ->when(request('search'), function ($q) use ($request) {
                $q->where('type_ar', 'like', '%' . $request->search . '%');
            })
            ->get();

        return successResponse(CashierCategoryResource::collection($data), 'success');
    }

    public function cashierSubCategories($category_id, Request $request)
    {
        $data =  SubCategory::whereHas('products')->orderBy('id', 'desc')
            ->where('category_id', $category_id)
            ->when(request('country_id', $this->getAuthCountryCode()), function ($q) use ($request) {
                $q->whereHas('subCategoryCities', function ($q) {
                    $q->where('country_id', request('country_id', $this->getAuthCountryCode()))
                        ->where('city_id', auth()->user()->branch->city_id)
                        ->where('is_active', 1);
                });
            })
            ->when(request('search'), function ($q) use ($request) {
                $q->where('type_ar', 'like', '%' . $request->search . '%');
            })->get();

        return successResponse(CashierSubcategoryResource::collection($data), 'success');
    }

    public function cashierProducts($subcategory_id, Request $request)
    {
        $data =  Product::with('productSizes', 'productCuts', 'productPreparations')
            ->where('is_active', 1)
            ->when(request('country_id', $this->getAuthCountryCode()), function ($q) use ($request) {
                $q->whereHas('productCities', function ($q) {
                    $q->where('country_id', request('country_id', $this->getAuthCountryCode()))
                        ->where('city_id', auth()->user()->branch->city_id)
                        ->where('is_active', 1);
                });
            })
            ->when(empty(request('search')), function ($q) use ($subcategory_id) {
                $q->where('sub_category_id', $subcategory_id);
            })
            ->when(request('search'), function ($q) use ($request) {
                $q->where('name_ar', 'like', '%' . $request->search . '%');
            })->get();

        return successResponse(CashierProductResource::collection($data), 'success');
    }

    public function cashierProductCode($product_code)
    {
        $data =  Product::with('productSizes', 'productCuts', 'productPreparations')
            ->where('is_active', 1)
            ->whereHas('productSizes', function ($q) use ($product_code) {
                $q->where('product_code', $product_code);
            })
            ->take(1)
            ->first();

        return successResponse(new  CashierProductCodeResource($data), 'success');
    }

    public function cashierPaymentMethods()
    {
        $data = PaymentType::query() //where('active', 1)
            ->whereNotIn('code',  [
                'COD',
                'Wallet',
                'Sadqa'
            ])->get();
        return successResponse($data, 'success');
    }

    public function cashierOrderDetails($ref_no)
    {
        $order = Order::where('ref_no', $ref_no)
            ->with(
                'paymentType',
                'customer.default_addresses.city',
                'payment',
                'orderState',
                'user',
                'salesRepresentative',
                'deliveryPeriod',
                'paidpayment'
            )->first();

        $data['order'] = $order;

        $data['products'] = OrderProduct::with('preparation', 'size', 'cut', 'product.productImages')
            ->where('order_ref_no', $order->ref_no)->get()->map(function ($i) {
                $i->shalwata = $i->shalwata ? true : false;
                return $i;
            });

        $payments = CashierPayment::with('payment_type')->where('order_ref_no', $order->ref_no)->get();
        if ($payments->count() > 0) {

            $data['paid_payment_types'] = implode(' - ',  $payments->pluck('payment_type.name_ar')->toArray());
        } else {
            $data['paid_payment_types'] = isset($order->paymentType->name_ar) ? $order->paymentType->name_ar : null;
        }

        return \successResponse($data);
    }

    public function cashierDeleteOrder($ref_no)
    {
        $order = Order::where('ref_no', $ref_no)->delete();

        return \successResponse($order);
    }

    public function cashierEditOrder(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $validated = $this->validateEditOrderRequest($request);

            $order = Order::with('customer')->where('ref_no', $request->ref_no)->first();

            if (!$order) {
                return failResponse('Order not found');
            }

            OrderProduct::where('order_ref_no', $order->ref_no)->delete();
            Payment::where('order_ref_no', $order->ref_no)->delete();
            DB::table('cashier_payments')->where('order_ref_no', $order->ref_no)->delete();

            $customer = $this->getCustomer($validated['customer_mobile']);
            $totalBeforeDiscount = $request->total_amount;
            $otherDiscount = $order->other_discount ?? 0;
            $discountAmount = $validated['applied_discount_code'] ?
                $this->handleDiscountAmount($validated['applied_discount_code'] ?? null, $totalBeforeDiscount, $request) : 0;
            $finalTotal =  $totalBeforeDiscount - $discountAmount - $otherDiscount;
            $walletAmountUsed = 0;
            $orderData = $this->prepareEditOrderData(
                $validated,
                $customer,
                $totalBeforeDiscount,
                $discountAmount,
                $finalTotal,
                $walletAmountUsed,
            );

            $AllOrderData = $this->handleWalletUsage($validated, $customer, $finalTotal, $walletAmountUsed, $orderData);
            $order->update($AllOrderData);
            $order->refresh();

            $this->handleWalletLog($order);
            $this->storeOrderProducts($validated['products'], $order);

            return successResponse($order, 'success');
        });
    }

    public function cashierOrderUpdate($ref_no)
    {
        $order = Order::where('ref_no', $ref_no)->first();

        if (!$order) {
            return failResponse('Order not found');
        }

        if (request('order_state_id')) {
            $order->update(['order_state_id' => request('order_state_id')]);
        }

        if (request()->has('paid')) {
            $order->update(['paid' => request('paid')]);
        }

        if (request()->has('other_discount')) {
            if (request('other_discount') <= $order->total_amount_after_discount && empty($order->other_discount)) {
                $order->update([
                    'total_amount_after_discount' => $order->total_amount_after_discount - request('other_discount'),
                    'total_amount' => $order->total_amount - request('other_discount'),
                    'other_discount' => request('other_discount'),
                ]);
            } elseif (request('other_discount') <= $order->total_amount_after_discount && $order->other_discount > 0) {
                $order->update([
                    'total_amount_after_discount' => ($order->total_amount_after_discount + $order->other_discount) - request('other_discount'),
                    'total_amount' => ($order->total_amount + $order->other_discount) - request('other_discount'),
                    'other_discount' => request('other_discount'),
                ]);
            } else {
                return failResponse('Discount amount is greater than total amount');
            }
        }

        if (request('order_state_id') && request('order_state_id') == '203') {
            touchStock($order);
        }

        return \successResponse($order);
    }

    public function cashierCreateOrder(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $validated = $this->validateOrderRequest($request);

            $customer = $this->getCustomer($validated['customer_mobile']);
            $totalBeforeDiscount = $request->total_amount;
            $discountAmount = $validated['applied_discount_code'] ?
                $this->handleDiscountAmount($validated['applied_discount_code'] ?? null, $totalBeforeDiscount, $request)
                : 0;
            $finalTotal =  $totalBeforeDiscount - $discountAmount;

            $walletAmountUsed = 0;

            $lastOrder = Order::withTrashed()->latest("id")->first();
            $countryCode = $this->getCountryCode($customer);

            $this->ensureWalletBalance($validated, $customer);

            $orderData = $this->prepareOrderData(
                $validated,
                $customer,
                $totalBeforeDiscount,
                $discountAmount,
                $finalTotal,
                $walletAmountUsed,
                $lastOrder ? $lastOrder->id : 0,
                $countryCode
            );

            $AllOrderData = $this->handleWalletUsage($validated, $customer, $finalTotal, $walletAmountUsed, $orderData);
            $order = Order::updateOrCreate(['ref_no' => $orderData['ref_no']], $AllOrderData);

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
                'payment_type_id' => 'nullable|exists:payment_types,id',
                'comment' => 'nullable|string',
                'later' => 'nullable|boolean',
                'payment_types' => 'nullable|array',
                'prices' => 'nullable|array',
            ]);

            $order = Order::with('customer')->where('ref_no', $request->order_ref_no)->first();

            if (!$request->later) {

                $lastPaymentId = Payment::max('id') ?? 0;

                $payment = Payment::create([
                    'ref_no' => GetNextPaymentRefNo('SA', $lastPaymentId + 1),
                    'customer_id' => $order->customer_id,
                    'order_ref_no' => $order->ref_no,
                    'payment_type_id' => $request->payment_type_id,
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
                    'later' => 0,
                ]);
            } else {
                $order->update([
                    'payment_id' => null,
                    'payment_type_id' => null,
                    'comment' => $request->comment,
                    'later' => 1,
                ]);
            }

            if ($request->has('prices') && $request->prices) {
                DB::table('cashier_payments')->where('order_ref_no', $order->ref_no)->delete();
                foreach ($request->prices as $payment_id => $payment_value) {
                    $payments[] = [
                        'order_ref_no' => $order->ref_no,
                        'payment_id' => $payment_id,
                        'payment_value' => $payment_value,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                DB::table('cashier_payments')->insert($payments);
            }


            $order = $order->refresh();

            return successResponse($order, 'success');
        });
    }

    public function cashierLaterOrder()
    {
        $orders = Order::select('id', 'ref_no')->where('later', 1)->latest()->take(4)->get();
        return successResponse($orders, 'success');
    }

    public function cashierDiscountCodeDetails(Request $request)
    {
        $request->validate([
            'discount_code' => 'required|exists:discounts,code',
            'total_amount' => 'required|min:1',
            'products' => 'required|array',
        ]);

        $discount = $this->handleDiscountAmount($request->discount_code, $request->total_amount, $request);

        if ($discount > $request->total_amount) {
            $amount = 0;
        } else {
            $amount = $discount ?? 0;
        }
        return successResponse($amount, 'order updated successfully');
    }

    public function cashierUserSalesDetails(Request $request)
    {
        // Set SQL mode for MySQL
        DB::statement('SET sql_mode = ""');

        // Validate input
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        // Payment types
        $paymentTypes = PaymentType::whereNotIn('code', ['COD', 'Wallet', 'Sadqa'])->get(['id', 'name_en', 'name_ar', 'code']);

        // Determine date range
        $start_date = $request->start_date ?? date('Y-m-d');
        $end_date = $request->end_date ?? date('Y-m-d');

        $data = [];
        $orders = DB::table('cashier_payments')
            ->when($start_date, fn($query) => $query->whereDate('cashier_payments.created_at', '>=', $start_date))
            ->when($end_date, fn($query) => $query->whereDate('cashier_payments.created_at', '<=', $end_date))
            ->when(empty($start_date) && empty($end_date), fn($query) => $query->whereDate('cashier_payments.created_at', date('Y-m-d')))
            ->leftJoin('orders', 'orders.ref_no', '=', 'cashier_payments.order_ref_no')
            ->leftJoin('payment_types', 'cashier_payments.payment_id', '=', 'payment_types.id')
            ->leftJoin('users', 'orders.user_id', '=', 'users.id')
            ->leftJoin('branches', 'branches.id', '=', 'users.branch_id')
            ->where('orders.paid', 1);

        $selectColumns = [
            'users.id as user_id',
            'users.username as user_name',
            'branches.name as branch_name',
            'payment_types.name_en as payment_type_en',
            'cashier_payments.order_ref_no',
            DB::raw('DATE(cashier_payments.created_at) as date'),
            DB::raw('SUM(cashier_payments.payment_value) as total'),
            DB::raw('COUNT(DISTINCT cashier_payments.order_ref_no) as order_count'),
        ];

        foreach ($paymentTypes as $paymentType) {
            $selectColumns[] = DB::raw('SUM(IF(cashier_payments.payment_id = ' . $paymentType->id . ', cashier_payments.payment_value, 0)) as ' . $paymentType->name_en);
        }

        //for refund remove it when add it to pyments_types
        $selectColumns[] = DB::raw('SUM(IF(cashier_payments.payment_id = 1000 , cashier_payments.payment_value, 0)) as refund');

        $orders->select($selectColumns);

        $orders = $orders->groupBy('user_id', 'date')->get();

        $currentDate = strtotime($start_date);
        $endDate = strtotime($end_date);

        while ($currentDate <= $endDate) {
            $dateString = date('Y-m-d', $currentDate);

            $data[] = $orders->filter(fn($order) => date('Y-m-d', strtotime($order->date)) == $dateString);

            $currentDate = strtotime("+1 day", $currentDate);
        }

        return response()->json([
            'success' => true,
            'data' => Arr::flatten($data),
            'payment_types' => $paymentTypes,
            'description' => 'success',
            'code' => 200,
        ], 200);
    }

    public function cashierOrders(Request $request)
    {
        $total = 0;
        $perPage = $request->input('per_page', 20);
        $perPage = ($perPage == 0) ? 20 : $perPage;

        $orders = DB::table('orders')
            ->select(
                'orders.*',
                'customers.name as customer_name',
                'customers.mobile as customer_mobile',
                DB::raw('LEFT(customers.mobile, 4) as mobile_prefix'),
                'order_states.state_ar as order_state_ar',
                'order_states.state_en as order_state_en',
                'payment_types.name_ar as payment_type_name',
                'payment_types.code as payment_type_code',
                'payments.price as payment_price',
                'payments.status as payment_status',
                'users.username as sales_officer_name',
            )
            ->whereNull('orders.deleted_at')
            ->join('customers', 'customers.id', '=', 'orders.customer_id')
            ->leftJoin('users as u', 'u.id', '=', 'orders.user_id')
            ->leftJoin('users', 'users.id', '=', 'orders.sales_representative_id')
            ->leftJoin('order_states', 'order_states.code', '=', 'orders.order_state_id')
            ->leftJoin('payment_types', 'payment_types.id', '=', 'orders.payment_type_id')
            ->leftJoin('payments', 'payments.id', '=', 'orders.payment_id')
            ->whereNull('orders.address_id')
            ->orderBy('orders.id', 'desc')
            ->when(request('country_ids'), function ($query) {
                $query->having('mobile_prefix', '=', request('country_ids') == 1 ? '+966' : '+971');
            })
            ->when(request('order_state_ids'), function ($query) {
                $query->whereIn('orders.order_state_id', request('order_state_ids'));
            })
            ->when(request('date_from') && request('date_to'), function ($query) {
                $query->whereBetween('orders.created_at', [date('Y-m-d', strtotime(request('date_from'))), date('Y-m-d', strtotime(request('date_to')))]);
            })
            ->when(request('customer_id'), function ($query) {
                $query->where('orders.customer_id', request('customer_id'));
            })
            ->when(request('mobile'), function ($query) {
                $query->where('customers.mobile', request('mobile'));
            })
            ->when(request('user_id'), function ($query) {
                $query->where('orders.user_id', request('user_id'));
            })
            ->when(request('sales_agent_id'), function ($query) {
                $query->where('orders.user_id', request('sales_agent_id'));
            })
            ->when(request('sales_representative_id'), function ($query) {
                $query->where('orders.sales_representative_id', request('sales_representative_id'));
            })
            ->when(request('payment_type_ids'), function ($query) {
                $payment_type_ids = is_array(request('payment_type_ids')) ? request('payment_type_ids') : json_decode(request('payment_type_ids'));
                $query->whereIn('orders.payment_type_id', $payment_type_ids ?? []);
            })
            ->when(auth()->check() && in_array('delegate', auth()->user()->roles->pluck('name')->toArray()), function ($query) {
                $query->where('orders.user_id', auth()->user()->id);
            })
            ->when(request('ref_no'), function ($query) {
                $query->where('orders.ref_no', request('ref_no'));
            });


        $total = $orders->sum('total_amount');

        $orders = $orders->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $orders,
            'total' => $total,
            'message' => 'Retrieved successfully',
            'code' => '200',
        ], 200);
    }

    public function cashierOrderRefund($ref_no, Request $request)
    {
        $orderProduct = OrderProduct::where('order_ref_no', $ref_no)
            ->whereIn('id', $request->ids)
            ->whereNull('refund_at')
            ->get();

        foreach ($orderProduct as $product) {
            $product->update([
                'is_refund' => 1,
                'refund_at' => date('Y-m-d'),
            ]);
        }

        return successResponse(true);
    }

    /********************************************************************************************** */

    private function validateOrderRequest(Request $request)
    {
        return $request->validate([
            "customer_mobile" => 'required|min:13',
            "comment" => 'nullable|string',
            'applied_discount_code' => 'nullable',
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

    private function validateEditOrderRequest(Request $request)
    {
        return $request->validate([
            "ref_no" => 'required|exists:orders,ref_no',
            "customer_mobile" => 'required|min:13',
            "comment" => 'nullable|string',
            'applied_discount_code' => 'nullable',
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
            'applied_discount_code' => $validated['applied_discount_code'] ?? null,
            'comment' => $validated['notes'] ?? null,
            'sales_representative_id' => auth()->id(),
            'user_id' => auth()->id(),
            'paid' => 0,
            'order_state_id' => 202 // الاستلم من الفرع
        ];
    }

    private function prepareEditOrderData($validated, $customer, $totalBeforeDiscount, $discountAmount, $finalTotal, $walletAmountUsed)
    {
        return [
            'order_subtotal' => $totalBeforeDiscount,
            'total_amount' => $finalTotal,
            'total_amount_after_discount' => $finalTotal,
            'total_amount_before_discount' => $totalBeforeDiscount,
            'discount_applied' => $discountAmount,
            'delivery_date' => now()->toDateString(),
            'wallet_amount_used' => $walletAmountUsed,
            'customer_id' => $customer->id,
            'payment_type_id' => 1,
            'applied_discount_code' => $validated['applied_discount_code'] ?? null,
            'comment' => $validated['notes'] ?? null,
            'order_state_id' => 202 // الاستلم من الفرع
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
            if ($finalTotal == 0) {
                $orderData['paid'] = 1;
            }
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

    private function handleDiscountAmount($code, $TotalAmountBeforeDiscount, $data)
    {
        $discount = Discount::where('code', 'like', '%' . $code . '%')->where('is_active', 1)->first();
        if (!$discount) {
            return 0;
        }

        $discountAmount = Discount::isValidForCashier($discount, $data, $TotalAmountBeforeDiscount, $this->getAuthCountryCode(), $this->getAuthCityCode());

        return $discountAmount ?? 0;
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
        // return null;
        return auth()->user()->mobile_country_code != '+966' ? 4 : 1;
    }

    private function getCountryCode($customer)
    {
        return  substr($customer->mobile, 0, 4) === '+966' ? 'SA' : 'AE';
    }

    private function getAuthCityCode()
    {
        // return null;
        return auth()->user()->branch->city->id  ?? 165;
    }

    private function ensureWalletBalance($validated, $customer)
    {
        if ($validated["using_wallet"] == 1 && $customer->wallet == 0) {
            throw new \Exception('Your wallet is empty!', 400);
        }
    }
}
