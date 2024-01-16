<?php

namespace App\Services;


use App\Models\Payment;
use Illuminate\Http\Request;
use \stdClass;
use App\Models\Country;
use App\Models\Customer;
use App\Models\Order;
use App\Models\TraceError;
use App\Models\PaymentType;
use App\Models\NgeniusPayment;

class NgeniusPaymentService
{

    public function accessToken()
    {
        $apikey = "YmEzYjAwYmUtNWJkNi00NzE3LWE0MDUtYzNmYzcxM2NmODNmOjBkMDk1MmZmLWMyOWUtNGJjOS05OTAxLTJlNDBiMTk4ZGFlNQ==";     // enter your API key here
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api-gateway.ngenius-payments.com/identity/auth/access-token");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "accept: application/vnd.ni-identity.v1+json",
            "authorization: Basic ".$apikey,
            "content-type: application/vnd.ni-identity.v1+json"));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,  "{\"realmName\":\"networkinternational\"}");
        $output = json_decode(curl_exec($ch));
        $access_token = $output->access_token;
        return $output;
    }

    public function createNgeniusPayment(Customer $customer, Order $order, PaymentType $paymentType, Country $country)
    {
        $lastPayment = Payment::latest('id')->first();

            $data = [
                "ref_no" => GetNextPaymentRefNo($country->code, $lastPayment != null ? $lastPayment->id + 1 : 1),
                "customer_id" => $customer->id,
                "order_ref_no" => $order->ref_no,
                "price" => (double)$order->total_amount_after_discount,
                "payment_type_id" => $paymentType->id,
                "status" => "Waiting for Client", // need to move to enum class
                "description" => "Payment Created", // need to move to enum class
            ];


           $priceArr = explode(".", $order->total_amount_after_discount . "");
            $postData = new StdClass();
            $postData->action = "PURCHASE";
            $postData->action = "PURCHASE";
            $postData->amount = new StdClass();
            $postData->amount->currencyCode = $country->currency_en;
             $postData->amount->value = $priceArr[0] . (isset($priceArr[1]) ? $priceArr[1] : '00') ;
            //  $postData->amount->value = $order->total_amount_after_discount;

            $outlet = "4f2019d2-5bff-45eb-8aae-8d13e5211466";

            $token = $this->accessToken()->access_token;

            $json = json_encode($postData);

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, "https://api-gateway.ngenius-payments.com/transactions/outlets/".$outlet."/orders");
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Authorization: Bearer ".$token,
            "Content-Type: application/vnd.ni-payment.v2+json",
            "Accept: application/vnd.ni-payment.v2+json"));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);

            $output = json_decode(curl_exec($ch));

            $order_reference = $output->reference;
            $order_paypage_url = $output->_links->payment->href;

             curl_close($ch);

         //  return  response()->json($output->_links->payment->href);

            if(isset($output->reference)){
                $data["bank_ref_no"] = $output->reference;

                $payment = Payment::create($data);
                $order->update(['payment_id' => $payment->id , 'paid' => 1]);

                $result['invoiceURL'] = $output->_links->payment->href;
                $result['success'] = true;
                return $result;
            }else{
                $result['success'] = false;
                $result['error'] = $output;

                return $result;
            }

    }


    public function response(Request $request)
    {

    $outlet = "4f2019d2-5bff-45eb-8aae-8d13e5211466";

    $token = $this->accessToken()->access_token;

      $curl = curl_init();

      curl_setopt_array($curl, array(
      CURLOPT_URL => "https://api-gateway.ngenius-payments.com/transactions/outlets/".$outlet."/orders/2e95aad0-b89a-4668-8e06-3abbcd715041",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'GET',
      CURLOPT_HTTPHEADER => array(
      "Authorization: Bearer ".$token.""
  ),
));

$response = curl_exec($curl);

curl_close($curl);
echo $response;

    }

     public function webhookNgenius(Request $request)
    {
        $outlet = "4f2019d2-5bff-45eb-8aae-8d13e5211466";

         TraceError::create(['class_name' => "NgeniusPaymentService", 'method_name' => 'webhookNgenius', 'error_desc' => json_encode($request->all())]);

         $data = $request->post();

         $entity=  [
            'order_id' => $data['order']['reference'],
            'paymentId' => $data['order']['_id'],
            'eventId' => $data['eventId'],
            'eventName' => $data['eventName'],
            'ref' => $data['order']['reference'],
            'paymentTimestamp' => $data['order']['createDateTime'],
            'amount' => $data['order']['amount']['value'],
            'currencyCode' => $data['order']['amount']['currencyCode'],
            'result' =>  $data['eventName'],
            'payload' =>json_encode($request->all()),
            'outletId' => $data['order']['outletId']
             ];

         $npayment = NgeniusPayment::create($entity);

             if($outlet !== $npayment->outletId){
                 TraceError::create(['class_name' => "NgeniusPaymentService", 'method_name' => 'webhookNgenius', 'error_desc' => '-'.$data['order']['reference'].'- Payment update failed :'.json_encode($data)]);
             }

             else if($npayment->eventName === "PURCHASED"){
                 $payment = Payment::where('bank_ref_no',$npayment->order_id)->get()->last();
                 if($payment != null){
                    $order = Order::where('ref_no', $payment->order_ref_no)->get()->last();
                    TraceError::create(['class_name' => "NgeniusPaymentService", 'method_name' => 'webhookNgenius', 'error_desc' => '-'.$payment->ref_no.'- sending payment update to Netsuite :'.json_encode($order)]);

                    $callPaymentNetsuiteApi = new CallPaymentNetsuiteApi();
                    $res = $callPaymentNetsuiteApi->sendUpdatePaymentToNS($order->toArray() , $request);
                    TraceError::create(['class_name' => "NgeniusPaymentService", 'method_name' => 'webhookNgenius', 'error_desc' => '-'.$payment->ref_no.'- sent payment update to Netsuite :'.json_encode($res)]);
                    dd($res);
                 }else {
                     TraceError::create(['class_name' => "NgeniusPaymentService", 'method_name' => 'webhookNgenius', 'error_desc' => '-'.$data['order']['reference'].'- bank ref for payment update not found :'.json_encode($request)]);
                 }
             }else {
                 TraceError::create(['class_name' => "NgeniusPaymentService", 'method_name' => 'webhookNgenius', 'error_desc' => '-'.$data['order']['reference'].'- Payment update failed :'.json_encode($data)]);
             }


         return response()->json(['data' => [],
                'success' => true, 'message' => 'success', 'description' => '', 'code' => '200'], 200);
    }

}
