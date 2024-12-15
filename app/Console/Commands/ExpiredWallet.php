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
                ->whereNotNull('expired_at')
                ->whereNotNull('action_id')
                ->whereDate('expired_at', date('Y-m-d', strtotime('-1 day')))
                ->get();


            foreach ($logs as $key => $log) {
                $amount = $log->new_amount - $log->last_amount;
                if (
                    $log->customer &&
                    $log->customer->orders
                    ->where('created_at', '>=',  Carbon::parse($log->created_at))
                    ->where('created_at', '<=', Carbon::parse($log->expired_at))
                    ->where('total_amount', '>=', $amount)
                    ->count() < 1
                ) {


                    $remove = WalletLog::where([
                        'action_id' => $log->action_id,
                        'customer_id' => $log->customer_id,
                        'action' => 'expiry',
                    ])->first();

                    if (!$remove) {

                        $new_amount = $log->customer->wallet - $amount;

                        if ($new_amount >= 0) {
                            $newlog = WalletLog::create([
                                'user_id' => null,
                                'customer_id' => $log->customer_id,
                                'last_amount' => $log->customer->wallet,
                                'new_amount' => $new_amount > 0 ? $new_amount : 0,
                                'action_id' =>  $log->action_id,
                                'action' => 'expiry',
                                'expired_days' => null,
                                'expired_at' => null,
                                'message_ar' => ' تسوية رصيد منتهي الصلاحية',
                                'message_en' => 'Expired balance settlement '
                            ]);

                            $log->update(['is_active' => 0]);
                            $log->customer->update(['wallet' => $new_amount]);
                        }
                    }
                }
            }


            DB::commit();
            info('expired wallet success');
            //code...
        } catch (\Throwable $th) {
            DB::rollBack();
            //throw $th;
            info('expired wallet error');
            info($th->getMessage());
        }
    }
}
