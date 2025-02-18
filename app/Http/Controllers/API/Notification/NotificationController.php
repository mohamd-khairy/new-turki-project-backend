<?php

namespace App\Http\Controllers\API\Notification;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Cart;
use App\Models\Customer;
use App\Models\Notification;
use App\Models\StaticNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $data = Notification::with('customer')
            ->when(request('customer_id'), function ($query) {
                $query->where('customer_id', request('customer_id'));
            })
            ->when(request('start_date'), function ($query) {
                $query->whereDate('created_at', '>=', request('start_date'));
            })
            ->when(request('end_date'), function ($query) {
                $query->whereDate('created_at', '<=', request('end_date'));
            })
            ->latest('id')
            ->paginate(request('per_page', 10));

        return successResponse($data);
    }

    public function destroy($id)
    {
        return successResponse(Notification::where('id', $id)->delete());
    }

    public function show(Request $request)
    {
        $request->validate([
            'type' => 'required',
        ]);

        return successResponse(StaticNotification::where('type', request('type'))->first());
    }

    public function updateStaticNotification(Request $request)
    {
        DB::statement('SET sql_mode = " "');

        $request->validate([
            'title' => 'required|string',
            'body' => 'required|string',
            'config' => 'required',
            'is_active' => 'required',
            'type' => 'required',
        ]);

        $data = StaticNotification::where('type', $request->type)->updateOrCreate([
            'type' => $request->type,
        ], [
            'title' => $request->title,
            'body' => $request->body,
            'config' => $request->config,
            'is_active' => $request->is_active,
        ]);

        return successResponse($data);
    }

    public function sendDirectNotification(Request $request)
    {
        $validatedData = $request->validate([
            'title' => 'required|string',
            'body' => 'required|string',
            'scheduled_at' => 'required',

            'is_for_all' => 'required|boolean',
            'is_by_city' => 'required|boolean',
            'is_by_country' => 'required|boolean',
            'is_by_category' => 'required|boolean',
            'is_by_subcategory' => 'required|boolean',
            'is_by_product' => 'required|boolean',
            'is_by_size' => 'required|boolean',
            'for_clients_only' => 'required|boolean',

            'product_ids' => array('array'),
            'product_ids.*' => array('required_with:product_ids', 'exists:products,id'),

            'size_ids' => array('array'),
            'size_ids.*' => array('required_with:size_ids', 'exists:sizes,id'),

            'category_parent_ids' => array('array'),
            'category_parent_ids.*' => array('required_with:category_parent_ids', 'exists:categories,id'),

            'category_child_ids' => array('array'),
            'category_child_ids.*' => array('required_with:category_child_ids', 'exists:sub_categories,id'),

            'city_ids' => array('array'),
            'city_ids.*' => array('required_with:city_ids', 'exists:cities,id'),

            'country_ids' => array('array'),
            'country_ids.*' => array('required_with:country_ids', 'exists:countries,id'),

            'client_ids' => array('array'),
            'client_ids.*' => array('required_with:client_ids', 'exists:customers,id'),
        ]);

        $userIds = [];
        if ($validatedData['is_for_all'] == 1) {
            $userIds += DB::table('customers')
                ->whereNotNull('device_token')
                ->pluck('id')->toArray();
        } else {
            if ($validatedData['for_clients_only'] == 1) {
                $userIds += DB::table('customers')
                    ->whereNotNull('device_token')
                    ->whereIn('id', $validatedData['client_ids'])
                    ->pluck('id')->toArray();
            }
            if ($validatedData['is_by_country'] == 1) {
                if (in_array(1, $validatedData['country_ids'])) {
                    $userIds += DB::table('customers')
                        ->whereNotNull('device_token')
                        ->select(
                            'id',
                            DB::raw('LEFT(mobile, 4) as mobile_prefix'),
                        )->having('mobile_prefix', '=', '+966')->pluck('id')->toArray();
                }
                if (in_array(4, $validatedData['country_ids'])) {
                    $userIds += DB::table('customers')
                        ->whereNotNull('device_token')
                        ->select(
                            'id',
                            DB::raw('LEFT(mobile, 4) as mobile_prefix'),
                        )->having('mobile_prefix', '=', '+971')->pluck('id')->toArray();
                }
            }
            if ($validatedData['is_by_city'] == 1) {
                $userIds += DB::table('orders')
                    ->join('addresses', 'orders.address_id', '=', 'addresses.id')
                    ->join('cities', 'addresses.city_id', '=', 'cities.id')
                    ->join('customers', 'orders.customer_id', '=', 'customers.id')
                    ->whereIn('cities.id', $validatedData['city_ids'])
                    ->where('cities.is_active', 1)
                    ->whereNotNull('customers.device_token')
                    ->pluck('customers.id')->toArray();
            }
            if ($validatedData['is_by_product'] == 1) {
                $userIds +=  DB::table('order_products')
                    ->join('orders', 'order_products.order_ref_no', '=', 'orders.ref_no')
                    ->join('customers', 'orders.customer_id', '=', 'customers.id')
                    ->whereIn('order_products.product_id', $validatedData['product_ids'])
                    ->whereNotNull('customers.device_token')
                    ->pluck('customers.id')->toArray();
            }
            if ($validatedData['is_by_size'] == 1) {
                $userIds +=  DB::table('order_products')
                    ->join('orders', 'order_products.order_ref_no', '=', 'orders.ref_no')
                    ->join('customers', 'orders.customer_id', '=', 'customers.id')
                    ->whereIn('order_products.size_id', $validatedData['size_ids'])
                    ->whereNotNull('customers.device_token')
                    ->pluck('customers.id')->toArray();
            }

            if ($validatedData['is_by_category'] == 1) {
                $userIds += DB::table('order_products')
                    ->join('orders', 'order_products.order_ref_no', '=', 'orders.ref_no')
                    ->join('customers', 'orders.customer_id', '=', 'customers.id')
                    ->join('products', 'order_products.product_id', '=', 'products.id')
                    ->whereIn('products.category_id', $validatedData['category_parent_ids'])
                    ->whereNotNull('customers.device_token')
                    ->pluck('customers.id')->toArray();
            }
            if ($validatedData['is_by_subcategory'] == 1) {
                $userIds += DB::table('order_products')
                    ->join('orders', 'order_products.order_ref_no', '=', 'orders.ref_no')
                    ->join('customers', 'orders.customer_id', '=', 'customers.id')
                    ->join('products', 'order_products.product_id', '=', 'products.id')
                    ->whereIn('products.sub_category_id', $validatedData['category_child_ids'])
                    ->whereNotNull('customers.device_token')
                    ->pluck('customers.id')->toArray();
            }
        }

        $userIds = array_values(array_unique($userIds));

        $title = $request->title;
        $body = $request->body;
        $data = 'custom';

        $this->saveNotification($userIds, $title, $body, $request->scheduled_at, $data);

        return successResponse(['done']);
    }

    public function updateDeviceToken(Request $request)
    {
        $request->validate([
            'device_token' => 'required|string',
        ]);

        Customer::where('id', $request->customer_id)->update([
            'device_token' => $request->device_token,
        ]);

        return successResponse(['done']);
    }

    private function saveNotification($userIds, $title, $body, $scheduled_at = null, $data = null)
    {
        foreach ($userIds as $userId) {
            Notification::updateOrCreate([
                'customer_id' => $userId,
                'data' =>  $data,
                'sent_at' => null
            ], [
                'title' => $title,
                'body' => $body,
                'scheduled_at' => $scheduled_at ?? now()->addMinutes(1),
            ]);
        }

        return true;
    }

    // // 2. Personalized Offers for Old Customers
    // public function oldCustomerOffer(Request $request)
    // {
    //     $request->validate([
    //         'scheduled_at' => 'nullable|date',
    //     ]);

    //     // Query old customers (e.g., those who haven't purchased in the last 6 months)
    //     $oldCustomerUserIds = User::where('last_purchase_at', '<=', now()->subMonths(6))
    //         ->pluck('id')
    //         ->toArray();

    //     $title = 'We Miss You!';
    //     $body = "Hi {{name}}, we miss you! Enjoy 15% off your next purchase just for being a loyal customer.";
    //     $data = ['discount' => '15%'];

    //     $this->saveNotification($oldCustomerUserIds, $title, $body, $request->scheduled_at, $data);
    // }

    // // 3. Welcome Offers for New Customers
    // public function newCustomerWelcome(Request $request)
    // {
    //     $request->validate([
    //         'scheduled_at' => 'nullable|date',
    //     ]);

    //     // Query new customers (e.g., those who registered in the last week)
    //     $newCustomerUserIds = User::where('created_at', '>=', now()->subWeek())
    //         ->pluck('id')
    //         ->toArray();

    //     $title = 'Welcome to Our Store!';
    //     $body = "Welcome {{name}}! As a thank-you, enjoy 20% off your next order. Use code WELCOME20 at checkout.";
    //     $data = ['discount_code' => 'WELCOME20'];

    //     $this->saveNotification($newCustomerUserIds, $title, $body, $request->scheduled_at, $data);
    // }

    // // 4. Upselling and Cross-Selling Based on Past Purchases
    // public function crossSell(Request $request)
    // {
    //     $request->validate([
    //         'scheduled_at' => 'nullable|date',
    //     ]);

    //     // Query users who have purchased a specific product
    //     $crossSellUserIds = User::whereHas('orders.products', function ($query) {
    //         $query->where('product_id', '=', 1); // Replace with actual product ID
    //     })->pluck('id')->toArray();

    //     $title = 'Complete Your Purchase!';
    //     $body = "Love your recent purchase of {{product_name}}? Pair it with {{complementary_product}} for just {{X}} more!";
    //     $data = ['product_id' => 1, 'complementary_product_id' => 2];

    //     $this->saveNotification($crossSellUserIds, $title, $body, $request->scheduled_at, $data);
    // }

    // // 5. Flash Sales and Limited-Time Offers
    // public function flashSale(Request $request)
    // {
    //     $request->validate([
    //         'scheduled_at' => 'nullable|date',
    //     ]);

    //     // Query all users with device tokens
    //     $flashSaleUserIds = User::whereNotNull('device_token')->pluck('id')->toArray();

    //     $title = 'Flash Sale Alert!';
    //     $body = "Get 30% off all {{category}} items for the next 2 hours only! Don’t miss out.";
    //     $data = ['category' => 'electronics', 'discount' => '30%'];

    //     $this->saveNotification($flashSaleUserIds, $title, $body, $request->scheduled_at, $data);
    // }

    // // 6. Location-Based Notifications
    // public function locationBased(Request $request)
    // {
    //     $request->validate([
    //         'scheduled_at' => 'nullable|date',
    //     ]);

    //     // Query users near a specific location
    //     $locationBasedUserIds = User::where('location', '=', 'New York') // Replace with actual location logic
    //         ->pluck('id')
    //         ->toArray();

    //     $title = 'Visit Us Today!';
    //     $body = "You’re close to our store! Stop by today and get an exclusive in-store offer: Buy one, get one 50% off!";
    //     $data = ['store_location' => 'New York'];

    //     $this->saveNotification($locationBasedUserIds, $title, $body, $request->scheduled_at, $data);
    // }
}
