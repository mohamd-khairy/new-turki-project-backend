<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\StaticNotification;
use App\Services\FirebaseService;
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
        $old_customers = StaticNotification::where('type', 'old_customers')->where('is_active', 1)->first();
        if ($old_customers) {
            $notifications = DB::table('customers')
                ->select('id', 'created_at', 'device_token')
                ->whereNotNull('device_token')
                ->whereRaw('TIMESTAMPDIFF(DAY, created_at, NOW()) = ?', [$old_customers->config]) // <=
                ->get();

            $firebase = new FirebaseService();

            foreach ($notifications as $notification) {

                try {
                    $firebase->sendNotification(
                        $notification->device_token,
                        $old_customers->title,
                        $old_customers->body,
                        $old_customers->data
                    );

                    Notification::create([
                        'customer_id' => $notification->id,
                        'data' => $old_customers->data,
                        'title' => $old_customers->title,
                        'body' => $old_customers->body,
                        'scheduled_at' => $notification->created_at ? date('Y-m-d H:i:s', strtotime($notification->created_at . '+' . $old_customers->config . ' minute')) : now()->addMinutes(1),
                    ]);

                    info('old_customer_notification');
                    info(json_encode($notification));
                } catch (\Exception $e) {
                    info('old_customer_notification');
                    info($e->getMessage());
                }
            }
        }

        return true;
    }
}
