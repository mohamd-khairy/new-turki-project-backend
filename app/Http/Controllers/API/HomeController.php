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

    public function handleCartChartObject()
    {
        DB::statement('SET sql_mode = " "');

        $data = DB::table('carts')
            ->select('customers.name', 'customers.mobile', DB::raw('GROUP_CONCAT(products.name_ar SEPARATOR "-") as product_names'), 'carts.quantity', 'cities.name_ar as city_name', 'carts.created_at')
            ->leftJoin('customers', 'carts.customer_id', '=', 'customers.id')
            ->leftJoin('products', 'carts.product_id', '=', 'products.id')
            ->leftJoin('cities', 'cities.id', '=', 'carts.city_id');

        if (request('date_from') && request('date_to')) {
            $data = $data->whereBetween('carts.created_at', [request('date_from'), request('date_to')]);
        }

        if (request('country_id')) {
            $data = $data->where('cities.country_id', request('country_id'));
        }

        $data = $data->groupBy('customers.name')
            ->orderBy('carts.created_at', 'desc')
            ->paginate(request('per_page', 10));

        return successResponse($data);
    }

    public function handleProfitChartObject()
    {
        DB::statement('SET sql_mode = " "');

        $data = DB::table('order_products')
            ->select(
                DB::raw('MONTH(order_products.created_at) as month'), // Extract month from created_at
                DB::raw('YEAR(order_products.created_at) as year'),   // Extract year from created_at
                DB::raw('SUM(sizes.sale_price) as total_sale_price'),
                DB::raw('SUM(COALESCE(stocks.price / stocks.quantity, 0)) as total_buy_price'), // Handle null stock price
                DB::raw('SUM(sizes.sale_price - COALESCE(stocks.price / stocks.quantity, 0)) as total_profit')  // Sum price difference and handle null stock
            )
            ->join('orders', 'orders.ref_no', '=', 'order_products.order_ref_no')
            ->join('sizes', 'sizes.id', '=', 'order_products.size_id')
            ->leftJoin('stocks', 'stocks.product_id', '=', 'order_products.product_id') // Changed to left join
            ->whereNotNull('order_products.created_at') // Ensure created_at is not null
            ->where('orders.order_state_id', 200); // Ensure order_state_id is 200

        // Conditional filtering by country_id
        if (request('country_id') == 4) {
            $data = $data->where('orders.ref_no', 'like', 'AE%');
        } else {
            $data = $data->where('orders.ref_no', 'like', 'SA%');
        }

        // Filter by product_id if present
        if (request('product_id')) {
            $data = $data->where('order_products.product_id', request('product_id'));
        }

        // Date range filter
        if (request('date_from') && request('date_to')) {
            $data = $data->whereBetween('order_products.created_at', [request('date_from'), request('date_to')]);
        }

        $data = $data->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->groupBy(DB::raw('YEAR(order_products.created_at)'), DB::raw('MONTH(order_products.created_at)'))  // Group by year and month
            ->paginate(request('per_page', 10));


        return successResponse($data);
    }

    public function handleProductChartObject()
    {
        DB::statement('SET sql_mode = " "');

        $data = DB::table('orders')
            ->select(
                DB::raw('CONCAT(products.name_ar, " - ", sizes.name_ar) as name'),
                DB::raw('SUM(order_products.quantity) as total_quantity'),
                DB::raw('SUM(order_products.total_price) as total_price')
            )
            ->leftJoin('order_products', 'orders.ref_no', '=', 'order_products.order_ref_no')
            ->leftJoin('products', 'products.id', '=', 'order_products.product_id')
            ->leftJoin('sizes', 'sizes.id', '=', 'order_products.size_id');

        if (request('product_id')) {
            $data = $data->where('products.id', request('product_id'));
        }

        if (request('category_id')) {
            $data = $data->where('products.category_id', request('category_id'));
        }

        if (request('country_id') == 4) {
            $data = $data->where('orders.order_state_id', 101)->where('orders.ref_no', 'like', 'AE%');
        } else {
            $data = $data->where('orders.order_state_id', 200)->where('orders.ref_no', 'like', 'SA%');
        }

        if (request('date_from') && request('date_to')) {
            $data = $data->whereBetween('order_products.created_at', [request('date_from'), request('date_to')]);
        }

        $data = $data->groupBy(DB::raw('CONCAT(products.name_ar, " - ", sizes.name_ar)')) // Group by concatenated name
            ->paginate(request('per_page', 10));

        return successResponse($data);
    }
}
