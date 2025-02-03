<?php

namespace App\Http\Controllers\API\Notification;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Cart;
use App\Models\Notification;
use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class NotificationController extends Controller
{
    private $firebase;

    public function __construct(FirebaseService $firebase)
    {
        $this->firebase = $firebase;
    }

    // 1. Left Cart Notification
    public function leftCart(Request $request)
    {
        $request->validate([
            'scheduled_at' => 'nullable|date',
        ]);

        $leftCartUserIds = Cart::query()->pluck('user_id')->toArray();
        $title = 'Cart Left';
        $body = 'You left your cart, do you want to continue shopping?';
        $data = [];

        $this->saveNotification($leftCartUserIds, $title, $body, $request->scheduled_at, $data);
    }

    // 2. Personalized Offers for Old Customers
    public function oldCustomerOffer(Request $request)
    {
        $request->validate([
            'scheduled_at' => 'nullable|date',
        ]);

        // Query old customers (e.g., those who haven't purchased in the last 6 months)
        $oldCustomerUserIds = User::where('last_purchase_at', '<=', now()->subMonths(6))
            ->pluck('id')
            ->toArray();

        $title = 'We Miss You!';
        $body = "Hi {{name}}, we miss you! Enjoy 15% off your next purchase just for being a loyal customer.";
        $data = ['discount' => '15%'];

        $this->saveNotification($oldCustomerUserIds, $title, $body, $request->scheduled_at, $data);
    }

    // 3. Welcome Offers for New Customers
    public function newCustomerWelcome(Request $request)
    {
        $request->validate([
            'scheduled_at' => 'nullable|date',
        ]);

        // Query new customers (e.g., those who registered in the last week)
        $newCustomerUserIds = User::where('created_at', '>=', now()->subWeek())
            ->pluck('id')
            ->toArray();

        $title = 'Welcome to Our Store!';
        $body = "Welcome {{name}}! As a thank-you, enjoy 20% off your next order. Use code WELCOME20 at checkout.";
        $data = ['discount_code' => 'WELCOME20'];

        $this->saveNotification($newCustomerUserIds, $title, $body, $request->scheduled_at, $data);
    }

    // 4. Upselling and Cross-Selling Based on Past Purchases
    public function crossSell(Request $request)
    {
        $request->validate([
            'scheduled_at' => 'nullable|date',
        ]);

        // Query users who have purchased a specific product
        $crossSellUserIds = User::whereHas('orders.products', function ($query) {
            $query->where('product_id', '=', 1); // Replace with actual product ID
        })->pluck('id')->toArray();

        $title = 'Complete Your Purchase!';
        $body = "Love your recent purchase of {{product_name}}? Pair it with {{complementary_product}} for just $X more!";
        $data = ['product_id' => 1, 'complementary_product_id' => 2];

        $this->saveNotification($crossSellUserIds, $title, $body, $request->scheduled_at, $data);
    }

    // 5. Flash Sales and Limited-Time Offers
    public function flashSale(Request $request)
    {
        $request->validate([
            'scheduled_at' => 'nullable|date',
        ]);

        // Query all users with device tokens
        $flashSaleUserIds = User::whereNotNull('device_token')->pluck('id')->toArray();

        $title = 'Flash Sale Alert!';
        $body = "Get 30% off all {{category}} items for the next 2 hours only! Don’t miss out.";
        $data = ['category' => 'electronics', 'discount' => '30%'];

        $this->saveNotification($flashSaleUserIds, $title, $body, $request->scheduled_at, $data);
    }

    // 6. Location-Based Notifications
    public function locationBased(Request $request)
    {
        $request->validate([
            'scheduled_at' => 'nullable|date',
        ]);

        // Query users near a specific location
        $locationBasedUserIds = User::where('location', '=', 'New York') // Replace with actual location logic
            ->pluck('id')
            ->toArray();

        $title = 'Visit Us Today!';
        $body = "You’re close to our store! Stop by today and get an exclusive in-store offer: Buy one, get one 50% off!";
        $data = ['store_location' => 'New York'];

        $this->saveNotification($locationBasedUserIds, $title, $body, $request->scheduled_at, $data);
    }

    // Private method to save notifications
    private function saveNotification($userIds, $title, $body, $scheduled_at = null, $data = [])
    {
        foreach ($userIds as $userId) {
            Notification::create([
                'title' => $title,
                'body' => $body,
                'data' => json_encode($data),
                'user_id' => $userId,
                'scheduled_at' => $scheduled_at ?? now()->addMinutes(1),
            ]);
        }

        return true;
    }
}
