<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\StaticNotification;
use App\Services\FirebaseService;
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
        $new_customers = StaticNotification::where('type', 'new_customers')->where('is_active', 1)->first();
        if ($new_customers) {
            $notifications = DB::table('customers')
                ->select('id', 'created_at', 'device_token')
                ->whereNotNull('device_token')
                ->whereRaw('TIMESTAMPDIFF(MINUTE, created_at, NOW()) = ?', [$new_customers->config]) // <=
                ->get();

            $firebase = new FirebaseService();

            foreach ($notifications as $notification) {

                try {
                    $firebase->sendNotification(
                        $notification->device_token,
                        $new_customers->title,
                        $new_customers->body,
                        $new_customers->data
                    );

                    Notification::create([
                        'customer_id' => $notification->id,
                        'data' => $new_customers->data,
                        'title' => $new_customers->title,
                        'body' => $new_customers->body,
                        'scheduled_at' => $notification->created_at ? date('Y-m-d H:i:s', strtotime($notification->created_at . '+' . $new_customers->config . ' minute')) : now()->addMinutes(1),
                    ]);

                    info('new_customer_notification');
                    info(json_encode($notification));
                } catch (\Exception $e) {
                    info('new_customer_notification');
                    info($e->getMessage());
                }
            }
        }
        return true;
    }
}
