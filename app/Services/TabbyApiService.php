<?php

namespace App\Services;


use App\Models\City;
use App\Models\Country;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Shalwata;
use App\Models\Size;
use App\Models\TraceError;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\PaymentLog;
use App\Models\Customer;

class TabbyApiService
{
    public function checkoutTabby($customer, $address, $order, $country)
    {
        $lastPayment = Payment::latest('id')->first();

        $createPayment = [
            "ref_no" => GetNextPaymentRefNo($country->code, $lastPayment != null ? $lastPayment->id + 1 : 1),
            "customer_id" => $customer->id,
            "order_ref_no" => $order->ref_no,
            "price" => (float)$order->total_amount_after_discount,
            "payment_type_id" => 7, //tabby
            "status" => "Waiting for the client", // need to move to enum class
            "description" => "Payment Created", // need to move to enum class
        ];

        $merchantCode = "";
        if ($country->code == "SA") {
            $merchantCode = "TD_APP";
        } elseif ($country->code == "AE") {
            $merchantCode = "TD_APPAE";
        } else {
            $merchantCode = "N/A";
        }


        $city = City::find($address['city_id']);
        $Country = Country::find($address['country_id']);
        $orderProduct = OrderProduct::where('order_ref_no', $order['ref_no'])->get();
        $items = [];
        for ($j = 0; $j < count($orderProduct); $j++) {
            $qty = $orderProduct[$j]['quantity'];
            $size = Size::find($orderProduct[$j]['size_id']);
            $shalwata = $orderProduct[$j]['shalwata_id'] == null ? null : Shalwata::find($orderProduct[$j]['shalwata_id']);
            $unitPrice = $size->sale_price;

            if ($shalwata != null) {
                $unitPrice = $unitPrice + $shalwata->price;
            }

            $item = [
                "title" => $size->name_ar,
                "quantity" => (int)$qty,
                "unit_price" => $unitPrice,
                "discount_amount" => "0.00",
                "reference_id" => (string)$size->id,
                "category" => "string",
            ];

            array_push($items, $item);
        }


        $data = [
            "payment" => [
                "amount" => $order->total_amount,
                "currency" => $Country->currency_en,
                "description" => $order->comment,
                "buyer" => [
                    "phone" => $customer->mobile,
                    "email" => "card.success@tabby.ai",
                    "name" => $customer->name
                ],
                "buyer_history" => [
                    "registered_since" => $customer->created_at,
                    "loyalty_level" => 0,
                    "is_phone_number_verified" => true
                ],
                "order" => [
                    "reference_id" => $order->ref_no,
                    "items" => $items
                ],
                "order_history" => [
                    [
                        "purchased_at" => $order->created_at,
                        "amount" => $order->total_amount,
                        "status" => "new",
                    ]
                ],
                "shipping_address" => [
                    "city" => $city->name_ar,
                    "address" => $address->address,
                    "zip" => "string"
                ],
                "meta" => [
                    "order_id" => $order->ref_no,
                    "customer" => $customer->id
                ],
            ],
            "lang" => "en",
            "merchant_code" => $merchantCode,
            "merchant_urls" => [
                "success" => config("app.payment_url", "https://turki.almaraacompany.com/admin") . "/api/v2/tabby/checkout/success",
                "cancel" => config("app.payment_url", "https://turki.almaraacompany.com/admin") . "/api/v2/tabby/checkout/cancel",
                "failure" => config("app.payment_url", "https://turki.almaraacompany.com/admin") . "/api/v2/tabby/checkout/failure"
            ]
        ];


        $payload = json_encode($data);


        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.tabby.ai/api/v2/checkout',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer sk_test_86f97925-b3e7-4d24-a4b1-9e681e59e3ad',
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $response = json_decode($response);

        if (isset($response->id)) {
            $createPayment["bank_ref_no"] = $response->payment->id;
            $payment = Payment::create($createPayment);
            $order->update(['payment_id' => $payment->id]);
            $installments = $response->configuration->available_products->installments[0];
            $result['checkout_url'] = $installments->web_url;
            $result['success'] = true;
            return $result;
        }


        if (isset($response->message)) {
            TraceError::create(['class_name' => "TabbyApiService", 'method_name' => "checkoutTabby:212", 'error_desc' => json_encode($response)]);
            $result['success'] = false;
            $result['error'] = $response;
            return $result;
        }
    }

    public function response(Request $request)
    {
        $post = $request->all();
        TraceError::create(['class_name' => "TabyApiService", 'method_name' => "response", 'error_desc' => json_encode($request->all())]);

        $logoPath = config('app.url') . '/storage/assets/logo.png';
        $paymentId = $post["payment_id"] ?? "N/A";
        $paymentResult = isset($post["payment_id"]) ? "Paid" : "N/A";

        $payment = Payment::where('bank_ref_no', $paymentId)->get()->last();

        if ($payment != null) {
            $order = Order::where('ref_no', $payment->order_ref_no)->get()->last();

            PaymentLog::create([
                "payment_name" => "Tabby",
                "payment_ref" => $payment->ref_no,
                "order_ref" => $payment->order_ref_no,
                "descraption" => $payment->description,
                "payment_status" => $payment->status
            ]);

            $payment->update([
                "description" => $paymentResult,
                "status" => "Client's payment process has " . $paymentResult,
                "price" => (float)$order->total_amount_after_discount,
            ]);
        }

        $objOrder = $order;
        $order = $order->toArray();

        $callPaymentNetsuiteApi = new CallPaymentNetsuiteApi();
        $res = $callPaymentNetsuiteApi->sendUpdatePaymentToNS($order, $request);
        // background-color: #e7c05d;

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
                           background-color: #e7c05d;

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

        if ($paymentResult == "Paid") {

            $order->update(['paid' => 1]);

            return $html_head . '


                   <head>
                       <script type="text/javascript">
                           function invokeNative() {
                               MessageInvoker.postMessage("1");
                           }
                       </script> </head>

                   <body>
                   ' . $html_sec1 . '
                   تمت عملية الدفع بنجاح,سيتم تأكيد الطلب بأقرب وقت ممكن.
                   ' . $html_sec2 . '
                   </body>

                   </html>
                 ';
        } else {

            $order->update(['paid' => 0]);

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

    public function createManualPayment($customer, $address, $createdOrder, $country)
    {

        $customer = Customer::find(auth()->user()->id);
        $lastPayment = Payment::latest('id')->first();


        // $validData = $request->validate([
        //     "order_ref" => 'required|exists:orders,order_ref',
        //     'country_id' => 'required|exists:countries,code'
        // ]);

        $order = Order::where('ref_no', $createdOrder->ref_no)->get()->first();
        $country = Country::where('code', $country->code)->get()->first();

        $createPayment = [
            "ref_no" => GetNextPaymentRefNo($country->code, $lastPayment != null ? $lastPayment->id + 1 : 1),
            "customer_id" => $customer->id,
            "order_ref_no" => $createdOrder->ref_no,
            "price" => (float) $order->total_amount_after_discount,
            "payment_type_id" => 7, //tabby
            "status" => "Waiting for the client", // need to move to enum class
            "description" => "Payment Created", // need to move to enum class
        ];

        $payment = Payment::create($createPayment);

        return [
            'payment_ref' => $payment->ref_no,
            'order_ref' => $payment->order_ref_no,
        ];
    }


    public function manualResponseUpdate(Request $request)
    {
        $validData = $request->validate([
            "payment_ref" => 'required|exists:payments,ref_no',
            "order_ref" => 'required|exists:orders,ref_no',
            "paid" => 'required|bool',
        ]);

        $payment = Payment::where([['ref_no', $validData['payment_ref']], ['order_ref_no', $validData['order_ref']]])->get()->last();
        $payment->update([
            "description" => 'paid from mobile manual.',
            "status" => $validData['paid'] == 1 ? "Paid" : "Unpaid",
            'manual' => 1
        ]);

        return response()->json([
            'success' => true, 'message' => 'success', 'description' => "", "code" => "200",
            "data" => $payment
        ], 200);
    }

    public function testHash(Request $request)
    {
        $validData = $request->validate([
            "bank_ref" => 'required',
            "payment_ref" => 'required',
            "order_ref" => 'required',
            "paid" => 'required|bool',
            "sauce" => 'required',
        ]);

        $onlyReqData = $validData;
        unset($onlyReqData['sauce']);
        $spicy = '';
        if ($request->query("test") != null && $request->query("test") == 1) {
            $spicy = '9be22db5-16c9-4c98-ae6c-b7670f5b2e3c';
        } else {
            $spicy = '330b838b-6c2a-450d-8e2e-0cde4c38abb6';
        }

        $mySauce = base64_encode(hash('sha512', json_encode($onlyReqData) . $spicy, false));

        if ($mySauce !== $validData["sauce"]) {
            return response()->json(['success' => false, 'yourhash' => $validData['sauce'], 'serverhash' => $mySauce], 400);
        } else {
            return response()->json(['success' => true, 'yourhash' => $validData['sauce'], 'serverhash' => $mySauce], 200);
        }
    }

    public function manualResponseUpdateV2(Request $request)
    {


        $validData = $request->validate([
            "bank_ref" => 'required',
            "payment_ref" => 'required',
            "order_ref" => 'required',
            "paid" => 'required|bool',
            "sauce" => 'required',
        ]);

        $onlyReqData = $validData;
        unset($onlyReqData['sauce']);
        $spicy = '330b838b-6c2a-450d-8e2e-0cde4c38abb6';
        $mySauce = base64_encode(hash('sha512', json_encode($onlyReqData) . $spicy, false));


        if ($mySauce !== $validData["sauce"]) {
            return response()->json([
                'success' => false, 'message' => 'failed', 'description' => "hmmm!", "code" => "404",
                "data" => null
            ], 404);
        }

        $payment = Payment::where([['ref_no', $validData['payment_ref']], ['order_ref_no', $validData['order_ref']]])->get()->last();
        $order = Order::where('ref_no', $payment->order_ref_no)->get()->last();

        $payment->update([
            "bank_ref_no" => $validData['bank_ref'],
            "description" => 'paid from mobile app tappy manual.',
            "status" => $validData['paid'] == 1 ? "Paid" : "Unpaid",
            'manual' => 1,
            "price" => (float)$order->total_amount_after_discount,
        ]);


        $order->update([
            "payment_id" => $payment->id,

        ]);
        PaymentLog::create([
            "payment_name" => "Tabby",
            "payment_ref" => $payment->ref_no,
            "order_ref" => $payment->order_ref_no,
            "descraption" => $payment->description,
            "payment_status" => $payment->status
        ]);

        $order = $order->toArray();

        $callPaymentNetsuiteApi = new CallPaymentNetsuiteApi();
        $callPaymentNetsuiteApi->sendUpdatePaymentToNS($order, $request);
        return response()->json([
            'success' => true, 'message' => 'success', 'description' => "", "code" => "200",
            "data" => $payment
        ], 200);
    }
}
