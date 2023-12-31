<?php


namespace App\Services;

use App\Models\Payment;
use App\Models\TraceError;

define("NETSUITE_URL", 'https://6982375.restlets.api.netsuite.com/app/site/hosting/restlet.nl');
define("NETSUITE_SCRIPT_ID", '220');
define("NETSUITE_DEPLOY_ID", '1');
define("NETSUITE_ACCOUNT", '6982375');
define("NETSUITE_CONSUMER_KEY", 'be391aae5fd902df5c617f94e0a7e4d4b0ec32b4ddb0217af1f1b28fcc0c31d2');
define("NETSUITE_CONSUMER_SECRET", '65526b6186fec7e804d0898e487bf81da749b9f80fe6d7d5d26586f58abd16e2');
define("NETSUITE_TOKEN_ID", '15d3742a9d9f04947bc119d4fb7a596f69228b789331601b8e40a5b3dc4a3edd');
define("NETSUITE_TOKEN_SECRET", '534f102aee25369f2df33408d084e244ce68ae0aacf125562f230f396b8be11e');


class CallPaymentNetsuiteApi{
     
 ///send order netsuite

 public function sendUpdatePaymentToNS($order , $request){
     
       TraceError::create(['class_name'=> "CallPaymentNetsuiteApi 25:: coming from the app", 'method_name'=>"sendUpdatePaymentToNS", 'error_desc' => json_encode($request->all())]);
       
         TraceError::create(['class_name'=> "CallPaymentNetsuiteApi 26 :: coming from the app", 'method_name'=>"sendUpdatePaymentToNS", 'error_desc' => json_encode($order)]);
       
    $payment = Payment:: find($order['payment_id']);
    $paymentArray = $payment->toArray();
  
 
       $body = [
              "salesOrderId" => $order['saleOrderId'],
              "value" => $paymentArray['price']
              ];
        
  $data_string = json_encode($body);
  
 TraceError::create(['class_name'=> "CallPaymentNetsuiteApi::bodyRequest", 'method_name'=>"sendUpdatePaymentToNS", 'error_desc' => $data_string]);
  $oauth_nonce = md5(mt_rand());
  $oauth_timestamp = time();
  $oauth_signature_method = 'HMAC-SHA256';
  $oauth_version = "1.0";


  $base_string =
      "PUT&" . urlencode(NETSUITE_URL) . "&" .
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
      . 'realm="' . rawurlencode(NETSUITE_ACCOUNT) .'"';


  $ch = curl_init();

  curl_setopt($ch, CURLOPT_URL, NETSUITE_URL . '?&script=' . NETSUITE_SCRIPT_ID . '&deploy=' . NETSUITE_DEPLOY_ID . '&realm=' . NETSUITE_ACCOUNT);
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
  curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'Authorization: ' . $auth_header,
      'Content-Type: application/json',
      'Content-Length: ' . strlen($data_string)
  ]);
  
  

  $response = curl_exec($ch);
 
 TraceError::create(['class_name'=> "CallPaymentNetsuiteApiResponce", 'method_name'=>"sendUpdatePaymentToNS 88", 'error_desc' => json_encode($response)]);

  curl_close($ch);
  
  $s = json_decode($response);
    return $s;

}

    
}