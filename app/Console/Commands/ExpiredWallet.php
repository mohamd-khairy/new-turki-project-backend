<?php

namespace App\Console\Commands;

use App\Models\WalletLog;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpiredWallet extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'expired:wallet';

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
        try {
            DB::beginTransaction();


            $logs = WalletLog::with('customer')
                ->whereDate('expired_at', date('Y-m-d'))->get();

                info($logs);
            // foreach ($logs as $key => $log) {
            //     $amount = $log->new_amount - $log->last_amount;
            //     if (
            //         $log->customer->orders
            //         ->where('created_at', '>=',  Carbon::parse($log->created_at)->format('Y-m-d'))
            //         ->where('created_at', '<=', Carbon::parse($log->expired_at)->format('Y-m-d'))
            //         ->where('total_amount', '>=', $amount)
            //         ->count() < 1
            //     ) {

            //         $remove = WalletLog::where([
            //             'action_id' => $log->action_id,
            //             'customer_id' => $log->customer_id,
            //             'action' => 'remove',
            //         ])->first();

            //         if (!$remove) {

            //             $new_amount = $log->customer->wallet - $amount;

            //             $log = WalletLog::create([
            //                 'user_id' => null,
            //                 'customer_id' => $log->customer_id,
            //                 'last_amount' => $log->customer->wallet,
            //                 'new_amount' => $new_amount,
            //                 'action_id' =>  $log->action_id,
            //                 'action' => 'remove',
            //                 'expired_days' => null,
            //                 'expired_at' => null,
            //                 'message_ar' => 'انتهاء الرصيد',
            //                 'message_en' => 'expired money'
            //             ]);

            //             $log->customer->update(['wallet' => $new_amount]);
            //         }
            //     }
            // }

            DB::commit();
            //code...
        } catch (\Throwable $th) {
            DB::rollBack();
            //throw $th;
            info($th->getMessage());
        }
    }
}
