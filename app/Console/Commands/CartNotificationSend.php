<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\StaticNotification;
use App\Services\FirebaseService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CartNotificationSend extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cart-notification:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Disable strict SQL mode
        DB::statement('SET sql_mode = " "');

        // Fetch the active "cart" notification configuration
        $cartNotification = StaticNotification::where('type', 'cart')
            ->where('is_active', 1)
            ->first();

        // Exit early if no active configuration is found
        if (!$cartNotification) {
            return true;
        }

        // Fetch eligible customers for notifications
        $customersForNotification = $this->fetchEligibleCustomers($cartNotification);

        // Send notifications to eligible customers
        foreach ($customersForNotification as $customer) {
            $this->sendNotificationToCustomer($customer, $cartNotification);
        }

        return true;
    }

    /**
     * Fetch eligible customers for cart notifications based on the configuration.
     *
     * @param \App\Models\StaticNotification $cartNotification
     * @return \Illuminate\Support\Collection
     */
    private function fetchEligibleCustomers($cartNotification)
    {
        return DB::table('carts as c1')
            ->select('c1.customer_id', 'customers.device_token', 'c1.created_at' , 'customers.name')
            ->join('customers', 'c1.customer_id', '=', 'customers.id')
            ->whereNotNull('customers.device_token')
            ->whereNotNull('c1.created_at')
            ->where('c1.created_at', function ($query) {
                $query->selectRaw('MAX(c2.created_at)')
                    ->from('carts as c2')
                    ->whereColumn('c2.customer_id', 'c1.customer_id');
            })
            ->whereRaw('TIMESTAMPDIFF(MINUTE, c1.created_at, NOW()) = ?', [$cartNotification->config])
            ->orderBy('c1.created_at', 'desc')
            ->groupBy('c1.customer_id')
            ->get();
    }

    /**
     * Send a notification to a specific customer.
     *
     * @param \stdClass $customer
     * @param \App\Models\StaticNotification $cartNotification
     */
    private function sendNotificationToCustomer($customer, $cartNotification)
    {
        $firebase = new FirebaseService();

        try {
            // Send the notification via Firebase
            $firebase->sendNotification(
                $customer->device_token,
                str_replace('{user_name}', $customer->name, $cartNotification->title),
                str_replace('{user_name}', $customer->name, $cartNotification->body),
                $cartNotification->data
            );

            // Log successful notification sending
            $this->logNotificationSuccess($customer, $cartNotification);
        } catch (\Exception $e) {
            // Log any errors during notification sending
            $this->logNotificationError($e);
        }
    }

    /**
     * Log successful notification sending and save the notification record.
     *
     * @param \stdClass $customer
     * @param \App\Models\StaticNotification $cartNotification
     */
    private function logNotificationSuccess($customer, $cartNotification)
    {
        $scheduledAt = $customer->created_at
            ? Carbon::parse($customer->created_at)->addMinutes($cartNotification->config)->format('Y-m-d H:i:s')
            : now()->addMinutes(1);

        $notification = Notification::create([
            'customer_id' => $customer->customer_id,
            'data' => $cartNotification->data,
            'title' => str_replace('{user_name}', $customer->name, $cartNotification->title),
            'body' => str_replace('{user_name}', $customer->name, $cartNotification->body),
            'sent_at' => now(),
            'scheduled_at' => $scheduledAt,
        ]);

        if ($notification) {
            info('cart_notification');
            info(json_encode($notification));
        }
    }

    /**
     * Log an error during notification sending.
     *
     * @param \Exception $exception
     */
    private function logNotificationError($exception)
    {
        info('cart_notification_error');
        info($exception->getMessage());
    }
}
