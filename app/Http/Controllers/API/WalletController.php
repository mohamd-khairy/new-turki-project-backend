<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerWalletLogResource;
use App\Models\Customer;
use App\Models\Order;
use App\Models\WalletLog;
use Illuminate\Http\Request;

class WalletController extends Controller
{

    public function getWalletCustomerId(Request $request) /////////////
    {
        $log = Customer::with('wallet_logs')->where('id', auth()->user()->id)->first();



        return response()->json([
            'success' => true,
            'message' => 'successfully updated!',
            'description' => "",
            "code" => "200",
            "data" => new CustomerWalletLogResource($log), //$log
        ], 200);
    }

    public function customerWalletLog()
    {
        $logs = WalletLog::where('customer_id', auth()->user()->id)->orderBy('id', 'desc')->get();
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
        $logs = WalletLog::all();
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
