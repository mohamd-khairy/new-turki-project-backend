<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderProduct;
use Illuminate\Console\Command;

class SendToOdoo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:odoo';

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
    public function handle(): void
    {
        $orders = Order::query()
            ->with(
                'paymentType',
                'customer',
                'orderState',
                'deliveryPeriod',
                'selectedAddress',
            )->where('sent_to_odoo', 0)->orderBy('id', 'desc')
            ->take(10)
            ->get();

        foreach ($orders as $order) {

            $order->update(['sent_to_odoo' => 1]);

            $products = OrderProduct::with('preparation', 'size', 'cut', 'shalwata')
                ->where('order_ref_no', $order->ref_no)
                ->get();

            $result = sendOrderToTurkishop($order, $products);
            info('test send to odoo');
            info($order->ref_no);
            info(json_encode($result));
        }
    }
}
