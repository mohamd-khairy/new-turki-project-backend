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
                ->pluck('id', 'created_at')
                ->toArray();

            $this->saveNotification(
                $userIds,
                $old_customers->title,
                $old_customers->body,
                $old_customers->config,
                $old_customers->type
            );

            info('old_customer_notification');
            info(json_encode($userIds));
        }

        return true;
    }

    public function saveNotification($userIds, $title, $body, $config = 1, $data = null)
    {
        foreach ($userIds as $date => $userId) {

            if (!Notification::where([
                'customer_id' => $userId,
                'data' => $data,
                'scheduled_at' => $date ? date('Y-m-d H:i:s', strtotime($date . '+' . $config . ' minute')) : now()->addMinutes(1),
            ])->exists())
                Notification::create([
                    'title' => $title,
                    'body' => $body,
                    'scheduled_at' => $date ? date('Y-m-d H:i:s', strtotime($date . '+' . $config . ' minute')) : now()->addMinutes(1),
                ]);
        }

        return true;
    }
}
