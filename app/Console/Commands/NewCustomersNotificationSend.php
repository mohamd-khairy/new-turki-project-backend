<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\StaticNotification;
use App\Services\FirebaseService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class NewCustomersNotificationSend extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'new-customer-notification:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        // Fetch the active "new_customers" notification configuration
        $newCustomersNotification = StaticNotification::where('type', 'new_customers')
            ->where('is_active', 1)
            ->first();

        // Exit early if no active configuration is found
        if (!$newCustomersNotification) {
            return true;
        }

        // Fetch eligible customers for notifications
        $customersForNotification = $this->fetchEligibleCustomers($newCustomersNotification);

        // Send notifications to eligible customers
        foreach ($customersForNotification as $customer) {
            $this->sendNotificationToCustomer($customer, $newCustomersNotification);
        }

        return true;
    }

    /**
     * Fetch eligible customers for notifications based on the configuration.
     *
     * @param \App\Models\StaticNotification $newCustomersNotification
     * @return \Illuminate\Support\Collection
     */
    private function fetchEligibleCustomers($newCustomersNotification)
    {
        return DB::table('customers')
            ->select('id', 'created_at', 'device_token', 'name')
            ->whereNotNull('device_token')
            ->whereRaw('TIMESTAMPDIFF(MINUTE, created_at, NOW()) = ?', [$newCustomersNotification->config])
            ->get();
    }

    /**
     * Send a notification to a specific customer.
     *
     * @param \stdClass $customer
     * @param \App\Models\StaticNotification $newCustomersNotification
     */
    private function sendNotificationToCustomer($customer, $newCustomersNotification)
    {
        $firebase = new FirebaseService();

        try {
            // Send the notification via Firebase
            $firebase->sendNotification(
                $customer->device_token,
                str_replace('{user_name}', $customer->name, $newCustomersNotification->title),
                str_replace('{user_name}', $customer->name, $newCustomersNotification->body),
                $newCustomersNotification->data
            );

            // Log successful notification sending
            $this->logNotificationSuccess($customer, $newCustomersNotification);
        } catch (\Exception $e) {
            // Log any errors during notification sending
            $this->logNotificationError($e);
        }
    }

    /**
     * Log successful notification sending and save the notification record.
     *
     * @param \stdClass $customer
     * @param \App\Models\StaticNotification $newCustomersNotification
     */
    private function logNotificationSuccess($customer, $newCustomersNotification)
    {
        $scheduledAt = $customer->created_at
            ? Carbon::parse($customer->created_at)->addMinutes($newCustomersNotification->config)->format('Y-m-d H:i:s')
            : now()->addMinutes(1);

        $notification = Notification::create([
            'customer_id' => $customer->id,
            'data' => $newCustomersNotification->data,
            'title' => str_replace('{user_name}', $customer->name, $newCustomersNotification->title),
            'body' => str_replace('{user_name}', $customer->name, $newCustomersNotification->body),
            'sent_at' => now(),
            'scheduled_at' => $scheduledAt,
        ]);

        if ($notification) {
            info('new_customer_notification');
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
        info('new_customer_notification_error');
        info($exception->getMessage());
    }
}
