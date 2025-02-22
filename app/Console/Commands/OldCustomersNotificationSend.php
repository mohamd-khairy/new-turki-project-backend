<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\StaticNotification;
use App\Services\FirebaseService;
use Carbon\Carbon;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class OldCustomersNotificationSend extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'old-customer-notification:send';

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
        // Fetch the active "old_customers" notification configuration
        $oldCustomersNotification = StaticNotification::where('type', 'old_customers')
            ->where('is_active', 1)
            ->first();

        // Exit early if no active configuration is found
        if (!$oldCustomersNotification) {
            return true;
        }

        // Fetch eligible customers for notifications
        $customersForNotification = $this->fetchEligibleCustomers($oldCustomersNotification);

        // Send notifications to eligible customers
        foreach ($customersForNotification as $customer) {
            if ($this->checkIfCustomerHasNotification($customer, $oldCustomersNotification)) {
                continue;
            }
            $this->sendNotificationToCustomer($customer, $oldCustomersNotification);
        }

        return true;
    }

    /**
     * Fetch eligible customers for notifications based on the configuration.
     *
     * @param \App\Models\StaticNotification $oldCustomersNotification
     * @return \Illuminate\Support\Collection
     */
    private function fetchEligibleCustomers($oldCustomersNotification)
    {
        return DB::table('customers')
            ->select(
                'customers.id',
                'customers.device_token',
                'customers.name',
                'orders.ref_no',
                'orders.created_at',
                DB::raw('TIMESTAMPDIFF(DAY, orders.created_at, NOW()) as last_order_days')
            )
            ->join('orders', 'customers.id', '=', 'orders.customer_id')
            ->where('orders.created_at', function ($query) {
                $query->selectRaw('MAX(o2.created_at)')
                    ->from('orders as o2')
                    ->whereColumn('o2.customer_id', 'customers.id');
            })
            ->whereNotNull('customers.device_token')
            ->whereRaw('TIMESTAMPDIFF(DAY, orders.created_at, NOW()) >= ?', [$oldCustomersNotification->config])
            ->orderBy('orders.created_at', 'desc')
            ->get();
    }


    private function checkIfCustomerHasNotification($customer, $oldCustomersNotification)
    {
        return Notification::where('customer_id', $customer->id)
            ->where('data', $oldCustomersNotification->data)
            ->where(function ($query) {
                $query->whereDate('sent_at', today()->format('Y-m-d'))
                    ->orWhereDate('scheduled_at', today()->format('Y-m-d'));
            })
            ->exists();
    }
    /**
     * Send a notification to a specific customer.
     *
     * @param \stdClass $customer
     * @param \App\Models\StaticNotification $oldCustomersNotification
     */
    private function sendNotificationToCustomer($customer, $oldCustomersNotification)
    {
        $firebase = new FirebaseService();

        try {
            // Send the notification via Firebase
            $firebase->sendNotification(
                $customer->device_token,
                str_replace('{user_name}', $customer->name, $oldCustomersNotification->title),
                str_replace('{user_name}', $customer->name, $oldCustomersNotification->body),
                $oldCustomersNotification->data
            );

            // Log successful notification sending
            $this->logNotificationSuccess($customer, $oldCustomersNotification);
        } catch (\Exception $e) {
            // Log any errors during notification sending
            $this->logNotificationError($e);
        }
    }

    /**
     * Log successful notification sending and save the notification record.
     *
     * @param \stdClass $customer
     * @param \App\Models\StaticNotification $oldCustomersNotification
     */
    private function logNotificationSuccess($customer, $oldCustomersNotification)
    {
        $scheduledAt = $customer->created_at
            ? Carbon::parse($customer->created_at)->addMinutes($oldCustomersNotification->config)->format('Y-m-d H:i:s')
            : now()->addMinutes(1);

        $notification = Notification::create([
            'customer_id' => $customer->id,
            'data' => $oldCustomersNotification->data,
            'title' => str_replace('{user_name}', $customer->name, $oldCustomersNotification->title),
            'body' => str_replace('{user_name}', $customer->name, $oldCustomersNotification->body),
            'sent_at' => now(),
            'scheduled_at' => $scheduledAt,
        ]);

        if ($notification) {
            info('old_customer_notification');
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
        info('old_customer_notification_error');
        info($exception->getMessage());
    }
}
