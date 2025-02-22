<?php

namespace App\Http\Controllers\API\Notification;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Cart;
use App\Models\Customer;
use App\Models\CustomNotification;
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
            'is_active' => $request->is_active == 1 ? 1 : 0,
        ]);

        return successResponse($data);
    }

    public function allCustomNotifications(Request $request)
    {
        $data = CustomNotification::query()
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

    public function createCustomNotification(Request $request)
    {
        $validatedData = $request->validate([
            'title' => 'required|string',
            'body' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'scheduled_at' => 'required',

            'is_for_all' => 'required|boolean',
            'is_by_city' => 'required|boolean',
            'is_by_country' => 'required|boolean',
            'is_by_category' => 'required|boolean',
            'is_by_subcategory' => 'required|boolean',
            'is_by_product' => 'required|boolean',
            'is_by_size' => 'required|boolean',
            'for_clients_only' => 'required|boolean',

            'product_ids' => 'nullable|required_if:is_by_product,1',
            'size_ids' => 'nullable|required_if:is_by_size,1',
            'category_parent_ids' => 'nullable|required_if:is_by_category,1',
            'category_child_ids' => 'nullable|required_if:is_by_subcategory,1',
            'city_ids' => 'nullable|required_if:is_by_city,1',
            'country_ids' => 'nullable|required_if:is_by_country,1',
            'client_ids' => 'nullable|required_if:for_clients_only,1',
        ]);


        $data = CustomNotification::create([
            'title' => $request->title,
            'body' => $request->body,
            'image' => $request->image ?  url('public/storage/' . $request->image->store('custom_notifications', 'public')) : null,
            'scheduled_at' => $request->scheduled_at,
            'is_for_all' => $request->is_for_all,
            'is_by_city' => $request->is_by_city,
            'is_by_country' => $request->is_by_country,
            'is_by_category' => $request->is_by_category,
            'is_by_subcategory' => $request->is_by_subcategory,
            'is_by_product' => $request->is_by_product,
            'is_by_size' => $request->is_by_size,
            'for_clients_only' => $request->for_clients_only,
            'product_ids' => $request->is_by_product ? (collect(explode(',', $request->product_ids))->map(fn($item) => (int) $item)->toArray()) : null,
            'size_ids' => $request->is_by_size ? (collect(explode(',', $request->size_ids))->map(fn($item) => (int) $item)->toArray()) : null,
            'category_parent_ids' => $request->is_by_category ? (collect(explode(',', $request->category_parent_ids))->map(fn($item) => (int) $item)->toArray()) : null,
            'category_child_ids' => $request->is_by_subcategory ? (collect(explode(',', $request->category_child_ids))->map(fn($item) => (int) $item)->toArray()) : null,
            'city_ids' => $request->is_by_city ? (collect(explode(',', $request->city_ids))->map(fn($item) => (int) $item)->toArray()) : null,
            'country_ids' => $request->is_by_country ? (collect(explode(',', $request->country_ids))->map(fn($item) => (int) $item)->toArray()) : null,
            'client_ids' => $request->for_clients_only ? (collect(explode(',', $request->client_ids))->map(fn($item) => (int) $item)->toArray()) : null,
            'is_active' => $request->is_active == 1 ? 1 : 0,
        ]);

        return successResponse($data);
    }

    public function updateCustomNotification(Request $request, $id)
    {
        $validatedData = $request->validate([
            'title' => 'required|string',
            'body' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'scheduled_at' => 'required',
            'is_for_all' => 'required|boolean',
            'is_by_city' => 'required|boolean',
            'is_by_country' => 'required|boolean',
            'is_by_category' => 'required|boolean',
            'is_by_subcategory' => 'required|boolean',
            'is_by_product' => 'required|boolean',
            'is_by_size' => 'required|boolean',
            'for_clients_only' => 'required|boolean',
            'product_ids' => 'nullable|required_if:is_by_product,1',
            'size_ids' => 'nullable|required_if:is_by_size,1',
            'category_parent_ids' => 'nullable|required_if:is_by_category,1',
            'category_child_ids' => 'nullable|required_if:is_by_subcategory,1',
            'city_ids' => 'nullable|required_if:is_by_city,1',
            'country_ids' => 'nullable|required_if:is_by_country,1',
            'client_ids' => 'nullable|required_if:for_clients_only,1',

        ]);

        $data = CustomNotification::where('id', $id)->first();

        if ($data) {
            $image = $data->image;
            if ($request->image) {
                if (isset(explode('/public', $data->image)[1]))
                    unlink('public/' . explode('/public', $data->image)[1]);
                $image = url('public/storage/' . $request->image->store('custom_notifications', 'public'));
            }

            $data->update([
                'title' => $request->title ?? $data->title,
                'body' => $request->body ?? $data->body,
                'image' => $image,
                'scheduled_at' => $request->scheduled_at ?? $data->scheduled_at,
                'is_for_all' => $request->is_for_all ?? $data->is_for_all,
                'is_by_city' => $request->is_by_city ?? $data->is_by_city,
                'is_by_country' => $request->is_by_country ?? $data->is_by_country,
                'is_by_category' => $request->is_by_category ?? $data->is_by_category,
                'is_by_subcategory' => $request->is_by_subcategory ?? $data->is_by_subcategory,
                'is_by_product' => $request->is_by_product ?? $data->is_by_product,
                'is_by_size' => $request->is_by_size ?? $data->is_by_size,
                'for_clients_only' => $request->for_clients_only ?? $data->for_clients_only,
                'product_ids' => $request->is_by_product ? (collect(explode(',', $request->product_ids))->map(fn($item) => (int) $item)->toArray()) : null,
                'size_ids' => $request->is_by_size ? (collect(explode(',', $request->size_ids))->map(fn($item) => (int) $item)->toArray()) : null,
                'category_parent_ids' => $request->is_by_category ? (collect(explode(',', $request->category_parent_ids))->map(fn($item) => (int) $item)->toArray()) : null,
                'category_child_ids' => $request->is_by_subcategory ? (collect(explode(',', $request->category_child_ids))->map(fn($item) => (int) $item)->toArray()) : null,
                'city_ids' => $request->is_by_city ? (collect(explode(',', $request->city_ids))->map(fn($item) => (int) $item)->toArray()) : null,
                'country_ids' => $request->is_by_country ? (collect(explode(',', $request->country_ids))->map(fn($item) => (int) $item)->toArray()) : null,
                'client_ids' => $request->for_clients_only ? (collect(explode(',', $request->client_ids))->map(fn($item) => (int) $item)->toArray()) : null,
            ]);
        }

        return successResponse($data);
    }
    public function deleteCustomNotification(Request $request, $id)
    {
        $data = CustomNotification::where('id', $id)->first();

        if ($data) {
            if (isset(explode('/public', $data->image)[1]))
                unlink('public/' . (explode('/public', $data->image)[1]));
            $data->delete();
        }
        return successResponse($data);
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
