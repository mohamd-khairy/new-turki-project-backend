<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\StaticNotification;
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
            $userIds = DB::table('customers')
                ->whereNotNull('device_token')
                ->whereRaw('TIMESTAMPDIFF(DAY, created_at, NOW()) = ?', [$old_customers->config]) // <=
                ->pluck('id')
                ->toArray();

            $this->saveNotification(
                $userIds,
                $old_customers->title,
                $old_customers->body,
                now()->addMinutes(1),
                $old_customers->type
            );

            info('old_customer_notification');
            info(json_encode($userIds));
        }

        return true;
    }

    function saveNotification($userIds, $title, $body, $scheduled_at = null, $data = null)
    {
        foreach ($userIds as $userId) {
            Notification::firstOrCreate([
                'customer_id' => $userId,
                'data' => $data,
            ], [
                'title' => $title,
                'body' => $body,
                'scheduled_at' => $scheduled_at ?? now()->addMinutes(1),
            ]);
        }

        return true;
    }
}
