<?php


namespace App\Services;


use App\Models\Customer;
use App\Models\OrderProduct;
use App\Models\Address;
use App\Models\Product;
use App\Models\Shalwata;
use App\Models\Size;
use App\Models\Preparation;
use App\Models\Cut;
use App\Models\Order;
use App\Models\City;
use App\Models\Discount;
use App\Models\TraceError;
use App\Models\DeliveryPeriod;
use App\Models\TempCouponProducts;
use App\Services\CallNetsuiteApi;

define("NETSUITE_URL", 'https://6982375.restlets.api.netsuite.com/app/site/hosting/restlet.nl');
define("NETSUITE_SCRIPT_ID", '202');
define("NETSUITE_DEPLOY_ID", '1');
define("NETSUITE_ACCOUNT", '6982375');
define("NETSUITE_CONSUMER_KEY", 'be391aae5fd902df5c617f94e0a7e4d4b0ec32b4ddb0217af1f1b28fcc0c31d2');
define("NETSUITE_CONSUMER_SECRET", '65526b6186fec7e804d0898e487bf81da749b9f80fe6d7d5d26586f58abd16e2');
define("NETSUITE_TOKEN_ID", '15d3742a9d9f04947bc119d4fb7a596f69228b789331601b8e40a5b3dc4a3edd');
define("NETSUITE_TOKEN_SECRET", '534f102aee25369f2df33408d084e244ce68ae0aacf125562f230f396b8be11e');


class CallOrderNetsuiteApi{

    ///send order netsuite

    public function sendOrderToNS($order, $request)
    {
        TraceError::create(['class_name' => "CallOrderNetsuiteApi", 'method_name' => 'sendOrderToNS', 'error_desc' => '-'.$order['ref_no'].'- sending order to Netsuite :'.json_encode($order).', '. json_encode($request->all())]);
        
        
        $customer = Customer::find($order['customer_id']);
        $orderProduct = OrderProduct::where('order_ref_no', $order['ref_no'])->get();
        $address = Address::find($order['address_id']);
        $city = City::find($address['city_id']);
        $deliveryPeriod = DeliveryPeriod::find($order['delivery_period_id']);

        $coupon = $order['applied_discount_code'];

        if($coupon != null)
        {
            $coupon = Discount::where('code',$order['applied_discount_code'])->get();
        }

        $deliveryDate = (date("Y") . '-' . $order['delivery_date']);
        $deliveryDate = date("d/m/Y", strtotime($deliveryDate));

        $details = [
            'recordtype' => 'customrecord_trk_order',
            'name' => $customer->name,
            'custrecord_trk_order_phone' => $customer->mobile,
            'custrecord_trk_order_phone2' => '066665555',
            'custrecord_trk_order_size' => '',
            // 'custrecord_trk_down_payment_amount'=> $order['total'] == null ? 0:$order['total'],
            'custrecord_trk_down_payment_amount' => '0',
            'description' => $address->comment,
            'adress' => $address->address,
            'Longitude' => $address->long,
            'Latitude' => $address->lat,
            'City' => $city->integrate_id,
            'externalid' => $order['ref_no'],
            'deliverytime'=> $deliveryPeriod->integrate_id,
            'deliverydate'=> $deliveryDate,
            'notes'=> $order['comment'],
        ];


        for ($j = 0; $j < count($orderProduct); $j++) {

            $qty = $orderProduct[$j]['quantity'] . "";

            //  $product = Product::find($orderProduct[$j]['product_id']);
            $size = Size::find($orderProduct[$j]['size_id']);
            $preparation = Preparation::find($orderProduct[$j]['preparation_id']);
            $cut = Cut::find($orderProduct[$j]['cut_id']);
            $shalwata = $orderProduct[$j]['shalwata_id'];
            
              
            $is_Ras= $orderProduct[$j]['is_Ras'];
            $is_kwar3 = $orderProduct[$j]['is_kwar3'];
            $is_lyh = $orderProduct[$j]['is_lyh'];
            $is_karashah = $orderProduct[$j]['is_karashah'];

            //  $productInd = $product->integrate_id;
            $sizeInd = $size->integrate_id;
            $preparationInd = $preparation == null? 2: $preparation->integrate_id;
            $cutInd = $cut == null? 16: $cut->integrate_id;

            $details["item"][$j] = [
                'item' => $sizeInd,
                'quantity' => $qty,
                'cutting' => $cutInd,
                'prepair' => $preparationInd,
                'shlwata' => $shalwata != null ? 'T':'F',
                'ras' => $is_Ras == 1 ? 'T':'F',
                'kwar3' => $is_kwar3 == 1 ? 'T':'F',
                'lya' => $is_lyh == 1 ? 'T':'F',
                'krsha' => $is_karashah == 1 ? 'T':'F'
            ];
        }


        if($coupon != null && $coupon[0]['integrate_id'] != null)
        {
            $details["item"][count($details["item"])] = ['item' => $coupon[0]['integrate_id']];
        }

        $data_string = json_encode($details);

        TraceError::create(['class_name'=> "CallOrderNetsuiteApi::before sending to NS", 'method_name'=>"sendOrderToNS 111", 'error_desc' => $data_string]);

        $oauth_nonce = md5(mt_rand());
        $oauth_timestamp = time();
        $oauth_signature_method = 'HMAC-SHA256';
        $oauth_version = "1.0";

        $base_string =
            "POST&" . urlencode(NETSUITE_URL) . "&" .
            urlencode(
                "deploy=" . NETSUITE_DEPLOY_ID
                . "&oauth_consumer_key=" . NETSUITE_CONSUMER_KEY
                . "&oauth_nonce=" . $oauth_nonce
                . "&oauth_signature_method=" . $oauth_signature_method
                . "&oauth_timestamp=" . $oauth_timestamp
                . "&oauth_token=" . NETSUITE_TOKEN_ID
                . "&oauth_version=" . $oauth_version . "&realm=" . NETSUITE_ACCOUNT
                . "&script=" . NETSUITE_SCRIPT_ID
            );
        $sig_string = urlencode(NETSUITE_CONSUMER_SECRET) . '&' . urlencode(NETSUITE_TOKEN_SECRET);
        $signature = base64_encode(hash_hmac("sha256", $base_string, $sig_string, true));

        $auth_header = "OAuth "
            . 'oauth_signature="' . rawurlencode($signature) . '", '
            . 'oauth_version="' . rawurlencode($oauth_version) . '", '
            . 'oauth_nonce="' . rawurlencode($oauth_nonce) . '", '
            . 'oauth_signature_method="' . rawurlencode($oauth_signature_method) . '", '
            . 'oauth_consumer_key="' . rawurlencode(NETSUITE_CONSUMER_KEY) . '", '
            . 'oauth_token="' . rawurlencode(NETSUITE_TOKEN_ID) . '", '
            . 'oauth_timestamp="' . rawurlencode($oauth_timestamp) . '", '
            . 'realm="' . rawurlencode(NETSUITE_ACCOUNT) . '"';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, NETSUITE_URL . '?&script=' . NETSUITE_SCRIPT_ID . '&deploy=' . NETSUITE_DEPLOY_ID . '&realm=' . NETSUITE_ACCOUNT);
        curl_setopt($ch, CURLOPT_POST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: ' . $auth_header,
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data_string)
        ]);


        $response = curl_exec($ch);
        
     
 TraceError::create(['class_name' => "CallOrderNetsuiteApi", 'method_name' => 'sendOrderToNS', 'error_desc' => '-'.$order['ref_no'].'- sent order to Netsuite :'.json_encode($response)]);
        
//   TraceError::create(['class_name'=> "CallOrderNetsuiteApi::responce", 'method_name'=>"sendOrderToNS 159", 'error_desc' => json_encode($response)]);

        curl_close($ch);
        
    //      if(strpos($response, 'error')){
    //   TraceError::create(['class_name'=> "CallNetsuiteApi::after sending to NS", 'method_name'=>"there error", 'error_desc' => $response]);
      
    //   $res = app(CallNetsuiteApi::class)->sendCustomerToNS($customer , $request);
      
    //         if(!isset($res->status)){
    //              $customer->update(['integrate_id' => $res->id]);

    //              $res = $this->sendOrderToNS($order , $request);
                 
    //               }
    //   }else{
    //   return json_decode($response);
    //       }

        return json_decode($response);
    }
    // public function sendOrderToNSV2()
    // {
    //     $order = Order::where("ref_no", "SAO000115596")->get()->first()->toArray();
    //     $customer = Customer::find($order['customer_id']);
    //     $orderProduct = OrderProduct::where('order_ref_no', $order['ref_no'])->get();
    //     $address = Address::find($order['address_id']);
    //     $city = City::find($address['city_id']);
    //     $deliveryPeriod = DeliveryPeriod::find($order['delivery_period_id']);

    //     $coupon = $order['applied_discount_code'];

    //     if($coupon != null)
    //     {
    //         $coupon = Discount::where('code',$order['applied_discount_code'])->get();
    //     }

    //     $deliveryDate = (date("Y") . '-' . $order['delivery_date']);
    //     $deliveryDate = date("d/m/Y", strtotime($deliveryDate));

    //     $details = [
    //         'recordtype' => 'customrecord_trk_order',
    //         'name' => $customer->name,
    //         'custrecord_trk_order_phone' => $customer->mobile,
    //         'custrecord_trk_order_phone2' => '066665555',
    //         'custrecord_trk_order_size' => '',
    //         'custrecord_trk_down_payment_amount' => '0',
    //         'description' => $address->comment,
    //         'adress' => $address->address,
    //         'Longitude' => $address->long,
    //         'Latitude' => $address->lat,
    //         'City' => $city->integrate_id,
    //         'externalid' => $order['ref_no'],
    //         'deliverytime'=> $deliveryPeriod->integrate_id,
    //         'deliverydate'=> $deliveryDate,
    //         'notes'=> $order['comment'],
    //     ];

    //     $applicableProduct = TempCouponProducts::where([["order_id", $order['ref_no']], ["coupon_code", $order['applied_discount_code']]])->first();
        
    //     $applicableProductIds = json_decode($applicableProduct->product_ids);
        
    //     for ($j = 0; $j < count($orderProduct); $j++) {

    //         $qty = $orderProduct[$j]['quantity'] . "";
    //         $size = Size::find($orderProduct[$j]['size_id']);
    //         $preparation = Preparation::find($orderProduct[$j]['preparation_id']);
    //         $cut = Cut::find($orderProduct[$j]['cut_id']);
    //         $shalwata = $orderProduct[$j]['shalwata_id'];
            
              
    //         $is_Ras= $orderProduct[$j]['is_Ras'];
    //         $is_kwar3 = $orderProduct[$j]['is_kwar3'];
    //         $is_lyh = $orderProduct[$j]['is_lyh'];
    //         $is_karashah = $orderProduct[$j]['is_karashah'];
    //         $sizeInd = $size->integrate_id;
    //         $preparationInd = $preparation == null? 2: $preparation->integrate_id;
    //         $cutInd = $cut == null? 16: $cut->integrate_id;

    //         $details["item"][$j] = [
    //             'item' => $sizeInd,
    //             'quantity' => $qty,
    //             'cutting' => $cutInd,
    //             'prepair' => $preparationInd,
    //             'shlwata' => $shalwata != null ? 'T':'F',
    //             'shlwata' => $shalwata != null ? 'T':'F',
    //             'ras' => $is_Ras == 1 ? 'T':'F',
    //             'kwar3' => $is_kwar3 == 1 ? 'T':'F',
    //             'lya' => $is_lyh == 1 ? 'T':'F',
    //             'krsha' => $is_karashah == 1 ? 'T':'F'
    //         ];
    //     }
        
        
    //     for ($j = 0; $j < count($applicableProductIds); $j++) {
    //         if(in_array($orderProduct[$j]['product_id'], $applicableProductIds)){
    //             $details["item"][$j] = [
    //             'item' => $coupon[0]['integrate_id']
    //         ];
    //         }
    //     }


    //     if($coupon != null && $coupon[0]['integrate_id'] != null)
    //     {
    //         $details["item"][count($details["item"])] = ['item' => $coupon[0]['integrate_id']];
    //     }

    //     $data_string = json_encode($details);
        
    //     echo $data_string;

    // }
}
