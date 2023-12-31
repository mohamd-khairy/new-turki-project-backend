<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\WalletLog;
use Illuminate\Http\Request;
use App\Services\MyFatoorahApiService;
use App\Models\Country;
class WalletController extends Controller
{
    public function customerWalletLog(){
        $logs = WalletLog::where('customer_id', auth()->user()->id)->orderBy('id','desc')->get();
        return response()->json(['success' => true, 'message' => 'success!', 'description' => "", "code" => "200",
            "data" => $logs], 200);
    }
    
      public function getWalletLog(){
        $logs = WalletLog::all();
        return response()->json(['success' => true, 'message' => 'success!', 'description' => "", "code" => "200",
            "data" => $logs], 200);
    }
    
    
       public function getWalletLogById(WalletLog $wallet){
           
        return response()->json(['success' => true, 'message' => 'success!', 'description' => "", "code" => "200",
            "data" => $wallet], 200);
    }
    
           public function getWalletLogByCustomerId(Request $request ,$id){
      
        $log = WalletLog::where('customer_id', $id)->get();
   
        return response()->json(['success' => true, 'message' => 'successfully updated!', 'description' => "", "code" => "200",
            "data" => $log], 200);
    }
    
    public function updateCustomerWallet(Request $request)
    {
        $data = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'amount' => 'required|numeric|min:1',
            'induction' => 'required|boolean'
        ]);

        $customer = Customer::find($data['customer_id']);
        WalletLog::create([
            'user_id' => auth()->user()->id,
            'customer_id' => $customer->id,
            'last_amount' => $customer->wallet,
            'new_amount' => (float)$data['amount'],
            'action' => $data['induction'] == true ? 'induction': 'deduction'
        ]);

        if ($data['induction']){
            $customer->wallet = $customer->wallet + (float)$data['amount'];
        }else {
            if ($customer->wallet > (float)$data['amount']){
                $customer->wallet = $customer->wallet - (float)$data['amount'];
            }else {
                $customer->wallet =  (float)$data['amount'] - $customer->wallet;
            }
        }

        $customer->save();
        

        return response()->json(['success' => true, 'message' => 'successfully updated!', 'description' => "", "code" => "200",
            "data" => []], 200);
    }
    
    public function chargeWallet(Request $request)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:1'
        ]);
        $customer = Customer::find(auth()->user()->id);
        $countryId = $request->query('countryId');
        $country = Country::where('code', $countryId)->get()->first();

        if ($country === null)
            return response()->json(['data' => [],
                'success' => true, 'message' => 'success', 'description' => 'this service not available in your country!', 'code' => '200'], 200);

        if ($country->code == 'SA') {
            $paymentRes = app(MyFatoorahApiService::class)->SetPaymentMyfatooraWallet($customer, $country, $data['amount']);
            return response()->json(['success' => true, 'data' => $paymentRes,
                'message' => '', 'description' => '', 'code' => '200'], 200);
        }elseif ($country->code == 'AE') {
            $paymentRes = app(MyFatoorahApiService::class)->SetPaymentMyfatooraWallet($customer, $country, $data['amount']);
            return response()->json(['success' => true, 'data' => $paymentRes,
                'message' => '', 'description' => '', 'code' => '200'], 200);
        }

        return response()->json(['success' => false, 'message' => 'something went wrong, contact support!', 'description' => "", "code" => "422",
            "data" => []], 422);
    }

}
