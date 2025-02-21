<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\StaticNotification;
use App\Services\FirebaseService;
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
            $notifications = DB::table('carts as c1')
                ->select('c1.customer_id', 'customers.device_token', 'c1.created_at')
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
                ->get();

            $firebase = new FirebaseService();

            foreach ($notifications as $notification) {

                try {
                    $firebase->sendNotification(
                        $notification->device_token,
                        $cart_notification->title,
                        $cart_notification->body,
                        $cart_notification->data
                    );


                    Notification::create([
                        'customer_id' => $notification->customer_id,
                        'data' => $cart_notification->data,
                        'title' => $cart_notification->title,
                        'body' => $cart_notification->body,
                        'sent_at' => now(),
                        'scheduled_at' => $notification->created_at ? date('Y-m-d H:i:s', strtotime($notification->created_at . '+' . $cart_notification->config . ' minute')) : now()->addMinutes(1),
                    ]);

                    info('cart_notification');
                    info(json_encode($notification));
                } catch (\Exception $e) {
                    info('cart_notification');
                    info($e->getMessage());
                }
            }
        }

        return true;
    }
}
