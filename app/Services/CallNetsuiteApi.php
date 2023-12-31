<?php


namespace App\Services;

use App\Models\TraceError;
use Illuminate\Support\Str;

define("NETSUITE_URLC", 'https://6982375.restlets.api.netsuite.com/app/site/hosting/restlet.nl');
define("NETSUITE_SCRIPT_IDC", '200');
define("NETSUITE_DEPLOY_IDC", '1');
define("NETSUITE_ACCOUNTC", '6982375');
define("NETSUITE_CONSUMER_KEYC", 'be391aae5fd902df5c617f94e0a7e4d4b0ec32b4ddb0217af1f1b28fcc0c31d2');
define("NETSUITE_CONSUMER_SECRETC", '65526b6186fec7e804d0898e487bf81da749b9f80fe6d7d5d26586f58abd16e2');
define("NETSUITE_TOKEN_IDC", '15d3742a9d9f04947bc119d4fb7a596f69228b789331601b8e40a5b3dc4a3edd');
define("NETSUITE_TOKEN_SECRETC", '534f102aee25369f2df33408d084e244ce68ae0aacf125562f230f396b8be11e');


class CallNetsuiteApi{
        
    public function sendCustomerToNS($customer , $request){
        
         TraceError::create(['class_name'=> "CallNetsuiteApi:: coming from the app", 'method_name'=>"sendCustomerToNS", 'error_desc' => json_encode($request->all())]);
          $moblie_code = Str::substr($customer->mobile, 0, 4);
         // dd($moblie_code);
        if($moblie_code == "+966"){
            $code = "10";
         } else if($moblie_code == "+971"){
                   $code = "3";
             }

        $details =[
             'recordtype' => "customer",
             'entityid' => $customer['name'].$customer['id'],
             'companyname' => "integration Company",
             'subsidiary' => $code,
             'email' => $customer['email']. "",
             'phone' => $customer['mobile']. "",
             'externalid' => "v2".$customer['id']. "",
         ];


     $data_string = json_encode($details);
     
        
        TraceError::create(['class_name'=> "CallNetsuiteApi::before sending to NS", 'method_name'=>"sendCustomerToNS", 'error_desc' => $data_string]);

     $oauth_nonce = md5(mt_rand());
     $oauth_timestamp = time();
     $oauth_signature_method = 'HMAC-SHA256';
     $oauth_version = "1.0";

     $base_string =
         "POST&" . urlencode(NETSUITE_URLC) . "&" .
         urlencode(
             "deploy=" . NETSUITE_DEPLOY_IDC
           . "&oauth_consumer_key=" . NETSUITE_CONSUMER_KEYC
           . "&oauth_nonce=" . $oauth_nonce
           . "&oauth_signature_method=" . $oauth_signature_method
           . "&oauth_timestamp=" . $oauth_timestamp
           . "&oauth_token=" . NETSUITE_TOKEN_IDC
           . "&oauth_version=" . $oauth_version . "&realm=" . NETSUITE_ACCOUNTC
           . "&script=" . NETSUITE_SCRIPT_IDC
         );
     $sig_string = urlencode(NETSUITE_CONSUMER_SECRETC) . '&' . urlencode(NETSUITE_TOKEN_SECRETC);
     $signature = base64_encode(hash_hmac("sha256", $base_string, $sig_string, true));

     $auth_header = "OAuth "
         . 'oauth_signature="' . rawurlencode($signature) . '", '
         . 'oauth_version="' . rawurlencode($oauth_version) . '", '
         . 'oauth_nonce="' . rawurlencode($oauth_nonce) . '", '
         . 'oauth_signature_method="' . rawurlencode($oauth_signature_method) . '", '
         . 'oauth_consumer_key="' . rawurlencode(NETSUITE_CONSUMER_KEYC) . '", '
         . 'oauth_token="' . rawurlencode(NETSUITE_TOKEN_IDC) . '", '
         . 'oauth_timestamp="' . rawurlencode($oauth_timestamp) . '", '
         . 'realm="' . rawurlencode(NETSUITE_ACCOUNTC) .'"';

     $ch = curl_init();
     curl_setopt($ch, CURLOPT_URL, NETSUITE_URLC . '?&script=' . NETSUITE_SCRIPT_IDC . '&deploy=' . NETSUITE_DEPLOY_IDC . '&realm=' . NETSUITE_ACCOUNTC);
     curl_setopt($ch, CURLOPT_POST, "POST");
     curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
     curl_setopt($ch, CURLOPT_HTTPHEADER, [
         'Authorization: ' . $auth_header,
         'Content-Type: application/json',
         'Content-Length: ' . strlen($data_string)
     ]);

    $response = curl_exec($ch);
     curl_close($ch);
     
  
  if(strpos($response, 'error')){
      TraceError::create(['class_name'=> "CallNetsuiteApi::after sending to NS", 'method_name'=>"there error", 'error_desc' => $response]);
      return $response;
  }else{
      return json_decode($response);
  }
  
 }



 //   function registerNS(Request $request ,Order $order)
//     {
       
//       	$customer = Customer::find($order['customer_id']);
       	
//          $mobile = $customer->moblie;
//    
//             $mobile->save();

          
//             $res = $this->sendCustomerToNS($customer , $request);

//             if(!isset($res->status)){
                
            
//                 $customer->update(['integrate_id' => $res->id]);
                

             //return response()->json(['success' => true ,'data'=> $customer,
            //   'message'=> 'Customer retrieved successfully', 'description'=> '', 'code'=>'200'],200);
                
//             
//             }
 
    
}