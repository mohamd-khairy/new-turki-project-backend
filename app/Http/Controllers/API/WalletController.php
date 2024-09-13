<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\WalletLog;
use Illuminate\Http\Request;

class WalletController extends Controller
{

    public function getWalletCustomerId(Request $request) /////////////
    {
        $log = Customer::with('wallet_logs')->where('id', auth()->user()->id)->first()
            ->wallet_logs->map(function ($item) {
                return [
                    "id" => $item->id,
                    "last_amount" => $item->last_amount,
                    "new_amount" => $item->new_amount,
                    "action" => $item->action,
                    "action_id" => $item->action_id,
                    "created_at" => $item->created_at,
                    'action_item' => $item->action_id ? Order::where('ref_no', $item->action_id)->first() : null
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'successfully updated!',
            'description' => "",
            "code" => "200",
            "data" => $log
        ], 200);
    }

    public function customerWalletLog()
    {
        $logs = WalletLog::with('action_item')->where('customer_id', auth()->user()->id)->orderBy('id', 'desc')->get();
        return response()->json([
            'success' => true,
            'message' => 'success!',
            'description' => "",
            "code" => "200",
            "data" => $logs
        ], 200);
    }

    public function getWalletLog()
    {
        $logs = WalletLog::with('action_item')->all();
        return response()->json([
            'success' => true,
            'message' => 'success!',
            'description' => "",
            "code" => "200",
            "data" => $logs
        ], 200);
    }

    public function getWalletLogById(WalletLog $wallet)
    {
        $wallet->load('action_item');

        return response()->json([
            'success' => true,
            'message' => 'success!',
            'description' => "",
            "code" => "200",
            "data" => $wallet
        ], 200);
    }
}
