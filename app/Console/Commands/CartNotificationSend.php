<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\StaticNotification;
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
        DB::statement('SET sql_mode = " "');

        $cart_notification = StaticNotification::where('type', 'cart')->where('is_active', 1)->first();
        if ($cart_notification) {
            $userIds = DB::table('carts as c1')
                ->join('customers', 'c1.customer_id', '=', 'customers.id')
                ->whereNotNull('customers.device_token')
                ->whereNotNull('c1.created_at')
                ->where('c1.created_at', function ($query) {
                    $query->selectRaw('MAX(c2.created_at)')
                        ->from('carts as c2')
                        ->whereColumn('c2.customer_id', 'c1.customer_id');
                })
                ->whereRaw('TIMESTAMPDIFF(MINUTE, c1.created_at, NOW()) = ?', [$cart_notification->config]) // >=
                ->orderBy('c1.created_at', 'desc')
                ->groupBy('c1.customer_id')
                ->pluck('customers.id')
                ->toArray();

            $this->saveNotification(
                $userIds,
                $cart_notification->title,
                $cart_notification->body,
                now()->addMinutes(1),
                $cart_notification->type
            );

            info('cart_notification');
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
