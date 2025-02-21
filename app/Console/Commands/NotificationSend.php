<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Services\FirebaseService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class NotificationSend extends Command
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
    protected $description = 'send notification to user devices';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $notifications = DB::table('notifications')
            ->select(
                'notifications.id',
                'notifications.title',
                'notifications.body',
                'notifications.data',
                'notifications.customer_id',
                'customers.device_token',
                'customers.name'
            )
            ->join('customers', 'customers.id', '=', 'notifications.customer_id')
            ->where('scheduled_at', '<=', now())
            ->whereNull('sent_at')
            ->whereNotNull('customer_id')
            ->whereNotNull('device_token')
            ->get();

        $firebase = new FirebaseService();

        if ($notifications->count() > 0) {
            foreach ($notifications as $notification) {
                try {
                    $firebase->sendNotification(
                        $notification->device_token,
                        str_replace('{user_name}', $notification->name, $notification->title),
                        str_replace('{user_name}', $notification->name, $notification->body),
                        $notification->data
                    );

                    // Mark the notification as sent
                    Notification::where('id', $notification->id)->update(['sent_at' => now()]);

                    info('filter_notification');
                    info(json_encode($notification));
                } catch (\Throwable $th) {
                    // throw $th;
                    info('filter_notification');
                    info($th->getMessage());
                }
            }
        }

        return true;
    }
}
