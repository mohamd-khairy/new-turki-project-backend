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
        try {

            $orders = Order::query()
                ->with(
                    'paymentType',
                    'customer',
                    'orderState',
                    'deliveryPeriod',
                    'selectedAddress',
                )->where('sent_to_odoo', 0)
                ->whereHas('customer', function ($query) {
                    $query->where('mobile', 'like', '+966%');
                })
                ->whereDate('created_at', today())
                ->orderBy('id', 'desc')
                ->take(50)
                ->get();

            foreach ($orders as $order) {
                $result = sendOrderToTurkishop($order);
                if ($result['status_code'] == 200 || $result['status_code'] == '200') {
                    $order->update(['sent_to_odoo' => 1]);
                }
                info('test send to odoo');
                info($order->ref_no);
                info(json_encode($result));
            }

            //code...
        } catch (\Throwable $th) {
            //throw $th;
            info('error send to odoo');
            info($th->getMessage());
        }
    }
}
