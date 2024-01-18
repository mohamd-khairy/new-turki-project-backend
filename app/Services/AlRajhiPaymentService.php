<?php

namespace App\Services;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Customer;
use App\Models\Order;
use App\Models\TraceError;
use App\Models\Payment;
use App\Models\PaymentType;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\ArbPayment;
use App\Services\CallPaymentNetsuiteApi;

class AlRajhiPaymentService
{

    public function createARBpayment(Customer $customer, Order $order, PaymentType $paymentType, Country $country)
    {
        $lastPayment = Payment::latest('id')->first();

        $data = [
            "ref_no" => GetNextPaymentRefNo($country->code, $lastPayment != null ? $lastPayment->id + 1 : 1),
            "customer_id" => $customer->id,
            "order_ref_no" => $order->ref_no,
            "price" => (float)$order->total_amount_after_discount,
            "payment_type_id" => $paymentType->id,
            "status" => "Waiting for Client", // need to move to enum class
            "description" => "Payment Created", // need to move to enum class
        ];


        // todo: implement this
        //                Notification::send($order->foodOrders[0]->food->restaurant->users, new NewOrder($order));

        $bodyReq = [[
            'id' => 'trs1dWTEB76b8C3',
            'trandata' => $this->trandata($order, $country->code),
            'responseURL' => 'https://merchantpage/PaymentResult.jsp',
            'errorURL' => 'https://merchantpage/PaymentResult.jsp'
        ]];

        $baseUrl = "https://digitalpayments.alrajhibank.com.sa/pg/";
        $endpoint = $baseUrl . "payment/hosted.htm?PaymentID=";
        $options =  ["json" => $bodyReq, "headers" => ["Accept" => "application/json", "Content-Type" => "application/json",]];

        $this->client = new Client();
        $response = $this->client->request(strtoupper("post"), $endpoint, $options);
        $result = [];
        $body = (string)$response->getBody();
        $body = json_decode($body, true);

        if (isset($body[0]['status']) && $body[0]['status'] == 1) {


            $arr = explode(":", trim($body[0]["result"]));


            $data["bank_ref_no"] = $arr[0];
            $payment = Payment::create($data);
            $order->update(['payment_id' => $payment->id, 'paid' => 1]);


            $result['invoiceURL'] = "https://digitalpayments.alrajhibank.com.sa/pg/paymentpage.htm?PaymentID=" . $arr[0];
            $result['success'] = true;

            return $result;
        } else {
            $result['success'] = false;
            $result['error'] = $body;

            return $result;
        }
    }

    public function trandata(Order $order, $countryCode)
    {
        // $currencyCode = "682";
        // if($countryCode == "AE")
        //     $currencyCode = "784";

        // number_format($number, 2, '.', '')

        $details = collect([[
            'id' => "trs1dWTEB76b8C3",
            'amt' => number_format($order->total_amount_after_discount, 2, '.', ''),
            'action' => "1",
            'password' => "Meoj0#T53#Ys#5O",
            'currencyCode' => "682",
            'trackId' =>  $order->ref_no,
            'responseURL' => env('APP_URL') . '/api/v2/final_result?paid=1',
            'errorURL' => env('APP_URL') . '/api/v2/final_result?paid=0',
        ]]);

        $jsonDetails = json_encode($details);
        TraceError::create(['class_name' => "AlRajhiPaymentService::before sending to ARB line:94", 'method_name' => "trandata", 'error_desc' => $jsonDetails]);

        $str = $jsonDetails;
        return $this->encryptAES(($str), "11195079618911195079618911195079");
    }


    /**
     * Update the specified Order in storage.
     *
     * @param int $id
     * @param Request $request
     *
     * @return JsonResponse
     *
     *
     */

    public function Get_Payment(Order $order)
    {

        $payment = Payment::find($order->payment_id);

        $details = collect([[
            'id' => "trs1dWTEB76b8C3",
            'amt' => $payment->price . "",
            'action' => "8",
            'password' => "Meoj0#T53#Ys#5O",
            'currencyCode' => "682",
            'trackId' => $order->id . "",
            "udf5" => "TrackID",
            "transId" => $order->id . "",
        ]]);


        $en = $this->encryptAES((json_encode($details)), "11195079618911195079618911195079");

        $bodyReq = [[
            'id' => 'trs1dWTEB76b8C3',
            'trandata' => $en
        ]];

        $baseUrl = "https://digitalpayments.alrajhibank.com.sa/pg/";
        $endpoint = $baseUrl . "payment/hosted.htm";
        $options = ["json" => $bodyReq, "headers" => ["Accept" => "application/json", "Content-Type" => "application/json"]];
        $this->client = new Client();
        $response = $this->client->request(strtoupper("post"), $endpoint, $options);


        $body = (string)$response->getBody();
        $body = json_decode($body, true);
    }

    function show_final_result(Request $request)
    {
        TraceError::create(['class_name' => "AlRajhiPaymentService", 'method_name' => "show_final_result", 'error_desc' => json_encode($request->all())]);

        $validatedData = $request->validate([
            'trandata' => 'sometimes',
        ]);



        $decryptedData = null;
        if (isset($validatedData['trandata'])) {
            $decrypt = $this->decryptAES($validatedData['trandata'], "11195079618911195079618911195079");

            $decryptedData = json_decode(urldecode($decrypt), true);
            $decryptedData = $decryptedData[0];
        }


        $logoPath = config('app.url') . '/storage/assets/logo.png';
        $paymentId = $decryptedData['paymentId'] ?? 'N/A';
        $paymentResult = $decryptedData['result'] ?? 'N/A';
// #e7c05d;/images/PCh7OpSSPm1608014433.png
        $html_head = '<!DOCTYPE html>
                <html lang="en-US">
                <meta http-equiv="content-type" content="text/html;charset=UTF-8" />

                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width">
                    <title>تركي للذبائح</title>
                    <meta property="og:type" content="website"/>
                    <meta property="og:url" content="Food Explorer"/>
                    <meta property="og:title" content="Food Explorer"/>
                    <meta property="og:image" content="/images/PCh7OpSSPm1608014433.png"/>
                    <meta property="og:image:width" content="250px"/>
                    <meta property="og:image:height" content="250px"/>
                    <meta property="og:site_name" content="Food Explorer"/>
                    <meta property="og:description" content="Food Item Online Ordering System"/>
                    <meta property="og:keyword" content="Online,food"/>
                    <meta name="viewport" content="width=device-width, initial-scale=1">
                    <style>
                        body h1, body h2, body h3, body h4, body h5, body h6, p, .lte-header, .menu-item a, .citis, .lte-btn,#btnflag,#foodsection *
                        , #additemformsection *, #cartsection *,#myordersection *, .lte-content,#checkoutsection *
                        {}
                        body{
                            text-align: center;
                            margin: 0;
                            background: url();
                            color: #000;
                            font-size: 16px;
                            line-height: 2;
                            padding: 30px;
                            background-size: contain;
                        }
                        .lte-btn {
                            background-color: #6d1442;
                            border-radius: 30px;
                            padding: 9px 6px;
                            color: #6c3434;
                            margin: 22px;
                        }
                        .centerimglogo{
                             display: block;
                             margin-left: auto;
                             margin-right: auto;
                             text-align: center;
                             height: 100%;
                        }

                        .image-frame{
                            height: 250px;
                        }

                        .turkeyd {
                            margin-top: 20px;
                        }

                    </style>
                    ';
        $html_btn1 = '<div onclick="invokeNative()" class="lte-btn">
                    <span class="lte-btn-inner">
                        <span class="btnsm">
                        ';
        $html_btn2 = '
                    </span>
                </span>
                </div>';
        $html_sec1 = '<section id="turkeysection">
                    <div class="image-frame"><img src="' . $logoPath . '" class="centerimglogo"></div>
                    <div class="row">
                        <div class="turkeyd col-lg-6">

                        ';
        $html_sec2 = '
                    </div>
                    <span></span>
                </div>
                </section>';

        if (isset($decryptedData['result']) && $decryptedData['result'] == "CAPTURED") {

            return $html_head . '


                    <head>
                        <script type="text/javascript">
                            function invokeNative() {
                                MessageInvoker.postMessage("1");
                            }
                        </script> </head>

                    <body>
                    ' . $html_sec1 . '
                    تمت عملية الدفع بنجاح
                    ' . $html_sec2 . '
                    </body>

                    </html>
                  ';
        } else {
            return $html_head . '


                    <head>
                        <script type="text/javascript">
                        function invokeNative() {
                            MessageInvoker.postMessage("0");
                        }
                        </script> </head>

                    <body>
                    ' . $html_sec1 . '
                    لم يتم الدفع ... في حال أنك واثق من نجاح عملية الدفع الرجاء مراجعة قسم المبيعات , رقم الدفعه
                    ' . $html_sec2 . '
                     </body>

                    </html>
              ' . $paymentId . '---' . $paymentResult;
        }
    }

    function encryptAES($str, $key)
    {
        $str = $this->pkcs5_pad($str);
        $encrypted = openssl_encrypt($str, "AES-256-CBC", $key, OPENSSL_ZERO_PADDING, "PGKEYENCDECIVSPC");
        $encrypted = base64_decode($encrypted);
        $encrypted = unpack('C*', ($encrypted));
        $encrypted = $this->byteArray2Hex($encrypted);
        $encrypted = urlencode($encrypted);
        return $encrypted;
    }

    function pkcs5_pad($text)
    {
        $blocksize = openssl_cipher_iv_length("AES-256-CBC");
        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);
    }

    function byteArray2Hex($byteArray)
    {
        $chars = array_map("chr", $byteArray);
        $bin = join($chars);
        return bin2hex($bin);
    }

    public function Get_Payment_Status_ARB(Request $request)
    {
        TraceError::create(['class_name' => "AlRajhiPaymentService", 'method_name' => "Get_Payment_Status_ARB", 'error_desc' => json_encode($request->all())]);
        $validatedData = $request->post();


        if (isset($validatedData['trandata'])) {
            $decrypt = $this->decryptAES($validatedData['trandata'], "11195079618911195079618911195079");

            $decryptedData = json_decode(urldecode($decrypt), true);

            $decryptedData = $decryptedData[0];

            $decryptedDate['payload'] = $validatedData['trandata'];


            if (isset($decryptedData['paymentId'])) {
                $arp = ArbPayment::where('paymentId', $decryptedData['paymentId'])->get()->first();

                if ($arp) {
                    TraceError::create(['class_name' => "AlRajhiPaymentService", 'method_name' => "Get_Payment_Status_ARB", 'error_desc' => 'duplicate, no action took!: ' . $decrypt]);
                    return;
                }
            }

            $arp = ArbPayment::create($decryptedData);


            // why this is not correct? because you are creating the record and then checking if it exits by first, which will be there for sure. so i just swapped it.

            // $arp = ArbPayment::create($decryptedData);

            // $arp = ArbPayment::where('paymentId', $decryptedData['paymentId'])->get()->first();
            //   if ($arp) {
            // TraceError::create(['class_name'=> "AlRajhiPaymentService", 'method_name'=>"Get_Payment_Status_ARB", 'error_desc' => 'duplicate']);
            //      }

            $order = Order::find($decryptedData["trackId"]);
            $payment = Payment::find($order->payment_id);

            if (isset($decryptedData['result'])) {

                if ($decryptedData['result'] == "CAPTURED") {
                    $payment->update([
                        "description" => $decryptedData['result'],
                        "status" => "Paid",
                        "price" => (float)$decryptedData['amt'],

                    ]);

                    $objOrder = $order;
                    $order = $order->toArray();
                    //if($arp['duplicate'] == null){
                    $callPaymentNetsuiteApi = new CallPaymentNetsuiteApi();
                    $res = $callPaymentNetsuiteApi->sendUpdatePaymentToNS($order, $request);
                    //}
                } else {
                    $payment->update([
                        "description" => $decryptedData['result'],
                        "status" => "Waiting for Client",
                    ]);
                    // return response()->json([['status' => 2]]);
                }
            } elseif (isset($decryptedData['error'])) {
                $payment->update([
                    "description" => $decryptedData['error'],
                    "status" => "ERROR",
                ]);
                //  return response()->json([['status' => 2]]);
            }
        } else {

            // ArbPayment::create([
            //     ["payload"] => json_encode($request->post())
            //     ]);
            //  return response()->json([['status' => 2]]);
        }
        // return response()->json([['status' => 1]]);
        return $this->show_final_result($request);
    }

    function decryptAES($code, $key)
    {
        $code = $this->hex2ByteArray(trim($code));
        $code = $this->byteArray2String($code);
        $iv = "PGKEYENCDECIVSPC";
        $code = base64_encode($code);
        $decrypted = openssl_decrypt(
            $code,
            'AES-256-CBC',
            $key,
            OPENSSL_ZERO_PADDING,
            $iv
        );
        return $this->pkcs5_unpad($decrypted);
    }

    function hex2ByteArray($hexString)
    {
        $string = hex2bin($hexString);
        return unpack('C*', $string);
    }

    function byteArray2String($byteArray)
    {
        $chars = array_map("chr", $byteArray);
        return join($chars);
    }

    function pkcs5_unpad($text)
    {
        $pad = ord($text[strlen($text) - 1]);
        if ($pad > strlen($text)) {
            return false;
        }
        if (strspn($text, chr($pad), strlen($text) - $pad) != $pad) {
            return false;
        }
        return substr($text, 0, -1 * $pad);
    }
}
