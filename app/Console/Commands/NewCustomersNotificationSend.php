<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\StaticNotification;
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
            $userIds = DB::table('customers')
                ->whereNotNull('device_token')
                ->whereRaw('TIMESTAMPDIFF(MINUTE, created_at, NOW()) = ?', [$new_customers->config]) // <=
                ->pluck('id')->toArray();

            $this->saveNotification(
                $userIds,
                $new_customers->title,
                $new_customers->body,
                now()->addMinutes(1),
                $new_customers->type
            );

            info('new_customer_notification');
            info(json_encode($userIds));
        }
        return true;
    }

    public function saveNotification($userIds, $title, $body, $scheduled_at = null, $data = null)
    {
        foreach ($userIds as $userId) {

            if (!Notification::where([
                'customer_id' => $userId,
                'data' => $data,
            ])->exists())
                Notification::create([
                    'customer_id' => $userId,
                    'data' => $data,
                    'title' => $title,
                    'body' => $body,
                    'scheduled_at' => $scheduled_at ?? now()->addMinutes(1),
                ]);
        }

        return true;
    }
}
