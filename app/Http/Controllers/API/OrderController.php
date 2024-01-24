<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Cart;
use App\Models\TraceError;
use App\Models\Country;
use App\Models\Customer;
use App\Models\DeliveryDate;
use App\Models\DeliveryDateCity;
use App\Models\Discount;
use App\Models\MinOrder;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Payment;
use App\Models\PaymentType;
use App\Models\Product;
use App\Models\Shalwata;
use App\Models\Size;
use App\Models\TempCouponProducts;
use App\Services\TamaraApiService;
use App\Services\TamaraApiServiceV2;
use App\Services\NgeniusPaymentService;
use App\Services\MyFatoorahApiService;
use App\Services\TabbyApiService;
use App\Services\PointLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function __construct()
    {
        if (env('PERMISSIONS', false)) {
            $this->middleware('permission:read-order', ['only' => ['getOrdersDashboard']]);
        }
    }

    public function assignUserOrder(Request $request)
    {
        $request->validate([
            "user_id" => 'required|exists:users,id',
            'order_ids' => 'required|array',
            'order_ids.*' => 'required|exists:orders,id',
        ]);

        $order_ids = is_array($request->order_ids) ? $request->order_ids : json_decode($request->order_ids);

        $order = Order::whereIn('id', $order_ids ?? [])->get()->map->update(['user_id' => $request->user_id]);

        return successResponse(true);
    }

    public function takeOrder($id)
    {
        $order = Order::where('id', $id)->first();
        if ($order) {
            $order->update(['sales_representative_id' => auth()->user()->id]);
        }

        return successResponse($order);
    }

    public function getCustomerWallet($id)
    {
        $customer = Customer::with('wallet_orders.payment')->where('id', $id)->first();
        if (!$customer) {
            return failResponse([], 'لا يوجد عميل بهذا الرقم');
        }

        return successResponse($customer);
    }

    // public function getOrdersDashboard(Request $request)
    // {
    // $order_states = Cache::remember('order_states', $minutes = 365 * 24 * 60, function () {
    //     return handleRoleOrderState(auth()->user()->roles->pluck('name')->toArray())['orders'];
    // });

    //     $perPage = $request->input('per_page', 6);
    // $perPage = ($perPage == 0) ? 6 : $perPage;

    //     $orders = DB::table('orders')
    //         ->select(
    //             'orders.*',
    //             'customers.name as customer_name',
    //             'customers.mobile as customer_mobile',
    //             'order_states.state_ar as order_state_ar',
    //             'order_states.state_en as order_state_en',
    //             'shalwatas.name_ar as shalwata_name',
    //             'shalwatas.price as shalwata_price',
    //             'payment_types.name_ar as payment_type_name',
    //             'delivery_periods.name_ar as delivery_period_name',
    //             'delivery_periods.time_hhmm as delivery_period_time',
    //             'payments.price as payment_price',
    //             'payments.status as payment_status',
    //             'addresses.address as address_address',
    //             'addresses.lat as address_lat',
    //             'addresses.long as address_long',
    //             'addresses.country_id as address_country_id',
    //             'addresses.city_id as address_city_id',
    //             'cities.name_ar as city_name',
    //             'users.username as sales_officer_name',
    //             'u.username as driver_name',
    //             'u.id as driver_id',
    //         )
    //         ->join('customers', 'customers.id', '=', 'orders.customer_id')
    //         ->leftJoin('users as u', 'u.id', '=', 'orders.user_id')
    //         ->leftJoin('users', 'users.id', '=', 'orders.sales_representative_id')
    //         ->leftJoin('order_states', 'order_states.code', '=', 'orders.order_state_id')
    //         ->leftJoin('shalwatas', 'shalwatas.id', '=', 'orders.shalwata_id')
    //         ->leftJoin('payment_types', 'payment_types.id', '=', 'orders.payment_type_id')
    //         ->leftJoin('delivery_periods', 'delivery_periods.id', '=', 'orders.delivery_period_id')
    //         ->leftJoin('payments', 'payments.id', '=', 'orders.payment_id')
    //         ->leftJoin('addresses', 'addresses.id', '=', 'orders.address_id')
    //         ->leftJoin('cities', 'cities.id', '=', 'addresses.city_id');

    //     if (request()->header('Type') != 'dashboard') {
    //         $orders = $orders->where('orders.customer_id', auth()->user()->id);
    //     }

    //     if ($order_states) {
    //         $orders = $orders->whereIn('orders.order_state_id', $order_states ?? []);
    //     }
    //     if (request('city_ids')) {
    //         $orders = $orders->whereIn('addresses.city_id', request('city_ids'));
    //     }
    //     if (request('country_ids')) {
    //         $orders = $orders->where('addresses.country_id', request('country_ids'));
    //     }
    //     if (request('order_state_ids')) {
    //         $orders = $orders->whereIn('orders.order_state_id', request('order_state_ids'));
    //     }

    //     if (request('date_from') && request('date_to')) {
    //         // $orders = $orders->where(function ($q) {
    //         //     $q->whereDate('orders.delivery_date', ">=", request('date_from'))->whereDate('orders.delivery_date', "<=", request('date_to'));
    //         // });

    //         $orders = $orders->where(function ($query) {
    //             $query->whereRaw(
    //                 'IF(LENGTH(orders.delivery_date) - LENGTH(REPLACE(orders.delivery_date, "-", "")) < 2, CONCAT(SUBSTRING_INDEX(orders.created_at, "-", 1), "-", orders.delivery_date), orders.delivery_date) BETWEEN ? AND ?',
    //                 [date('Y-m-d', strtotime(request('date_from'))), date('Y-m-d', strtotime(request('date_to')))]
    //             );
    //         });
    //     }
    //     if (request('delivery_date')) {
    //         // $orders = $orders->where('orders.delivery_date', date('Y-m-d', strtotime(request('delivery_date'))));
    //         $orders = $orders->whereRaw('IF(LENGTH(orders.delivery_date) - LENGTH(REPLACE(orders.delivery_date, "-", "")) < 2, CONCAT(SUBSTRING_INDEX(orders.created_at, "-", 1), "-", orders.delivery_date), orders.delivery_date) = ?', [date('Y-m-d', strtotime(request('delivery_date')))]);
    //     }
    //     if (request('delivery_period_id')) {
    //         $orders = $orders->where('orders.delivery_period_id', request('delivery_period_id'));
    //     }
    //     if (request('customer_id')) {
    //         $orders = $orders->where('orders.customer_id', request('customer_id'));
    //     }
    //     if (request('mobile')) {
    //         $orders = $orders->where('customers.mobile', request('mobile'));
    //     }
    //     if (request('user_id')) {
    //         $orders = $orders->where('orders.user_id', request('user_id'));
    //     }

    //     if (request('sales_agent_id')) {
    //         $orders = $orders->where('orders.user_id', request('sales_agent_id'));
    //     }

    //     if (request('sales_representative_id')) {
    //         $orders = $orders->where('orders.sales_representative_id', request('sales_representative_id'));
    //     }

    //     if (request('payment_type_ids')) {
    //         $payment_type_ids = is_array(request('payment_type_ids')) ? request('payment_type_ids') : json_decode(request('payment_type_ids'));
    //         $orders = $payment_type_ids  ? $orders->whereIn('orders.payment_type_id', $payment_type_ids ?? []) : $orders;
    //     }

    //     if (in_array('delegate', auth()->user()->roles->pluck('name')->toArray())) {
    //         $orders = $orders->where('orders.user_id', auth()->user()->id);
    //     }

    //     $total = $orders->sum('total_amount_after_discount');

    //     $orders = $orders->orderBy('id', 'desc')->paginate($perPage);

    //     $items =  $orders->toArray()['data'];

    //     $items = collect($items)->map(function ($i) {
    //         $i->total_amount_after_tax = $i->total_amount_after_discount ? round($i->total_amount_after_discount / 1.15, 2) : 0;
    //         $i->tax_fees = round(($i->total_amount_after_discount ?? 0) - ($i->total_amount_after_tax  ?? 0), 2);

    //         if ($i->payment_status == 'Paid') {
    //             $i->remain_amount = $i->payment_price ? ($i->total_amount_after_discount - $i->payment_price) : $i->total_amount_after_discount ?? 0;

    //             if (!$i->paid && $i->remain_amount <= 0) {
    //                 Order::where('id', $i->id)->update(['paid' => 1]);
    //                 $i->paid = 1;
    //             }
    //         } else {
    //             $i->remain_amount = $i->total_amount_after_discount;
    //             $i->payment_price = 0;
    //         }

    //         $i->orderProducts = OrderProduct::with('preparation', 'size', 'cut', 'shalwata', 'product.productImages')
    //             ->where('order_ref_no', $i->ref_no)->get();
    //         $i->is_printed = $i->printed_at ?  true : false;
    //         return $i;
    //     });

    //     $orders->data = $items;

    //     return response()->json([
    //         'success' => true, 'data' => $orders, 'total' => $total,
    //         'message' => 'retrieved successfully', 'description' => '', 'code' => '200'
    //     ], 200);
    // }

    public function getOrdersDashboard(Request $request)
    {
        $total = 0;
        $orderStates = Cache::remember('order_states', $minutes = 365 * 24 * 60, function () {
            return handleRoleOrderState(auth()->user()->roles->pluck('name')->toArray())['orders'];
        });

        $perPage = $request->input('per_page', 6);
        $perPage = ($perPage == 0) ? 6 : $perPage;

        $orders = DB::table('orders')
            ->select(
                'orders.*',
                'customers.name as customer_name',
                'customers.mobile as customer_mobile',
                'order_states.state_ar as order_state_ar',
                'order_states.state_en as order_state_en',
                'shalwatas.name_ar as shalwata_name',
                'shalwatas.price as shalwata_price',
                'payment_types.name_ar as payment_type_name',
                'delivery_periods.name_ar as delivery_period_name',
                'delivery_periods.time_hhmm as delivery_period_time',
                'payments.price as payment_price',
                'payments.status as payment_status',
                'addresses.address as address_address',
                'addresses.lat as address_lat',
                'addresses.long as address_long',
                'addresses.country_id as address_country_id',
                'addresses.city_id as address_city_id',
                'cities.name_ar as city_name',
                'users.username as sales_officer_name',
                'u.username as driver_name',
                'u.id as driver_id'
            )
            ->join('customers', 'customers.id', '=', 'orders.customer_id')
            ->leftJoin('users as u', 'u.id', '=', 'orders.user_id')
            ->leftJoin('users', 'users.id', '=', 'orders.sales_representative_id')
            ->leftJoin('order_states', 'order_states.code', '=', 'orders.order_state_id')
            ->leftJoin('shalwatas', 'shalwatas.id', '=', 'orders.shalwata_id')
            ->leftJoin('payment_types', 'payment_types.id', '=', 'orders.payment_type_id')
            ->leftJoin('delivery_periods', 'delivery_periods.id', '=', 'orders.delivery_period_id')
            ->leftJoin('payments', 'payments.id', '=', 'orders.payment_id')
            ->leftJoin('addresses', 'addresses.id', '=', 'orders.address_id')
            ->leftJoin('cities', 'cities.id', '=', 'addresses.city_id')
            ->when(request()->header('Type') != 'dashboard', function ($query) {
                $query->where('orders.customer_id', auth()->user()->id);
            })
            ->when($orderStates, function ($query) use ($orderStates) {
                $query->whereIn('orders.order_state_id', $orderStates);
            })
            ->when(request('order_state_ids'), function ($query) {
                $query->whereIn('orders.order_state_id', request('order_state_ids'));
            })
            ->when(request('city_ids'), function ($query) {
                $query->whereIn('addresses.city_id', request('city_ids'));
            })
            ->when(request('country_ids'), function ($query) {
                $query->where('addresses.country_id', request('country_ids'));
            })
            ->when(request('date_from') && request('date_to'), function ($query) {
                $query->where(function ($q) {
                    $q->whereRaw(
                        'IF(LENGTH(orders.delivery_date) - LENGTH(REPLACE(orders.delivery_date, "-", "")) < 2, CONCAT(SUBSTRING_INDEX(orders.created_at, "-", 1), "-", orders.delivery_date), orders.delivery_date) BETWEEN ? AND ?',
                        [date('Y-m-d', strtotime(request('date_from'))), date('Y-m-d', strtotime(request('date_to')))]
                    );
                });
            })
            ->when(request('delivery_date'), function ($query) {
                $query->whereRaw('IF(LENGTH(orders.delivery_date) - LENGTH(REPLACE(orders.delivery_date, "-", "")) < 2, CONCAT(SUBSTRING_INDEX(orders.created_at, "-", 1), "-", orders.delivery_date), orders.delivery_date) = ?', [date('Y-m-d', strtotime(request('delivery_date')))]);
            })
            ->when(request('delivery_period_ids'), function ($query) {
                $query->whereIn('orders.delivery_period_id', request('delivery_period_ids'));
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
            ->when(in_array('delegate', auth()->user()->roles->pluck('name')->toArray()), function ($query) {
                $query->where('orders.user_id', auth()->user()->id);
            })
            ->when(request('ref_no'), function ($query) {
                $query->where('orders.ref_no', request('ref_no'));
            })
            ->when(!in_array('admin', auth()->user()->roles->pluck('name')->toArray()) && request()->header('Type') == 'dashboard', function ($query) {
                $query->where('addresses.country_id', strtolower(auth()->user()->country_code) == 'sa' ? 1 : 4);
            });

        // Add more conditions based on request parameters
        $orders = $orders->orderBy('orders.id', 'desc');

        if (request()->header('Type') == 'dashboard') {
            $total = $orders->sum('total_amount_after_discount');
        }

        $orders = $orders->paginate($perPage);

        $items = $this->transformOrderData($orders->items());

        $orders->data = $items;

        return response()->json([
            'success' => true,
            'data' => $orders,
            'total' => $total,
            'message' => 'Retrieved successfully',
            'description' => '',
            'code' => '200'
        ], 200);
    }


    private function transformOrderData($items)
    {
        return collect($items)->map(function ($i) {

            // Handle payment status logic
            $this->handlePaymentStatus($i);

            if (request()->header('Type') != 'dashboard') {

                $per = 1.15;
                if ($i->address_country_id == 4) {
                    $per = 1.05;
                }

                // Perform the common calculations
                $i->total_amount_after_tax = round($i->total_amount_after_discount ? $i->total_amount_after_discount / $per : 0, 2);
                $i->tax_fees = round(($i->total_amount_after_discount ?? 0) - ($i->total_amount_after_tax  ?? 0), 2);
                $i->orderProducts = $this->loadOrderProducts($i->ref_no);
            }

            // Additional transformations and checks
            $i->is_printed = $i->printed_at ? true : false;

            return $i;
        });
    }

    private function handlePaymentStatus($order)
    {
        if ($order->payment_status == 'Paid') {
            $order->remain_amount = $order->payment_price ? ($order->total_amount_after_discount - $order->payment_price) : $order->total_amount_after_discount ?? 0;

            if (!$order->paid && $order->remain_amount <= 0) {
                Order::where('id', $order->id)->update(['paid' => 1]);
            }
        } else {
            $order->remain_amount = $order->total_amount_after_discount;
            $order->payment_price = 0;
        }
    }

    private function loadOrderProducts($orderRefNo)
    {
        return OrderProduct::with('preparation', 'size', 'cut', 'shalwata', 'product.productImages')
            ->where('order_ref_no', $orderRefNo)
            ->get();
    }


    public function getOrderDashboard($order)
    {
        $order = Order::where('ref_no', $order)
            ->with(
                'paymentType',
                'customer',
                'payment',
                'orderState',
                'deliveryPeriod',
                'selectedAddress',
                'user',
                'salesRepresentative'
            )->first();

        $data['order'] = $order;

        $data['products'] = OrderProduct::with('preparation', 'size', 'cut',  'product.productImages')->where('order_ref_no', $order->ref_no)->get()->map(function ($i) {
            $i->shalwata = $i->shalwata ? true : false;
            return $i;
        });

        return \successResponse($data);
    }

    public function editOrder(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|exists:orders,id',
                'order_state_id' => 'nullable|exists:order_states,code',
                'user_id' => 'nullable|exists:users,id',
                'discount_code' => 'nullable|exists:discounts,code',
                'payment_type_id' => 'nullable|exists:payment_types,id',
                'delivery_date' => 'nullable',
                'delivery_period_id' => 'nullable|exists:delivery_periods,id',
                'paid' => 'nullable|in:0,1',
                'comment' => 'nullable',
                'address' => 'nullable',
                'address_id' => 'nullable',
                'boxes_count' => 'nullable|min:1',
                'dishes_count' => 'nullable|min:1',
                'delivery_fee' => 'nullable|min:1',
                'sales_representative_id' => 'nullable',
                'is_printed' => 'nullable',
            ]);

            $order = Order::where('id', $request->id)->first();

            $data = $request->only(
                'order_state_id',
                'user_id',
                'discount_code',
                'payment_type_id',
                'delivery_date',
                'delivery_period_id',
                'paid',
                'comment',
                'boxes_count',
                'dishes_count',
                'delivery_fee',
                'address',
                'address_id',
                'sales_representative_id',
            );

            if ($request->discount_code && $request->discount_code != $order->applied_discount_code) {

                if ($order->discount_applied) {
                    $order->total_amount_after_discount += $order->discount_applied;
                    $order->total_amount += $order->discount_applied;
                    $order->discount_applied = 0;
                    $order->applied_discount_code = null;
                    $order->save();
                    $order = $order->refresh();
                }

                $discount = $this->handleDiscountAmount($request->discount_code, $order->total_amount);

                $data['applied_discount_code'] = $request->discount_code;
                $data['discount_applied'] = $discount;
                $data['total_amount_after_discount'] = $order->total_amount - $discount;
                $data['total_amount'] = $order->total_amount - $discount;
            }


            if (isset($request->paid)) {
                $data['paid'] = $request->paid ?? 0;

                if ($request->paid && !$order->paid) {

                    $payment = Payment::where('order_ref_no', $order->ref_no)->first();
                    if ($payment) {
                        $payment->update(
                            [
                                'payment_type_id' => $order->payment_type_id,
                                'price' => $order->total_amount_after_discount ?? 0,
                                'status' => 'Paid',
                                'manual' => 1
                            ]
                        );
                    }
                    if (!$payment) {

                        $lastPayment = Payment::latest('id')->first();

                        $payment = Payment::create(
                            [
                                "ref_no" => GetNextPaymentRefNo('SA', $lastPayment != null ? $lastPayment->id + 1 : 1),
                                "customer_id" => $order->customer_id,
                                'order_ref_no' => $order->ref_no,
                                'payment_type_id' => $order->payment_type_id,
                                'price' => $order->total_amount_after_discount ?? 0,
                                'status' => 'Paid',
                                'manual' => 1,
                                "description" => "Payment Created", // need to move to enum class
                            ]
                        );
                    }

                    if ($payment) {
                        $data['payment_id'] = $payment->id ?? null;
                    }
                } else if (!$request->paid) {
                    $payment = Payment::where('order_ref_no', $order->ref_no)->latest()->first();
                    if ($payment  && $order->using_wallet) {

                        $customer = Customer::where('id', $order->customer_id)->first();

                        $customer->wallet += $payment->price;
                    }

                    if ($payment) {
                        $payment->update([
                            'status' => 'Waiting for Client',
                            'price' => 0
                        ]);
                    }
                }
            }

            if ($request->is_printed) {
                $data['printed_at'] = now();
            }
            // if (request('delivery_period')) {
            //     $data['delivery_period_id'] = request('delivery_period');
            // }

            $order->update($data);


            return successResponse($order->refresh(), 'order updated successfully');
        } catch (\Throwable $th) {

            return failResponse([], $th->getMessage());
        }
    }

    public function removeDiscount($id)
    {
        $order = Order::where('id', $id)->first();

        if ($order && !$order->paid) {
            $order->update([
                'applied_discount_code' => null,
                'discount_applied' => 0,
            ]);

            $this->reSumOrderProducts($order->ref_no);
            return successResponse($order->refresh(), 'order updated successfully');
        }

        return failResponse([], 'لقد تم دفع الاوردر');
    }

    public function createOrderForDashboard(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $validated = $request->validate([
                "customer_id" => 'required|exists:customers,id',
                "country_id" => 'required|exists:countries,id',
                "city_id" => 'required|exists:cities,id',
                "comment"  => 'nullable|string',
                "delivery_date" => array('required', 'date'), //'regex:(^(0[1-9]|1[012])\-(0[1-9]|[12][0-9]|3[01])+$)' // 01-29 or 12-29
                "delivery_period_id" => array('required', 'exists:delivery_periods,id'),
                "using_wallet" => 'required|boolean',
                'address_id' => 'required|exists:addresses,id',
                'products' => 'required|array',
                'products.*.quantity' => 'required|min:1',
                'products.*.product_id' => 'required|exists:products,id',
                'products.*.preparation_id' => 'nullable|exists:preparations,id',
                'products.*.size_id' => 'nullable|exists:sizes,id',
                'products.*.cut_id' => 'nullable|exists:cuts,id',
                'discount_code' => 'nullable',
                'notes' => 'nullable',
                'boxes_count' => 'nullable|min:1',
                'dishes_count' => 'nullable|min:1',
            ]);

            $delivery = 0;
            $totalAddonsAmount = 0.0;
            $app = ($request->query('app') == 1 ? 1 : 0);
            $totalItemsAmount = $this->handleOrderProducts($request->products) ?? 0.0;
            $TotalAmountBeforeDiscount = $totalItemsAmount + $totalAddonsAmount ?? 0.0;
            $discountAmount = $this->handleDiscountAmount($request->discount_code ?? null, $TotalAmountBeforeDiscount);
            $TotalAmountAfterDiscount =  round($TotalAmountBeforeDiscount - $discountAmount, 2) ??   0.0;
            $finalTotal = $TotalAmountAfterDiscount +  $delivery;
            $discountCode = $request->discount_code ?? null;
            $walletAmountUsed = 0;
            $customer = Customer::find($request->customer_id);
            $country = Country::find($request->country_id);
            $lastOrder = Order::latest("id")->first();
            $wallet = $customer->wallet ?? 0;

            if ($validated["using_wallet"] == 1 && $customer->wallet == 0) {
                return response()->json([
                    'success' => false, 'data' => [],
                    'message' => 'failed', 'description' => 'your wallet is empty!', 'code' => '400'
                ], 400);
            }

            $address = Address::where(['customer_id' => $request->customer_id, 'id' => $validated["address_id"]])->first();
            if ($address === null) {
                return response()->json([
                    'success' => false, 'data' => [],
                    'message' => 'failed', 'description' => 'invalid address', 'code' => '400'
                ], 400);
            }

            $order = [
                'ref_no' => GetNextOrderRefNo($country->code, $lastOrder != null ? $lastOrder->id + 1 : 1),
                'delivery_fee' => $delivery,
                'order_subtotal' => $TotalAmountBeforeDiscount,
                'total_amount' => $finalTotal,
                'total_amount_after_discount' => $TotalAmountAfterDiscount,
                'total_amount_before_discount' => $TotalAmountBeforeDiscount +  $delivery,
                'discount_applied' => $discountAmount,
                'delivery_date' => $validated["delivery_date"],
                'delivery_period_id' => $validated["delivery_period_id"],
                "comment" => $validated["comment"] ?? null,
                "using_wallet" => $validated["using_wallet"],
                'wallet_amount_used' => $walletAmountUsed,
                "address_id" => $validated["address_id"],
                "address" => '',
                'customer_id' => $request->customer_id,
                'payment_type_id' => 1,
                'applied_discount_code' => $discountCode,
                'version_app' => $app,
                'comment' => $validated['notes'] ?? null,
                'boxes_count' => isset($validated["boxes_count"]) ? $validated["boxes_count"] : 0,
                'dishes_count' => isset($validated["dishes_count"]) ? $validated["dishes_count"] : 0,
                'sales_representative_id' => in_array('store_manager', auth()->user()->roles->pluck('name')->toArray()) ? auth()->user()->id : null,
            ];


            if ($validated["using_wallet"] == 1 && $wallet) {

                if ($TotalAmountAfterDiscount >= $wallet) {
                    $finalTotal = $TotalAmountAfterDiscount - $wallet;
                    $walletAmountUsed = $wallet;
                    $customer->wallet = 0;
                    $customer->save();
                    $order['paid'] = 0;
                } else {
                    $walletAmountUsed = $TotalAmountAfterDiscount;
                    $customer->wallet = $wallet - $TotalAmountAfterDiscount;
                    $customer->save();
                    $finalTotal = 0;
                    $order['paid'] = 1;
                }
            }


            $order = Order::create($order);

            if ($order->using_wallet) {

                $lastPayment = Payment::latest('id')->first();

                $payment = Payment::create(
                    [
                        "ref_no" => GetNextPaymentRefNo('SA', $lastPayment != null ? $lastPayment->id + 1 : 1),
                        "customer_id" => $order->customer_id,
                        'order_ref_no' => $order->ref_no,
                        'payment_type_id' => 8, //wallet
                        'price' => $walletAmountUsed ?? 0,
                        'status' => 'Paid',
                        'manual' => 1,
                        "description" => "Payment Created", // need to move to enum class
                    ]
                );

                if ($payment) {

                    $order->update(['payment_id' =>  $payment->id ?? null, 'payment_type_id' => 8]);
                }
            }

            if (!Payment::where(['order_ref_no' => $order->ref_no])->first()) {

                $lastPayment = Payment::latest('id')->first();

                $payment = Payment::create(
                    [
                        "ref_no" => GetNextPaymentRefNo('SA', $lastPayment != null ? $lastPayment->id + 1 : 1),
                        "customer_id" => $order->customer_id,
                        'order_ref_no' => $order->ref_no,
                        'payment_type_id' => 1, //wallet
                        'price' => 0,
                        'status' => 'NotPaid',
                        'manual' => 1,
                        "description" => "Payment Created", // need to move to enum class
                    ]
                );

                if ($payment) {

                    $order->update(['payment_id' =>  $payment->id ?? null, 'payment_type_id' => 1]);
                }
            }

            $this->storeOrderProducts($request->products, $order);

            return response()->json([
                'success' => true, 'data' => $order,
                'message' => '', 'description' => '', 'code' => '200'
            ], 200);
        });
    }

    public function addOrderProducts(Request $request)
    {
        return DB::transaction(function () use ($request) {

            $request->validate([
                'order_id' => 'required|exists:orders,id',
                "product_id" => 'required|exists:products,id',
                'quantity' => 'required|min:1',
                'preparation_ids' => 'nullable|exists:preparations,id',
                'size_ids' => 'required|exists:sizes,id',
                'cut_ids' => 'nullable|exists:cuts,id',
                'is_kwar3' =>  'nullable|in:1,0',
                'is_Ras' =>  'nullable|in:1,0',
                'is_lyh' =>  'nullable|in:1,0',
                'is_karashah' => 'nullable|in:1,0',
                'shalwata' => 'nullable|in:1,0',
            ]);

            $order = Order::where('id', $request->order_id)->first();

            $products = [
                [
                    'product_id' => $request->product_id,
                    'quantity' => $request->quantity ?? 1,
                    'preparation_id' => $request->preparation_ids,
                    'size_id' => $request->size_ids,
                    'cut_id' => $request->cut_ids,
                    'is_kwar3' =>  $request->is_kwar3 ?? false,
                    'is_Ras' =>  $request->is_Ras ?? false,
                    'is_lyh' =>  $request->is_lyh ?? false,
                    'is_karashah' => $request->is_karashah ?? false,
                    'shalwata' => $request->shalwata ?? false,
                ]
            ];

            if ($request->shalwata) {
                $order->update(['shalwata_id' => 1]);
            }

            $this->storeOrderProducts($products, $order);

            $this->reSumOrderProducts($order->ref_no);

            return successResponse(true, 'success');
        });
    }

    public function storeOrderProducts($products, $order)
    {
        return DB::transaction(function () use ($products, $order) {
            $orderProducts = [];
            foreach ($products as $item) {
                $product = Product::with('shalwata')->find($item['product_id']);

                $product_size = Size::find($item['size_id']);

                if ($product) {
                    $quantity = $item['quantity'] ? $item['quantity'] : 1;

                    array_push($orderProducts, [
                        'order_ref_no' => $order->ref_no,
                        'total_price' => ($product_size ? ($product_size->sale_price ? $product_size->sale_price :  $product_size->price)  * $quantity : 0) + (isset($item['shalwata']) && $item['shalwata'] ? Shalwata::first()->price : 0),
                        'quantity' => $quantity,
                        'product_id' => $product->id,
                        'preparation_id' => $item['preparation_id'] ?? null,
                        'size_id' =>  $item['size_id'] ?? null,
                        'cut_id' =>  $item['cut_id'] ?? null,
                        'is_kwar3' =>   $item['is_kwar3'] ??  false,
                        'is_Ras' =>  $item['is_Ras'] ??  false,
                        'is_lyh' =>  $item['is_lyh'] ??  false,
                        'is_karashah' => $item['is_karashah'] ??  false,
                        'shalwata_id' => isset($item['shalwata']) && $item['shalwata'] ? 1 : null,
                    ]);

                    $product->no_sale += 1;
                    $product->update();

                    if (isset($item['shalwata'])) {
                        $order->update(['shalwata_id' => 1]);
                    }
                }
            }

            return OrderProduct::insert($orderProducts);
        });
    }

    public function editOrderProducts(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $validated = $request->validate([
                "order_product_id" => 'required',
                'quantity' => 'required|min:1',
                'preparation_ids' => 'nullable',
                'size_ids' => 'required|exists:sizes,id',
                'cut_ids' => 'nullable|exists:cuts,id',
                'is_kwar3' =>  'nullable|in:1,0',
                'is_Ras' =>  'nullable|in:1,0',
                'is_lyh' =>  'nullable|in:1,0',
                'is_karashah' => 'nullable|in:1,0',
                'shalwata' => 'nullable|in:1,0',
            ]);

            $OrderProduct = OrderProduct::with('product')->where('id', $request->order_product_id)->first();
            if ($OrderProduct) {
                $product_size = Size::find($validated['size_ids']);

                $product_price = (isset($product_size->sale_price) && $product_size->sale_price > 0 ? $product_size->sale_price  : ($product_size->price ?? 0));

                $OrderProduct->total_price = (($product_price ?? 0) * ($request->quantity ?? 1) +  + ($request->shalwata ? Shalwata::first()->price : 0));
                $OrderProduct->quantity = $request->quantity ?? 1;
                $OrderProduct->preparation_id = $request->preparation_ids ?? null;
                $OrderProduct->size_id = $request->size_ids ?? null;
                $OrderProduct->cut_id = $request->cut_ids ?? null;

                $OrderProduct->is_kwar3 = $request->is_kwar3 ?? false;
                $OrderProduct->is_Ras = $request->is_Ras ?? false;
                $OrderProduct->is_lyh = $request->is_lyh ?? false;
                $OrderProduct->is_karashah = $request->is_karashah ?? false;
                $OrderProduct->shalwata_id = $request->shalwata ?  1  : null;

                $OrderProduct->save();

                $this->reSumOrderProducts($OrderProduct->order_ref_no);

                if ($request->shalwata) {
                    Order::where('ref_no', $OrderProduct->order_ref_no)->first()->update(['shalwata_id' => 1]);
                }
            }
            return successResponse(true, 'success');
        });
    }

    public function deleteOrderProducts(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $validated = $request->validate([
                "order_product_id" => 'required|exists:order_products,id',
            ]);

            $OrderProduct = OrderProduct::with('product', 'order')->where('id', $request->order_product_id)->first();
            $OrderProduct->delete();

            $this->reSumOrderProducts($OrderProduct->order_ref_no);

            return successResponse(true, 'success');
        });
    }

    public function reSumOrderProducts($order_ref)
    {
        $itemAmount = 0;
        $order_products = OrderProduct::where('order_ref_no', $order_ref)->get();

        foreach ($order_products as $item) {
            $itemAmount += $item->total_price;
        }

        $order = Order::where('ref_no', $order_ref)->first();

        $total_amount_after_discount = $order->discount_applied <= $itemAmount ? ($itemAmount - $order->discount_applied) : 0;
        $final_total = $total_amount_after_discount + $order->delivery_fee;
        return $order->update([
            'order_subtotal' => $itemAmount,
            'total_amount' => $final_total,
            'total_amount_after_discount' => $total_amount_after_discount,
        ]);
    }

    public function handleOrderProducts($products)
    {
        $sum = 0;
        foreach ($products as $item) {

            $product = Product::find($item['product_id']);

            $product_size = Size::find($item['size_id']);

            if ($product) {
                $quantity = $item['quantity'] ? $item['quantity'] : 1;
                $product_price = ($product_size->sale_price > 0 ? $product_size->sale_price  : ($product_size->price ?? 0));
                $sum += ($product ? ($product_price ?? 0)  * $quantity : 0);
            }
        }

        return $sum;
    }

    public function handleDiscountAmount($code, $TotalAmountBeforeDiscount)
    {
        $value = 0;
        if ($code) {
            $discount = Discount::where('code', 'like', '%' . $code . '%')->first();
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

    public function getUserOrdersDashboard(Request $request)
    {
        try {
            $order_states = (handleRoleOrderState(auth()->user()->roles->pluck('name')->toArray())['orders']);
        } catch (\Throwable $th) {
            $order_states = null;
        }

        $perPage = 6;
        if ($request->has('per_page'))
            $perPage = $request->get('per_page');

        if ($perPage == 0)
            $perPage = 6;

        $orders = DB::table('orders')
            ->select(
                'orders.*',
                'customers.name as customer_name',
                'customers.mobile as customer_mobile',
                'order_states.state_ar as order_state_ar',
                'order_states.state_en as order_state_en',
                'shalwatas.name_ar as shalwata_name',
                'shalwatas.price as shalwata_price',
                'payment_types.name_ar as payment_type_name',
                'delivery_periods.name_ar as delivery_period_name',
                'delivery_periods.time_hhmm as delivery_period_time',
                'payments.price as payment_price',
                'payments.status as payment_status',
                'addresses.address as address_address',
                'addresses.lat as address_lat',
                'addresses.long as address_long',
                'addresses.country_id as address_country_id',
                'addresses.city_id as address_city_id',
                'cities.name_ar as city_name',
            )
            ->join('customers', 'customers.id', '=', 'orders.customer_id')
            ->leftJoin('order_states', 'order_states.code', '=', 'orders.order_state_id')
            ->leftJoin('shalwatas', 'shalwatas.id', '=', 'orders.shalwata_id')
            ->leftJoin('payment_types', 'payment_types.id', '=', 'orders.payment_type_id')
            ->leftJoin('delivery_periods', 'delivery_periods.id', '=', 'orders.delivery_period_id')
            ->leftJoin('payments', 'payments.id', '=', 'orders.payment_id')
            ->leftJoin('addresses', 'addresses.id', '=', 'orders.address_id')
            ->leftJoin('cities', 'cities.id', '=', 'addresses.city_id');

        $orders = $orders->where('orders.user_id', request('user_id', auth()->user()->id));

        if ($order_states) {
            $orders = $orders->whereIn('orders.order_state_id', $order_states ?? []);
        }
        if (request('city_ids')) {
            $orders = $orders->whereIn('addresses.city_id', request('city_ids'));
        }
        if (request('country_ids')) {
            $orders = $orders->where('addresses.country_id', request('country_ids'));
        }
        if (request('order_state_ids')) {
            $orders = $orders->whereIn('orders.order_state_id', request('order_state_ids'));
        }
        if (request('date_from') && request('date_to')) {
            $orders = $orders->where(function ($q) {
                $q->whereDate('orders.delivery_date', ">=", request('date_from'))->whereDate('orders.delivery_date', "<=", request('date_to'));
            });
        } else {
            $orders = $orders->whereDate('orders.delivery_date', date('Y-m-d'));
        }

        if (request('delivery_date')) {
            $orders = $orders->where('orders.delivery_date', date('Y-m-d', strtotime(request('delivery_date'))));
        }
        if (request('delivery_period_id')) {
            $orders = $orders->where('orders.delivery_period_id', request('delivery_period_id'));
        }
        if (request('customer_id')) {
            $orders = $orders->where('orders.customer_id', request('customer_id'));
        }
        if (request('mobile')) {
            $orders = $orders->where('customers.mobile', request('mobile'));
        }

        $orders = $orders->orderBy('id', 'desc')->paginate($perPage);

        $items =  $orders->toArray()['data'];
        $items = collect($items)->map(function ($i) {

            $per = 1.15;
            if ($i->address_country_id == 4) {
                $per = 1.05;
            }

            $i->total_amount_after_tax = $i->total_amount_after_discount ? round($i->total_amount_after_discount / $per, 2) : 0;
            $i->tax_fees = round(($i->total_amount_after_discount ?? 0) - ($i->total_amount_after_tax  ?? 0), 2);
            $i->remain_amount = $i->payment_price ? ($i->total_amount_after_discount - $i->payment_price) : $i->total_amount_after_discount ?? 0;
            return $i;
        });

        $orders->data = $items;

        return response()->json([
            'success' => true, 'data' => $orders,
            'message' => 'retrieved successfully', 'description' => '', 'code' => '200'
        ], 200);
    }
    /********************************************************************************************************** */
    public function getOrders(Request $request)
    {
        $orders = Order::where('customer_id', auth()->user()->id)
            ->with('orderProducts.product', 'orderState', 'deliveryPeriod', 'selectedAddress')
            ->orderBy('id', 'desc')->take(30)->get();

        return response()->json([
            'success' => true, 'data' => $orders,
            'message' => 'Products retrieved successfully', 'description' => '', 'code' => '200'
        ], 200);
    }

    public function getOrdersV2(Request $request)
    {
        $perPage = 6;
        if ($request->has('per_page'))
            $perPage = $request->get('per_page');

        if ($perPage == 0)
            $perPage = 6;

        $orders = Order::where('customer_id', auth()->user()->id)
            ->with('orderProducts', 'orderState', 'deliveryPeriod', 'selectedAddress')
            ->orderBy('id', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true, 'data' => $orders,
            'message' => 'Products retrieved successfully', 'description' => "", 'code' => '200'
        ], 200);
    }

    public function getOrderByRefNo($order)
    {
        $order = Order::where(['customer_id' => auth()->user()->id, 'ref_no' => $order])
            ->with('orderProducts', 'orderState', 'deliveryPeriod', 'selectedAddress')->get()->first();

        if ($order != null)
            return response()->json([
                'success' => true, 'data' => $order,
                'message' => 'Products retrieved successfully', 'description' => '', 'code' => '200'
            ], 200);
        else
            return response()->json([
                'success' => false, 'data' => null,
                'message' => 'order not found!', 'description' => '', 'code' => '404'
            ], 404);
    }

    public function createOrder(Request $request)
    {
        // TraceError::create(['class_name' => "create order 351", 'method_name' => "Get_Payment_Status", 'error_desc' => json_encode($request->all())]);
        $customer = Customer::find(auth()->user()->id);

        // if ($customer->integrate_id == null) {
        //     TraceError::create(['class_name' => "CallOrderNetsuiteApi::responce", 'method_name' => "sendOrderToNS 1", 'error_desc' => json_encode($customer)]);
        //     $this->registerNSS($customer, $request);
        // } else {
        //     TraceError::create(['class_name' => "CallOrderNetsuiteApi::responce", 'method_name' => "sendOrderToNS 2", 'error_desc' => json_encode($customer)]);
        // }


        $app = ($request->query('app') == 1 ? 1 : 0);
        $point = $request->query('longitude') . " " . $request->query('latitude');
        $countryId = $request->query('countryId');
        $country = Country::where('code', $countryId)->get()->first();

        if ($country === null)
            return response()->json([
                'data' => [],
                'success' => true, 'message' => 'success', 'description' => 'this service not available in your country!', 'code' => '200'
            ], 200);

        $currentCity = app(PointLocation::class)->getLocatedCity($country, $point);

        if ($currentCity === null)
            return response()->json([
                'data' => [],
                'success' => true, 'message' => 'success', 'description' => 'this service not available in your city!', 'code' => '200'
            ], 200);

        $validated = $request->validate([
            "comment"  => 'nullable|string',
            "delivery_date" => 'required|date',
            // "delivery_date" => array('required', 'regex:(^(0[1-9]|1[012])\-(0[1-9]|[12][0-9]|3[01])+$)'), // 01-29 or 12-29
            "delivery_period_id" => array('required', 'exists:delivery_periods,id'),
            "payment_type_id" => 'required|exists:payment_types,id',
            "using_wallet" => 'required|boolean',
            'address_id' => 'required|exists:addresses,id',
            'tamara_payment_name' => array('required_if:payment_type_id,==,4', 'in:PAY_BY_INSTALMENTS,PAY_BY_LATER'), // add 'tamara in payment_types table with id 4
            'no_instalments' => array('required_if:tamara_payment_name,==,PAY_BY_INSTALMENTS', 'numeric')
        ]);

        if (!isset($validated["comment"])) {
            $validated["comment"] = null;
        }

        if ($validated["using_wallet"] == 1 && $customer->wallet == 0) {
            return response()->json([
                'success' => false, 'data' => [],
                'message' => 'failed', 'description' => 'your wallet is empty!', 'code' => '400'
            ], 400);
        }

        $deliveryDate = DeliveryDate::where('date', $validated["delivery_date"])->get()->first();
        //if null, means allowed date, period.
        if ($deliveryDate != null) {

            $deliveryPeriodCity = DeliveryDateCity::where([
                ['city_id', $currentCity->id],
                ['delivery_date', $deliveryDate],
                ['delivery_period_id', $validated['delivery_period_id']]
            ])->get()->first();

            if ($deliveryPeriodCity != null)
                return response()->json([
                    'success' => false, 'data' => [],
                    'message' => 'failed', 'description' => 'select valid delivery date/period!', 'code' => '400'
                ], 400);
        }


        $cart = Cart::where([['customer_id', auth()->user()->id], ['city_id', $currentCity->id]])->get();

        if (count($cart) == 0) {
            return response()->json([
                'success' => false, 'data' => [],
                'message' => 'failed', 'description' => 'add itmes to your cart first!', 'code' => '400'
            ], 400);
        }

        $address = Address::where([['customer_id', auth()->user()->id], ['id', $validated["address_id"]]])->get()->first();
        if ($address === null)
            return response()->json([
                'success' => false, 'data' => [],
                'message' => 'failed', 'description' => 'invalid address', 'code' => '400'
            ], 400);

        if ($address->city_id != $currentCity->id) {
            return response()->json([
                'success' => false, 'data' => [],
                'message' => 'failed', 'description' => 'invalid address, your location does not match with your selected address!', 'code' => '400'
            ], 400);
        }


        $shalwata = Shalwata::first();
        $totalItemsAmount = 0.0;
        $totalAddonsAmount = 0.0;
        $TotalAmountBeforeDiscount = 0.0;
        $TotalAmountAfterDiscount = 0.0;
        $orderProducts = [];
        $discountCode = null;
        $discountAmount = 0;
        $comment = null;

        $applied_discount_code = $cart[0]['applied_discount_code'];
        list($cartProduct, $discountCode, $totalAddonsAmount, $totalItemsAmount, $orderProducts) = $this->calculateProductsAmount($cart, $applied_discount_code, $shalwata, $totalAddonsAmount, $totalItemsAmount, $orderProducts);

        $TotalAmountBeforeDiscount = $totalAddonsAmount + $totalItemsAmount;

        $miniOrderValue = MinOrder::where("country_id", $country->id)->get()->first();

        $minOrderPerCity = MinOrder::where('city_id', $currentCity->id)->first();

        if ($miniOrderValue != null && $miniOrderValue->min_order > $TotalAmountBeforeDiscount)
            return response()->json([
                'success' => false, 'data' => [],
                'message' => 'failed', 'description' => "minimum order value should be more that or equal $miniOrderValue->min_order $country->currency_en!", 'code' => '400'
            ], 400);

        if ($minOrderPerCity != null && $minOrderPerCity->min_order > $TotalAmountBeforeDiscount)
            return response()->json([
                'success' => false, 'data' => [],
                'message' => 'failed', 'description' => "minimum order value should be more that or equal $miniOrderValue->min_order $country->currency_en!", 'code' => '400'
            ], 400);

        // TraceError::create(['class_name' => "Create Order", 'method_name' => "before coupon 1", 'error_desc' => json_encode($customer)]);
        $applicableProductIds = [];
        if ($discountCode != null) {
            list($couponValid, $discountAmount, $TotalAmountAfterDiscount, $couponValidatingResponse, $applicableProductIds) = app(CouponController::class)->discountProcess($discountCode, $cartProduct, $TotalAmountBeforeDiscount, $discountAmount, $TotalAmountAfterDiscount, $country->id, $currentCity->id);
            if ($couponValid == null) {
                return response()->json([
                    'success' => false, 'data' => Cart::where('customer_id', auth()->user()->id)->get(),
                    'message' => $couponValidatingResponse[0] . ":" . $couponValidatingResponse[1], 'description' => 'invalid coupon used', 'code' => '400'
                ], 400);
            }
        } else {
            $TotalAmountAfterDiscount = $TotalAmountBeforeDiscount;
        }

        $customer = Customer::find(auth()->user()->id);
        #$delivery = DeliveryFee::where('city_id', $currentCity->id)->get()->first();
        $delivery = 0;
        $walletAmountUsed = 0;
        $wallet = $customer->wallet;
        $TotalAmountAfterDiscount = $TotalAmountAfterDiscount + $delivery;
        if ($validated["using_wallet"] == 1) {

            if ($TotalAmountAfterDiscount >= $wallet) {
                $TotalAmountAfterDiscount = $TotalAmountAfterDiscount - $wallet;
                $walletAmountUsed = $wallet;
                $customer->wallet = 0;
                $customer->save();
            } else {
                $walletAmountUsed = $TotalAmountAfterDiscount;
                $customer->wallet = $wallet - $TotalAmountAfterDiscount;
                $customer->save();
                $TotalAmountAfterDiscount = 0;
            }
        }

        // TraceError::create(['class_name' => "Create Order", 'method_name' => "after discountProcess", 'error_desc' => json_encode($applicableProductIds)]);

        $lastOrder = Order::latest("id")->first();
        $order = [
            'ref_no' => GetNextOrderRefNo($country->code, $lastOrder != null ? $lastOrder->id + 1 : 1),
            'delivery_fee' => $delivery,
            'order_subtotal' => $TotalAmountBeforeDiscount,
            'total_amount' => $TotalAmountBeforeDiscount + $delivery,
            'total_amount_after_discount' => $TotalAmountAfterDiscount,
            'discount_applied' => $discountAmount,
            'delivery_date' => $validated["delivery_date"],
            'delivery_period_id' => $validated["delivery_period_id"],
            "comment" => $validated["comment"],
            "using_wallet" => $validated["using_wallet"],
            'wallet_amount_used' => $walletAmountUsed,
            "address_id" => $validated["address_id"],
            "address" => '',
            'customer_id' => auth()->user()->id,
            'payment_type_id' => $validated['payment_type_id'],
            'applied_discount_code' => $discountCode,
            'version_app' => $app
            // "integrate_id" => 0
        ];

        $createdOrder = Order::create($order);

        foreach ($orderProducts as $orderProduct) {
            $orderProduct['order_ref_no'] = $createdOrder->ref_no;
            OrderProduct::create($orderProduct);

            $saled = Product::find($orderProduct['product_id']);
            $saled->no_sale += 1;
            $saled->update();
        }



        Cart::where([['customer_id', auth()->user()->id], ['city_id', $currentCity->id]])->delete();

        if ($discountCode != null) {
            TempCouponProducts::create([
                "order_id" => $createdOrder->ref_no,
                "coupon_code" => $discountCode,
                "product_ids" => json_encode($applicableProductIds)
            ]);
        }

        $paymentType = PaymentType::find($validated['payment_type_id']);

        if ($paymentType->code === "COD" || $TotalAmountAfterDiscount == 0) { // cod

            try {

                $lastPayment = Payment::latest('id')->first();

                $payment = Payment::create(
                    [
                        "ref_no" => GetNextPaymentRefNo('SA', $lastPayment != null ? $lastPayment->id + 1 : 1),
                        "customer_id" => $createdOrder->customer_id,
                        'order_ref_no' => $createdOrder->ref_no,
                        'payment_type_id' => 1, //wallet
                        'price' => 0,
                        'status' => 'NotPaid',
                        'manual' => 1,
                        "description" => "Payment Created", // need to move to enum class
                    ]
                );

                if ($payment) {

                    $createdOrder->update(['payment_id' =>  $payment->id ?? null, 'payment_type_id' => 1]);
                }
                //code...
            } catch (\Throwable $th) {
                //throw $th;
            }

            // $res = app(CallOrderNetsuiteApi::class)->sendOrderToNS($order, $request);

            // $res2 = $res->custrecord_trk_order_saleorder->internalid;

            // if ($res != null && $res2 != null && !isset($res->status)) {

            //     $orderToNS = Order::Find($order['ref_no']);

            //     $orderToNS->update(['integrate_id' => $res->id]);

            //     $orderToNS->update(['saleOrderId' => $res2]);
            // }
            return response()->json([
                'success' => true, 'data' => $createdOrder,
                'message' => '', 'description' => '', 'code' => '200'
            ], 200);
        } else {

            if ($country->code == 'SA' && $paymentType->code === "ARB") {

                // $paymentRes = app(MyFatoorahApiService::class)->Set_Payment_myfatoora($customer, $createdOrder, $paymentType, $country, 'KSA');

                if ($paymentType->active === 1) {
                    $paymentRes = app(MyFatoorahApiService::class)->Set_Payment_myfatoora($customer, $createdOrder, $paymentType, $country, 'KSA');
                } else {
                    return response()->json([
                        'success' => true, 'data' => $createdOrder,
                        'message' => '', 'description' => '', 'code' => '200'
                    ], 200);
                }
                // $res = app(CallOrderNetsuiteApi::class)->sendOrderToNS($order, $request);

                // $res2 = $res->custrecord_trk_order_saleorder->internalid;

                // if ($res != null && $res2 != null && !isset($res->status)) {

                //     $orderToNS = Order::Find($order['ref_no']);

                //     $orderToNS->update(['integrate_id' => $res->id]);

                //     $orderToNS->update(['saleOrderId' => $res2]);
                // }

                return response()->json([
                    'success' => true, 'data' => $paymentRes,
                    'message' => '', 'description' => '', 'code' => '200'
                ], 200);

                // $paymentRes = app(AlRajhiPaymentService::class)->createARBpayment($customer, $createdOrder, $paymentType, $country);

                // if ($paymentRes['success'] == true){

                //     $res = app(CallOrderNetsuiteApi::class)->sendOrderToNS($order , $request);

                //     // if(isset($res->error) && $res->error->code='UNIQUE_CUST_ID_REQD'){

                //     //     $res = app(CallNetsuiteApi::class)->sendCustomerToNS($customer , $request);

                //     //     if(!isset($res->status)){
                //     //         $customer->update(['integrate_id' => $res->id]);

                //     //         $res = app(CallOrderNetsuiteApi::class)->sendOrderToNS($order , $request);
                //     //     }
                //     // }

                //     $res2 = $res->custrecord_trk_order_saleorder->internalid;

                //     if($res != null && $res2 != null && !isset($res->status)){

                //         $orderToNS = Order::Find($order['ref_no']);

                //         $orderToNS->update(['integrate_id' => $res->id]);

                //         $orderToNS->update(['saleOrderId'=> $res2]);
                //     }

                //     return response()->json(['success' => true, 'data' => $paymentRes,
                //         'message' => '', 'description' => '', 'code' => '200'], 200);
                // }else {
                //     return response()->json(['success' => false, 'data' => $paymentRes,
                //         'message' => '', 'description' => 'something went wrong, contact support!', 'code' => '400'], 400);
                // }
            } elseif ($paymentType->code === "tamara") {

                if (isset($validated['no_instalments'])) {
                    $paymentRes = app(TamaraApiService::class)->checkoutTamara($customer, $address, $createdOrder, $validated['tamara_payment_name'], $country, $validated['no_instalments']);

                    // $res = app(CallOrderNetsuiteApi::class)->sendOrderToNS($order, $request);


                    // $res2 = $res->custrecord_trk_order_saleorder->internalid;

                    // if ($res != null && $res2 != null && !isset($res->status)) {

                    //     $orderToNS = Order::Find($order['ref_no']);

                    //     $orderToNS->update(['integrate_id' => $res->id]);

                    //     $orderToNS->update(['saleOrderId' => $res2]);
                    // }
                } else {
                    $paymentRes = app(TamaraApiService::class)->checkoutTamara($customer, $address, $createdOrder, $validated['tamara_payment_name'], $country);
                }


                return response()->json([
                    'success' => true, 'data' => $paymentRes,
                    'message' => '', 'description' => '', 'code' => '200'
                ], 200);
            } elseif ($paymentType->code === "tamara-v2") {

                if (isset($validated['no_instalments'])) {
                    $paymentRes = app(TamaraApiServiceV2::class)->checkoutTamara($customer, $address, $createdOrder, $validated['tamara_payment_name'], $country, $validated['no_instalments']);

                    //   $res = app(CallOrderNetsuiteApi::class)->sendOrderToNS($order , $request);


                    // $res2 = $res->custrecord_trk_order_saleorder->internalid;

                    // if($res != null && $res2 != null && !isset($res->status)){

                    //     $orderToNS = Order::Find($order['ref_no']);

                    //     $orderToNS->update(['integrate_id' => $res->id]);

                    //     $orderToNS->update(['saleOrderId'=> $res2]);
                    // }

                } else {
                    $paymentRes = app(TamaraApiServiceV2::class)->checkoutTamara($customer, $address, $createdOrder, $validated['tamara_payment_name'], $country);
                }


                return response()->json([
                    'success' => true, 'data' => $paymentRes,
                    'message' => '', 'description' => '', 'code' => '200'
                ], 200);
            } elseif ($country->code == 'AE' && $paymentType->code === "ARB") {

                //   $paymentRes = app(NgeniusPaymentService::class)->createNgeniusPayment($customer, $createdOrder, $paymentType, $country);

                //     $res = app(CallOrderNetsuiteApi::class)->sendOrderToNS($order , $request);

                //     $res2 = $res->custrecord_trk_order_saleorder->internalid;

                //     if($res != null && $res2 != null && !isset($res->status)){

                //         $orderToNS = Order::Find($order['ref_no']);

                //         $orderToNS->update(['integrate_id' => $res->id]);

                //         $orderToNS->update(['saleOrderId'=> $res2]);
                //     }

                //     return response()->json(['success' => true, 'data' => $paymentRes,
                //         'message' => '', 'description' => '', 'code' => '200'], 200);

                $paymentRes = app(MyFatoorahApiService::class)->Set_Payment_myfatoora($customer, $createdOrder, $paymentType, $country);

                // $res = app(CallOrderNetsuiteApi::class)->sendOrderToNS($order, $request);

                // $res2 = $res->custrecord_trk_order_saleorder->internalid;

                // if ($res != null && $res2 != null && !isset($res->status)) {

                //     $orderToNS = Order::Find($order['ref_no']);

                //     $orderToNS->update(['integrate_id' => $res->id]);

                //     $orderToNS->update(['saleOrderId' => $res2]);
                // }

                return response()->json([
                    'success' => true, 'data' => $paymentRes,
                    'message' => '', 'description' => '', 'code' => '200'
                ], 200);
            } elseif ($paymentType->code === "Tabby") {

                $paymentRes = app(TabbyApiService::class)->createManualPayment($customer, $address, $createdOrder, $country);

                // $res = app(CallOrderNetsuiteApi::class)->sendOrderToNS($order, $request);

                // $res2 = $res->custrecord_trk_order_saleorder->internalid;

                // if ($res != null && $res2 != null && !isset($res->status)) {

                //     $orderToNS = Order::Find($order['ref_no']);

                //     $orderToNS->update(['integrate_id' => $res->id]);

                //     $orderToNS->update(['saleOrderId' => $res2]);
                // }

                return response()->json([
                    'success' => true, 'data' => $paymentRes,
                    'message' => '', 'description' => '', 'code' => '200'
                ], 200);
            } elseif ($paymentType->code === "Ngenius") {

                $paymentRes = app(NgeniusPaymentService::class)->createNgeniusPayment($customer, $createdOrder, $paymentType, $country);
                TraceError::create(['class_name' => "OrderController", 'method_name' => 'create ngenius payment', 'error_desc' => '-' . $order['ref_no'] . '- sending order to Netsuite :' . json_encode($createdOrder)]);

                // $res = app(CallOrderNetsuiteApi::class)->sendOrderToNS($order, $request);
                // TraceError::create(['class_name' => "OrderController", 'method_name' => 'create ngenius payment', 'error_desc' => '-' . $order['ref_no'] . '- sent order to Netsuite :' . json_encode($res)]);

                // $res2 = $res->custrecord_trk_order_saleorder->internalid;

                // if ($res != null && $res2 != null && !isset($res->status)) {

                //     $orderToNS = Order::Find($order['ref_no']);

                //     $orderToNS->update(['integrate_id' => $res->id]);

                //     $orderToNS->update(['saleOrderId' => $res2]);
                // }

                return response()->json([
                    'success' => true, 'data' => $paymentRes,
                    'message' => '', 'description' => '', 'code' => '200'
                ], 200);
            } else {

                try {

                    $lastPayment = Payment::latest('id')->first();

                    $payment = Payment::create(
                        [
                            "ref_no" => GetNextPaymentRefNo('SA', $lastPayment != null ? $lastPayment->id + 1 : 1),
                            "customer_id" => $createdOrder->customer_id,
                            'order_ref_no' => $createdOrder->ref_no,
                            'payment_type_id' => 1, //wallet
                            'price' => 0,
                            'status' => 'NotPaid',
                            'manual' => 1,
                            "description" => "Payment Created", // need to move to enum class
                        ]
                    );

                    if ($payment) {

                        $createdOrder->update(['payment_id' =>  $payment->id ?? null, 'payment_type_id' => 1]);
                    }
                    //code...
                } catch (\Throwable $th) {
                    //throw $th;
                }

                TraceError::create(['class_name' => "create order 351", 'method_name' => "Get_Payment_Status", 'error_desc' => json_encode($createdOrder)]);
                return response()->json([
                    'success' => false, 'data' => $createdOrder,
                    'message' => 'Please, contact support with ref: ' . $createdOrder->ref_no, 'description' => '', 'code' => '400'
                ], 400);
            }
        }
    }

    public function registerNSS($customer, Request $request)
    {

        if ($customer->name == '') {
            $customer->update(['name' => 'user' . $customer->id]);
        };

        // $res = app(CallNetsuiteApi::class)->sendCustomerToNS($customer, $request);

        // if ($res != null) {
        //     $customer->update(['integrate_id' => $res->id]);
        //     return 1;
        // } else {
        return 0;
        // }
    }

    public function calculateProductsAmount($cart, $discountCode, $shalwata, $totalAddonsAmount, $totalItemsAmount, array $orderProducts): array
    {
        TraceError::create(['class_name' => "orderController::consumer sent data360", 'method_name' => "checkValidation", 'error_desc' => json_encode($discountCode)]);
        foreach ($cart as $cartProduct) {
            $product = $cartProduct->product;
            $itemsAmount = 0.0;
            $addonsAmount = 0.0;
            //   $discountCode = $cartProduct->applied_discount_code;
            $comment = $cartProduct->comment;
            TraceError::create(['class_name' => "orderController::consumer sent data368", 'method_name' => "checkValidation", 'error_desc' => json_encode($discountCode)]);
            if ($cartProduct->preparation_id != null && $product->productPreparations()->find($cartProduct->preparation_id) != null) {
                $addonsAmount = $addonsAmount + ($cartProduct->quantity * $cartProduct->preparation->price);
            }

            if ($cartProduct->size_id != null && $product->productSizes()->find($cartProduct->size_id) != null) {
                $addonsAmount = $addonsAmount + ($cartProduct->quantity * $cartProduct->size->sale_price);
            }

            if ($cartProduct->cut_id != null && $product->productCuts()->find($cartProduct->cut_id) != null) {
                $addonsAmount = $addonsAmount + ($cartProduct->quantity * $cartProduct->cut->price);
            }

            if ($cartProduct->is_shalwata == 1 && $product->is_shalwata == 1) {
                $addonsAmount = $addonsAmount + ($cartProduct->quantity * $shalwata->price);
            }

            $totalAddonsAmount = $totalAddonsAmount + $addonsAmount;
            $totalItemsAmount = $totalItemsAmount + $itemsAmount;

            array_push($orderProducts, [
                'total_price' => $itemsAmount + $addonsAmount,
                'quantity' => $cartProduct->quantity,
                'product_id' => $product->id,
                'preparation_id' => $cartProduct->preparation_id,
                'size_id' => $cartProduct->size_id,
                'cut_id' => $cartProduct->cut_id,
                'is_kwar3' => $cartProduct->is_kwar3,
                'is_Ras' => $cartProduct->is_Ras,
                'is_lyh' => $cartProduct->is_lyh,
                'is_karashah' => $cartProduct->is_karashah,
                'shalwata_id' => $cartProduct->is_shalwata == 1 && $product->is_shalwata == 1 ? $shalwata->id : null
            ]);
        }
        return array($cart, $discountCode, $totalAddonsAmount, $totalItemsAmount, $orderProducts);
    }

    public function getTotalProductsAmount($cart, $shalwata): array
    {
        $totalAddonsAmount = 0.0;
        $totalItemsAmount = 0.0;
        foreach ($cart as $cartProduct) {
            $product = $cartProduct->product;

            if ($cartProduct->size_id != null && $product->productSizes()->find($cartProduct->size_id) != null) {
                $totalItemsAmount = $totalItemsAmount + ($cartProduct->quantity * $cartProduct->size->sale_price);
            }

            if ($cartProduct->preparation_id != null && $product->productPreparations()->find($cartProduct->preparation_id) != null) {
                $totalAddonsAmount = $totalAddonsAmount + ($cartProduct->quantity * $cartProduct->preparation->price);
            }

            if ($cartProduct->cut_id != null && $product->productCuts()->find($cartProduct->cut_id) != null) {
                $totalAddonsAmount = $totalAddonsAmount + ($cartProduct->quantity * $cartProduct->cut->price);
            }

            if ($cartProduct->is_shalwata == 1 && $product->is_shalwata == 1) {
                $totalAddonsAmount = $totalAddonsAmount + ($cartProduct->quantity * $shalwata->price);
            }
        }

        return array($totalItemsAmount, $totalAddonsAmount);
    }

    public function saveCouponForNSOrder($orderId, $discountCode, $applicableProductIds): void
    {
        TempCouponProducts::create([
            "order_id" => $orderId,
            "coupon_code" => $discountCode,
            "product_ids" => json_encode($applicableProductIds)
        ]);
    }
}
