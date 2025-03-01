<?php

namespace App\Console\Commands;

use App\Models\CustomNotification;
use App\Models\Notification;
use App\Services\FirebaseService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CustomNotificationSend extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notification:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send custom notifications to customers';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        DB::statement('SET sql_mode = " "');

        // Fetch active custom notifications that are due to be sent
        $customNotifications = CustomNotification::query()
            ->where('scheduled_at', '<=', now())
            ->whereNull('sent_at')
            ->get();

        // Process each notification
        foreach ($customNotifications as $customNotification) {

            if ($customNotification->is_for_all) {
                // $customer_data += $this->getAllUsersWithDeviceTokens();

                // $firebase = new FirebaseService();
                // $firebase->sendForAll($customNotification->title, $customNotification->body, [], $customNotification->image);
                // continue;
            } else {
                // Determine target user IDs based on notification criteria
                $customer_data = $this->getCustomerDataForNotification($customNotification);

                // Save and send notifications for the targeted users
                $this->saveNotification($customer_data, $customNotification);
            }

            $customNotification->update(['sent_at' => now()]);
        }

        return true;
    }

    /**
     * Get user IDs based on the notification's targeting criteria.
     *
     * @param \stdClass $customNotification
     * @return array
     */
    private function getCustomerDataForNotification($customNotification)
    {
        $customer_data = [];

        if ($customNotification->for_clients_only) {
            $customer_data += $this->getUsersByClientIds($customNotification->client_ids);
        }
        if ($customNotification->is_by_country) {
            $customer_data += $this->getUsersByCountry($customNotification->country_ids);
        }
        if ($customNotification->is_by_city) {
            $customer_data += $this->getUsersByCity($customNotification->city_ids);
        }
        if ($customNotification->is_by_product) {
            $customer_data += $this->getUsersByProduct($customNotification->product_ids);
        }
        if ($customNotification->is_by_size) {
            $customer_data += $this->getUsersBySize($customNotification->size_ids);
        }
        if ($customNotification->is_by_category) {
            $customer_data += $this->getUsersByCategory($customNotification->category_parent_ids);
        }
        if ($customNotification->is_by_subcategory) {
            $customer_data += $this->getUsersBySubcategory($customNotification->category_child_ids);
        }

        return $customer_data;
    }

    /**
     * Fetch all users with device tokens.
     *
     * @return array
     */
    private function getAllUsersWithDeviceTokens()
    {
        return DB::table('customers')
            ->whereNotNull('device_token')
            ->pluck('id', 'device_token')
            ->toArray();
    }

    /**
     * Fetch users by client IDs.
     *
     * @param array $clientIds
     * @return array
     */
    private function getUsersByClientIds($clientIds)
    {
        return DB::table('customers')
            ->whereNotNull('device_token')
            ->whereIn('id', $clientIds)
            ->pluck('id', 'device_token')
            ->toArray();
    }

    /**
     * Fetch users by country codes.
     *
     * @param array $countryIds
     * @return array
     */
    private function getUsersByCountry($countryIds)
    {
        $customer_data = [];
        foreach ($countryIds as $countryId) {
            switch ($countryId) {
                case 1:
                    $prefix = '+966';
                    break;
                case 4:
                    $prefix = '+971';
                    break;
                default:
                    $prefix = null;
            }
            if ($prefix) {
                $customer_data += DB::table('customers')
                    ->whereNotNull('device_token')
                    ->select('id', DB::raw('LEFT(mobile, 4) as mobile_prefix'))
                    ->having('mobile_prefix', '=', $prefix)
                    ->pluck('id', 'device_token')
                    ->toArray();
            }
        }
        return $customer_data;
    }

    /**
     * Fetch users by city IDs.
     *
     * @param array $cityIds
     * @return array
     */
    private function getUsersByCity($cityIds)
    {
        return DB::table('addresses')
            ->select('customers.id', 'customers.device_token')
            ->join('customers', 'addresses.customer_id', '=', 'customers.id')
            ->whereIn('addresses.city_id', $cityIds)
            ->whereNotNull('customers.device_token')
            ->groupBy('addresses.customer_id')
            ->pluck('customers.id', 'customers.device_token')
            ->toArray();
    }

    /**
     * Fetch users by product IDs.
     *
     * @param array $productIds
     * @return array
     */
    private function getUsersByProduct($productIds)
    {
        return DB::table('order_products')
            ->select('customers.id', 'customers.device_token')
            ->join('orders', 'order_products.order_ref_no', '=', 'orders.ref_no')
            ->join('customers', 'orders.customer_id', '=', 'customers.id')
            ->whereIn('order_products.product_id', $productIds)
            ->whereNotNull('customers.device_token')
            ->pluck('customers.id', 'customers.device_token')
            ->toArray();
    }

    /**
     * Fetch users by size IDs.
     *
     * @param array $sizeIds
     * @return array
     */
    private function getUsersBySize($sizeIds)
    {
        return DB::table('order_products')
            ->select('customers.id', 'customers.device_token')
            ->join('orders', 'order_products.order_ref_no', '=', 'orders.ref_no')
            ->join('customers', 'orders.customer_id', '=', 'customers.id')
            ->whereIn('order_products.size_id', $sizeIds)
            ->whereNotNull('customers.device_token')
            ->pluck('customers.id', 'customers.device_token')
            ->toArray();
    }

    /**
     * Fetch users by category IDs.
     *
     * @param array $categoryIds
     * @return array
     */
    private function getUsersByCategory($categoryIds)
    {
        return DB::table('order_products')
            ->select('customers.id', 'customers.device_token')
            ->join('orders', 'order_products.order_ref_no', '=', 'orders.ref_no')
            ->join('customers', 'orders.customer_id', '=', 'customers.id')
            ->join('products', 'order_products.product_id', '=', 'products.id')
            ->whereIn('products.category_id', $categoryIds)
            ->whereNotNull('customers.device_token')
            ->pluck('customers.id', 'customers.device_token')
            ->toArray();
    }

    /**
     * Fetch users by subcategory IDs.
     *
     * @param array $subcategoryIds
     * @return array
     */
    private function getUsersBySubcategory($subcategoryIds)
    {
        return DB::table('order_products')
            ->select('customers.id', 'customers.device_token')
            ->join('orders', 'order_products.order_ref_no', '=', 'orders.ref_no')
            ->join('customers', 'orders.customer_id', '=', 'customers.id')
            ->join('products', 'order_products.product_id', '=', 'products.id')
            ->whereIn('products.sub_category_id', $subcategoryIds)
            ->whereNotNull('customers.device_token')
            ->pluck('customers.id', 'customers.device_token')
            ->toArray();
    }

    /**
     * Save and send notifications for the targeted users.
     *
     * @param array $customer_data
     * @param \stdClass $customNotification
     */
    private function saveNotification($customer_data, $customNotification)
    {
        $notifications = [];
        $firebase = new FirebaseService();

        foreach ($customer_data as $deviceToken => $userId) {
            try {
                // Send the notification via Firebase
                $res = $firebase->sendNotification(
                    $deviceToken,
                    $customNotification->title,
                    $customNotification->body,
                    $customNotification->data,
                    $customNotification->image
                );

                // Prepare notification data for bulk insertion
                $notifications[] = [
                    'customer_id' => $userId,
                    'data' => 'custom_notification',
                    'sent_at' => now(),
                    'title' => $customNotification->title,
                    'body' => $customNotification->body,
                    'scheduled_at' => $customNotification->scheduled_at,
                ];
            } catch (\Exception $e) {
                // Log errors silently (optional: log to a file or monitoring system)
                $this->logNotificationError($e);
            }
        }

        // Bulk insert notifications into the database
        if (!empty($notifications)) {
            Notification::insert($notifications);

            info('custom_notification');
            info(json_encode($notifications));
        }
    }

    /**
     * Log an error during notification sending.
     *
     * @param \Exception $exception
     */
    private function logNotificationError($exception)
    {
        info('custom_notification_error');
        info($exception->getMessage());
    }
}
