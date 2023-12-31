<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\Dashboard\OrderResource;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderState;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{

    public function GetOrderStatus()
    {
        try {
            $order_states = (handleRoleOrderState(auth()->user()->roles->pluck('name')->toArray())['status']);
            return successResponse(OrderState::where('is_active', 1)->whereIn('code', $order_states)->get());
        } catch (\Throwable $th) {
            $order_states = null;
            return successResponse(OrderState::where('is_active', 1)->get());
        }
    }

    public function dashboard()
    {
        $product_count = Product::where('is_active', 1)->count();
        $customer_count = Customer::count();
        $customer_wallet_sum = Customer::sum('wallet');
        $order_count = Order::count(); //where('order_state_id', 100)->
        $payment_sum = Payment::where('status', 'Paid')->sum('price');
        $orders = Order::with('orderState', 'paymentType')->where('order_state_id', 100)->orderBy('id', 'desc')->take(6)->get();
        $monthly_orders = DB::table('orders')->select(
            DB::raw("CAST(DATE_FORMAT(created_at, '%c') AS SIGNED) as month"),
            DB::raw("COUNT(id) as count")
        )->groupBy('month')->get();

        $data['total']['product_count'] = (int)$product_count;
        $data['total']['customer_count'] = (int)$customer_count;
        $data['total']['order_count'] = (int)$order_count;
        $data['total']['payment_sum'] = (float)$payment_sum;
        $data['total']['wallet_sum'] = (float)$customer_wallet_sum;


        $data['orders'] = OrderResource::collection($orders);
        $data['monthly_orders'] = $this->handleMonthlyOrderObject($monthly_orders);

        return successResponse($data);
    }


    public function handleMonthlyOrderObject($monthly_orders)
    {
        $months = $monthly_orders->pluck('month')->toArray();
        collect([1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12])->each(function ($month) use ($months, &$monthly_orders) {
            if (!in_array($month, $months)) {
                $monthly_orders[] = ['month' => $month, 'count' => 0];
            }
        });

        return $monthly_orders->sortBy('month')->values()->all();
    }
}
