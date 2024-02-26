<?php

namespace App\Services;


use App\Models\City;
use App\Models\Country;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Shalwata;
use App\Models\Size;
use App\Models\TamaraPayment;
use App\Models\TraceError;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\PaymentLog;
use App\Services\CallPaymentNetsuiteApi;

class TamaraApiServiceV2
{
    protected $http;
    protected $config = [];
    protected $SAND_BASE_URL = "https://api-sandbox.tamara.co/";
    protected $BASE_URL = "https://api.tamara.co/";
    protected $ENDPOINT = "";
    protected $API_KEY = "";
    //In order to validate this token, please use JWT decode with the HS256 algorithm and your given notification token.
    protected $NOTIFICATION_TOKEN = "";
    private $SECRET_KEY = "";

    public function __construct()
    {
        $this->http = new HttpServices();
        $this->config = array_merge($this->config, $this->getConfig());
        $this->SECRET_KEY = env("TAMARA_NOTIFY_KEY");
        $this->API_KEY = env("TAMARA_API_KEY");
    }

    public function getConfig()
    {
        return [
            "Content-Type" => "application/vnd.ni-identity.v1+json",
            "Authorization" => "Bearer " . $this->API_KEY
        ];
    }

    public function getAccessToken()
    {
        $this->buildUrl("identity/auth/access-token");
        $body = [
            "api_key" => config('services.tookan.key'),
            "tookan_shared_secret" => config('services.tookan.secret')
        ];
        return $this->http->post($this->ENDPOINT, $body, $this->config);
    }

    public function buildUrl($endpoint)
    {
        if (env("APP_ENV") == "local")
            $this->ENDPOINT = $this->SAND_BASE_URL . $endpoint;
        else
            $this->ENDPOINT = $this->BASE_URL . $endpoint;
        return $this;
    }

    // public function getPaymentTypes(string $countryIsoCode,string $currencyCode, int $orderValue){
    //     $this->buildUrl("checkout/payment-types");
    //     //query params, from "?" the query params start (e.g. http://test.com?paramTest=value&paramTest2=value2)
    //     $params = ["country" => $countryIsoCode, "currency" => $currencyCode, "order_value" => $orderValue];
    //     $res = $this->http->get($this->BASE_URL, $params,$this->config);

    //     // if there error
    //     if (isset($res['message'])){
    //         TraceError::create(['class_name'=> "TamaraApiService", 'method_name'=>"getPaymentTypes", 'error_desc' => json_encode($res)]);
    //         return $res;
    //     }

    //     return $res;
    // }

    public function getPaymentTypes($countryIsoCode = 'SA', $currencyCode, $orderValue)
    {
        //      $configuration = Configuration::create($apiUrl, $API_KEY, $apiRequestTimeout, $transport);
        //      $client = Client::create($configuration);
        //
        //      $response = $client->getPaymentTypes('SA');
        //
        //      if ($response->isSuccess()) {
        //          var_dump($response->getPaymentTypes());
        //      }
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => ('https://api-sandbox.tamara.co/checkout/payment-types' . '?country=' . $countryIsoCode . '&currency=' . $currencyCode . '&order_value=' . $orderValue),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhY2NvdW50SWQiOiI4OTg2NGYxYy02ZjM2LTRmN2ItOGRkOS1jY2EzZmI4ZDBiOWYiLCJ0eXBlIjoibWVyY2hhbnQiLCJzYWx0IjoiNDA0YzliOTJmY2E1MWNjOGMzZGQyZTdjYzg2NjkxYTUiLCJpYXQiOjE2NjYyNTI2NTYsImlzcyI6IlRhbWFyYSJ9.suKYxzDwVdc3ZX30Wtp9skirfhQnx30kUCqShQ06aT5mNhqFK3PFM9KHVNYavfEtBAAiVsbbrg-liMcspTU4P-aSKf7YEKExNOJzPxp4R15ajtuGUQf99xY1nChcGPY20yRyqKJx_Wut7vkpdlDGbAl7YkXvaZMUNkRlLtXz1KCKQJpVvziXdIs2FaLDu8JTKbxC89v8P1hdhmoXEpsId71fYcSwdM2WO6g5iANpczp0fKpMBv-aqMVrXRHDLBl22ilKefBFjODbsD95mtSAMmS7a99Xt90acchcbVD6my5Vzvsqv6-oSugRNDYrPhEL1PBsMDKVWuzrWAsD0FmM-Q'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return json_decode($response);
    }

    public function checkoutTamara($customer, $address, $order, $paymentType, $country, $instalments = null)
    {

        $lastPayment = Payment::latest('id')->first();

        $createPayment = [
            "ref_no" => GetNextPaymentRefNo($country->code, $lastPayment != null ? $lastPayment->id + 1 : 1),
            "customer_id" => $customer->id,
            "order_ref_no" => $order->ref_no,
            "price" => (float)$order->total_amount_after_discount,
            "payment_type_id" => 6, //tamara
            "status" => "Waiting for the client", // need to move to enum class
            "description" => "Payment Created", // need to move to enum class
        ];

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
                "reference_id" => $size->id,
                "type" => "physical",
                "name" => $size->name_ar,
                "sku" => "SA-12436",
                "image_url" => "https://www.example.com/product.jpg",
                "quantity" => $qty,
                "unit_price" => [
                    "amount" => $unitPrice,
                    "currency" => $Country->currency_en
                ],
                "discount_amount" => [
                    "amount" => "00.00",
                    "currency" => $Country->currency_en
                ],
                "tax_amount" => [
                    "amount" => "00.00",
                    "currency" => $Country->currency_en
                ],
                "total_amount" => [
                    "amount" => $order->total_amount_after_discount,
                    "currency" => $country->currency_en
                ]
            ];

            array_push($items, $item);
        }


        $data = [
            "order_reference_id" => $order->ref_no,
            "order_number" => $order->ref_no,
            "total_amount" => [
                "amount" => $order->total_amount_after_discount,
                "currency" => $country->currency_en
            ],
            "description" => "any thing",
            "country_code" => $country->code,
            "payment_type" => $paymentType,
            "instalments" => $instalments,
            "locale" => "ar_SA",
            "items" => $items,
            "consumer" => [
                "first_name" => $customer->name,
                "last_name" => "",
                "phone_number" => $customer->mobile,
                "email" => "user@example.com"
            ],
            "billing_address" => [
                "first_name" => $customer->name,
                "last_name" => "",
                "line1" => $address->address,
                "line2" => $address->comment,
                "region" => $address->address,
                "postal_code" => "12345",
                "city" => $city->name_ar,
                "country_code" => $Country->code,
                "phone_number" => $customer->mobile
            ],
            "shipping_address" => [
                "first_name" => $customer->name,
                "last_name" => "",
                "line1" => $address->address,
                "line2" => $address->comment,
                "region" => $address->address,
                "postal_code" => "12345",
                "city" => $city->name_ar,
                "country_code" => $Country->code,
                "phone_number" => $customer->mobile
            ],
            "discount" => [
                "name" => "N/A",
                "amount" => [
                    "amount" => "00.00",
                    "currency" => $country->currency_en
                ]
            ],
            "tax_amount" => [
                "amount" => "00.00",
                "currency" => $country->currency_en
            ],
            "shipping_amount" => [
                "amount" => "00.00",
                "currency" => $country->currency_en
            ],
            "merchant_url" => [
                "success" => config("app.payment_url", "https://turki.almaraacompany.com/admin") . "/api/v2/checkout/success",
                "failure" => config("app.payment_url", "https://turki.almaraacompany.com/admin") . "/api/v2/checkout/failure",
                "cancel" => config("app.payment_url", "https://turki.almaraacompany.com/admin") . "/api/v2/checkout/cancel",
                "notification" => config("app.payment_url", "https://turki.almaraacompany.com/admin") . "/api/v2/payments/tamarapay"
            ],
            "platform" => "web",
            "is_mobile" => true,
            "risk_assessment" => [
                "customer_age" => $customer->age,
                "customer_dob" => "31-01-2000",
                "customer_gender" => "Male",
                "customer_nationality" => $country->code,
                "is_premium_customer" => false,
                "is_existing_customer" => true,
                "is_guest_user" => false,
                "account_creation_date" => "31-01-2019",
                "platform_account_creation_date" => "string",
                "date_of_first_transaction" => "31-01-2019",
                "is_card_on_file" => false,
                "is_COD_customer" => false,
                "has_delivered_order" => true,
                "is_phone_verified" => true,
                "is_fraudulent_customer" => false,
                "total_ltv" => 0.00,
                "total_order_count" => 0,
                "order_amount_last3months" => 0.00,
                "order_count_last3months" => 2,
                "last_order_date" => "31-01-2021",
                "last_order_amount" => 0.00,
                "reward_program_enrolled" => false,
                "reward_program_points" => 0
            ]
        ];


        $payload = json_encode($data);

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api-sandbox.tamara.co/checkout',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhY2NvdW50SWQiOiI4OTg2NGYxYy02ZjM2LTRmN2ItOGRkOS1jY2EzZmI4ZDBiOWYiLCJ0eXBlIjoibWVyY2hhbnQiLCJzYWx0IjoiNDA0YzliOTJmY2E1MWNjOGMzZGQyZTdjYzg2NjkxYTUiLCJpYXQiOjE2NjYyNTI2NTYsImlzcyI6IlRhbWFyYSJ9.suKYxzDwVdc3ZX30Wtp9skirfhQnx30kUCqShQ06aT5mNhqFK3PFM9KHVNYavfEtBAAiVsbbrg-liMcspTU4P-aSKf7YEKExNOJzPxp4R15ajtuGUQf99xY1nChcGPY20yRyqKJx_Wut7vkpdlDGbAl7YkXvaZMUNkRlLtXz1KCKQJpVvziXdIs2FaLDu8JTKbxC89v8P1hdhmoXEpsId71fYcSwdM2WO6g5iANpczp0fKpMBv-aqMVrXRHDLBl22ilKefBFjODbsD95mtSAMmS7a99Xt90acchcbVD6my5Vzvsqv6-oSugRNDYrPhEL1PBsMDKVWuzrWAsD0FmM-Q',
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $response = json_decode($response);

        if (isset($response->order_id)) {
            $createPayment["bank_ref_no"] = $response->order_id;
            $payment = Payment::create($createPayment);
            $order->update(['payment_id' => $payment->id, 'paid' => 1]);

            $result['checkout_url'] = $response->checkout_url;
            $result['success'] = true;
            return $result;
        }


        if (isset($response->message)) {
            TraceError::create(['class_name' => "TamaraApiServiceV2", 'method_name' => "checkoutTamara:297", 'error_desc' => json_encode($response)]);
            $result['success'] = false;
            $result['error'] = $response;
            return $result;
        }
    }


    public function response(Request $request)
    {
        $post = $request->all();
        TraceError::create(['class_name' => "TamaraApiServiceV2", 'method_name' => $request->query('paymentStatus'), 'error_desc' => json_encode($request->all())]);

        $logoPath = config('app.url') . '/storage/assets/logo.png';
        $paymentId = $request->query('orderId') ?? 'N/A';
        $paymentResult = $request->query('paymentStatus') ?? 'N/A';

        $payment = Payment::where('bank_ref_no', $paymentId)->get()->last();



        if ($payment != null) {
            $order = Order::where('ref_no', $payment->order_ref_no)->get()->last();

            PaymentLog::create([
                "payment_name" => "Tamara",
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

        if ($paymentResult == "approved") {

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


    public function checkoutTamaratest($customer, $address, $order, $paymentType, $country, $instalments = null)
    {
        $lastPayment = Payment::latest('id')->first();

        $createPayment = [
            "ref_no" => GetNextPaymentRefNo($country->code, $lastPayment != null ? $lastPayment->id + 1 : 1),
            "customer_id" => 8,
            "order_ref_no" => "SAO000017390",
            "price" => 500.00,
            "payment_type_id" => 4, //tamara
            "status" => "Waiting for the client", // need to move to enum class
            "description" => "Payment Created", // need to move to enum class
        ];

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
                "reference_id" => 3,
                "type" => "physical",
                "name" => "نعيمي هرفي",
                "sku" => "SA-12436",
                "image_url" => "https://www.example.com/product.jpg",
                "quantity" => 1,
                "unit_price" => [
                    "amount" => 500.00,
                    "currency" => "SAR"
                ],
                "discount_amount" => [
                    "amount" => "00.00",
                    "currency" => "SAR"
                ],
                "tax_amount" => [
                    "amount" => "00.00",
                    "currency" => "SAR"
                ],
                "total_amount" => [
                    "amount" => 500.00,
                    "currency" => "SAR"
                ]
            ];

            array_push($items, $item);
        }


        $data = [
            "order_reference_id" => "SAO000017390",
            "order_number" => "SAO000017390",
            "total_amount" => [
                "amount" => 500.00,
                "currency" => "SAR"
            ],
            "description" => "test",
            "country_code" => "SA",
            "payment_type" => "PAY_BY_INSTALMENTS",
            "instalments" => 3,
            "locale" => "ar_SA",
            "items" => $items,
            "consumer" => [
                "first_name" => "somaya",
                "last_name" => "",
                "phone_number" => "0561051956",
                "email" => "user@example.com"
            ],
            "billing_address" => [
                "first_name" => "somaya",
                "last_name" => "",
                "line1" => "test",
                "line2" => "test",
                "region" => "test",
                "postal_code" => "12345",
                "city" => "test",
                "country_code" => "SA",
                "phone_number" => "0561051956"
            ],
            "shipping_address" => [
                "first_name" => "somaya",
                "last_name" => "",
                "line1" => "test",
                "line2" => "test",
                "region" => "test",
                "postal_code" => "12345",
                "city" => "test",
                "country_code" => "SA",
                "phone_number" => "0561051956"
            ],
            "discount" => [
                "name" => "N/A",
                "amount" => [
                    "amount" => "00.00",
                    "currency" => "SAR"
                ]
            ],
            "tax_amount" => [
                "amount" => "00.00",
                "currency" => "SAR"
            ],
            "shipping_amount" => [
                "amount" => "00.00",
                "currency" => "SAR"
            ],
            "merchant_url" => [
                "success" => config("app.payment_url", "https://turki.almaraacompany.com/admin") . "/api/v2/checkout/response",
                "failure" => config("app.payment_url", "https://turki.almaraacompany.com/admin") . "/api/v2/checkout/response",
                "cancel" => config("app.payment_url", "https://turki.almaraacompany.com/admin") . "/api/v2/checkout/response",
                "notification" => config("app.payment_url", "https://turki.almaraacompany.com/admin") . "/api/v2/payments/tamarapay"
            ],
            "platform" => "web",
            "is_mobile" => true,
            "risk_assessment" => [
                "customer_age" => "28",
                "customer_dob" => "31-01-2000",
                "customer_gender" => "Male",
                "customer_nationality" => "SA",
                "is_premium_customer" => false,
                "is_existing_customer" => true,
                "is_guest_user" => false,
                "account_creation_date" => "31-01-2019",
                "platform_account_creation_date" => "string",
                "date_of_first_transaction" => "31-01-2019",
                "is_card_on_file" => false,
                "is_COD_customer" => false,
                "has_delivered_order" => true,
                "is_phone_verified" => true,
                "is_fraudulent_customer" => false,
                "total_ltv" => 0.00,
                "total_order_count" => 0,
                "order_amount_last3months" => 0.00,
                "order_count_last3months" => 2,
                "last_order_date" => "31-01-2021",
                "last_order_amount" => 0.00,
                "reward_program_enrolled" => false,
                "reward_program_points" => 0
            ]
        ];


        $payload = json_encode($data);

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api-sandbox.tamara.co/checkout',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhY2NvdW50SWQiOiI4OTg2NGYxYy02ZjM2LTRmN2ItOGRkOS1jY2EzZmI4ZDBiOWYiLCJ0eXBlIjoibWVyY2hhbnQiLCJzYWx0IjoiNDA0YzliOTJmY2E1MWNjOGMzZGQyZTdjYzg2NjkxYTUiLCJpYXQiOjE2NjYyNTI2NTYsImlzcyI6IlRhbWFyYSJ9.suKYxzDwVdc3ZX30Wtp9skirfhQnx30kUCqShQ06aT5mNhqFK3PFM9KHVNYavfEtBAAiVsbbrg-liMcspTU4P-aSKf7YEKExNOJzPxp4R15ajtuGUQf99xY1nChcGPY20yRyqKJx_Wut7vkpdlDGbAl7YkXvaZMUNkRlLtXz1KCKQJpVvziXdIs2FaLDu8JTKbxC89v8P1hdhmoXEpsId71fYcSwdM2WO6g5iANpczp0fKpMBv-aqMVrXRHDLBl22ilKefBFjODbsD95mtSAMmS7a99Xt90acchcbVD6my5Vzvsqv6-oSugRNDYrPhEL1PBsMDKVWuzrWAsD0FmM-Q',
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $response = json_decode($response);

        if (isset($response->order_id)) {
            $createPayment["bank_ref_no"] = $response->order_id;
            $payment = Payment::create($createPayment);
            $order->update(['payment_id' => $payment->id]);

            $result['checkout_url'] = $response->checkout_url;
            $result['success'] = true;
            return $result;
        }


        if (isset($response->message)) {
            TraceError::create(['class_name' => "TamaraApiServiceV2", 'method_name' => "checkoutTamara:297", 'error_desc' => json_encode($response)]);
            $result['success'] = false;
            $result['error'] = $response;
            return $result;
        }
    }

    public function orderDetails($order)
    {

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api-sandbox.tamara.co/merchants/orders/reference-id/' . $order,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhY2NvdW50SWQiOiI4OTg2NGYxYy02ZjM2LTRmN2ItOGRkOS1jY2EzZmI4ZDBiOWYiLCJ0eXBlIjoibWVyY2hhbnQiLCJzYWx0IjoiNDA0YzliOTJmY2E1MWNjOGMzZGQyZTdjYzg2NjkxYTUiLCJpYXQiOjE2NjYyNTI2NTYsImlzcyI6IlRhbWFyYSJ9.suKYxzDwVdc3ZX30Wtp9skirfhQnx30kUCqShQ06aT5mNhqFK3PFM9KHVNYavfEtBAAiVsbbrg-liMcspTU4P-aSKf7YEKExNOJzPxp4R15ajtuGUQf99xY1nChcGPY20yRyqKJx_Wut7vkpdlDGbAl7YkXvaZMUNkRlLtXz1KCKQJpVvziXdIs2FaLDu8JTKbxC89v8P1hdhmoXEpsId71fYcSwdM2WO6g5iANpczp0fKpMBv-aqMVrXRHDLBl22ilKefBFjODbsD95mtSAMmS7a99Xt90acchcbVD6my5Vzvsqv6-oSugRNDYrPhEL1PBsMDKVWuzrWAsD0FmM-Q'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return json_decode($response);
    }



    public function tamarapay(Request $request)
    {

        $data = $request->all();

        TraceError::create(['class_name' => "TamaraApiServiceV2", 'method_name' => "tamarapay:474", 'error_desc' => json_encode($request->all())]);

        // validate the request by the token in authorization header
        //         $notifToken =  $request->header("Authorization");
        //         $notifToken = str_replace("Bearer", '',$notifToken);
        //         $notifToken = str_replace(" ", '',$notifToken);

        // $token = $this->decode($data['tamaraToken']);

        // need test
        //         if ($token != $this->API_KEY){
        // TraceError::create(['class_name' => "TamaraApiServiceV2", 'method_name' => "tamarapay:485", 'error_desc' => 'token not matched!,  '.json_encode($request->all())]);
        //             return;
        //         }


        $tamara = TamaraPayment::create([
            'order_ref_no' => $data['order_reference_id'],
            'tamara_order_id' => $data['order_id'],
            'status' => $data['order_status'],
        ]);



        $order = Order::find($data['order_reference_id']);
        $payment = Payment::where('bank_ref_no', $data['order_id'])->get()->last();

        if (isset($data['order_status'])) {
            if ($data['order_status'] == "approved") {

                $payment->update([
                    "description" => $data['order_status'],
                    "status" => "Calling Tamara for Authorizing the Order!",
                    "price" => (float)$order->total_amount_after_discount,
                ]);

                $res = $this->authoriseOrder($data['order_id']);
                $orderArray = $order->toArray();

                // $callPaymentNetsuiteApi = new CallPaymentNetsuiteApi();
                // $resNetsuiteApi = $callPaymentNetsuiteApi->sendUpdatePaymentToNS($orderArray, $request);

                TraceError::create(['class_name' => "TamaraApiServiceV2", 'method_name' => "authoriseOrder:562", 'error_desc' =>  json_encode($res)]);


                if (isset($res->status)) {
                    $tamara->status = $res->status;
                    $tamara->payment_type = $res->payment_type;
                    $tamara->save();

                    $payment->update([
                        "description" => $res->status,
                        "status" => "Paid",
                        "price" => (float)$order->total_amount_after_discount,
                    ]);

                    $order->update(['paid' => 1]);

                    //   $orderArray = $order->toArray();

                    //   $callPaymentNetsuiteApi = new CallPaymentNetsuiteApi();
                    //   $resNetsuiteApi = $callPaymentNetsuiteApi->sendUpdatePaymentToNS($orderArray , $request);
                    //   TraceError::create(['class_name' => "TamaraApiService", 'method_name' => "tamarapay:557", 'error_desc' => 'order sent to NetsuiteApi!,  '.json_encode($resNetsuiteApi)]);
                } else {

                    if (isset($res->errors) && isset($res->errors->data) && isset($res->errors->data->new_state)) {
                        TraceError::create(['class_name' => "TamaraApiServiceV2", 'method_name' => "tamarapay:554", 'error_desc' => json_encode($res->errors)]);
                        if ($res->errors->data->new_state == "authorised") {
                            $tamara->update([
                                'status' => $res->errors->data->new_state
                            ]);

                            $payment->update([
                                "description" => $res->errors->data->new_state,
                                "status" => "Paid",
                                "price" => (float)$order->total_amount_after_discount,
                            ]);

                            $order->update(['paid' => 1]);

                            //   $orderArray = $order->toArray();

                            //     //add trace error fro this
                            //   $callPaymentNetsuiteApi = new CallPaymentNetsuiteApi();
                            //   $resNetsuiteApi = $callPaymentNetsuiteApi->sendUpdatePaymentToNS($orderArray , $request);
                        }
                    } else {
                        TraceError::create(['class_name' => "TamaraApiServiceV2", 'method_name' => "tamarapay:559", 'error_desc' => 'order not authorized!,  ' . json_encode($res)]);
                        $payment->update([
                            "description" => $res->message,
                            "status" => "order not authorized from Tamara!",
                        ]);

                        $tamara->update([
                            'status' => $res->message
                        ]);
                    }
                }
            }
        }
    }



    private function decode($jwt)
    {
        return JWT::decode($jwt, new Key($this->SECRET_KEY, 'HS256'));
    }

    private function encode($payload)
    {
        return JWT::encode($payload, $this->SECRET_KEY, 'HS256');
    }

    private function authoriseOrder($tamaraOrderId)
    {
        $curl = curl_init();

        $payload = json_encode([]);

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api-sandbox.tamara.co/orders/' . $tamaraOrderId . '/authorise',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhY2NvdW50SWQiOiI4OTg2NGYxYy02ZjM2LTRmN2ItOGRkOS1jY2EzZmI4ZDBiOWYiLCJ0eXBlIjoibWVyY2hhbnQiLCJzYWx0IjoiNDA0YzliOTJmY2E1MWNjOGMzZGQyZTdjYzg2NjkxYTUiLCJpYXQiOjE2NjYyNTI2NTYsImlzcyI6IlRhbWFyYSJ9.suKYxzDwVdc3ZX30Wtp9skirfhQnx30kUCqShQ06aT5mNhqFK3PFM9KHVNYavfEtBAAiVsbbrg-liMcspTU4P-aSKf7YEKExNOJzPxp4R15ajtuGUQf99xY1nChcGPY20yRyqKJx_Wut7vkpdlDGbAl7YkXvaZMUNkRlLtXz1KCKQJpVvziXdIs2FaLDu8JTKbxC89v8P1hdhmoXEpsId71fYcSwdM2WO6g5iANpczp0fKpMBv-aqMVrXRHDLBl22ilKefBFjODbsD95mtSAMmS7a99Xt90acchcbVD6my5Vzvsqv6-oSugRNDYrPhEL1PBsMDKVWuzrWAsD0FmM-Q',
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return json_decode($response);
    }

    // when order is ready for shipped, this api should be called
    public function capturedOrder($order, $country)
    {
        $curl = curl_init();

        $tamara = TamaraPayment::where('order_ref_no', $order->ref_no)->get()->last();
        $data = [
            'orderId' => $tamara->tamara_order_id,
            "total_amount" => [
                "amount" => $order->total_amount_after_discount,
                "currency" => $country->currency_en
            ],
            "shipping_info" => [
                "shipped_at" => $order->updated_at,
                "shipping_company" => "Turki Delivery",
                "tracking_number" => $order->ref_no,
                "tracking_url" => "https://almaraacompany.com",
            ]
        ];
        $payload = json_encode($data);

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api-sandbox.tamara.co/payments/capture',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhY2NvdW50SWQiOiI4OTg2NGYxYy02ZjM2LTRmN2ItOGRkOS1jY2EzZmI4ZDBiOWYiLCJ0eXBlIjoibWVyY2hhbnQiLCJzYWx0IjoiNDA0YzliOTJmY2E1MWNjOGMzZGQyZTdjYzg2NjkxYTUiLCJpYXQiOjE2NjYyNTI2NTYsImlzcyI6IlRhbWFyYSJ9.suKYxzDwVdc3ZX30Wtp9skirfhQnx30kUCqShQ06aT5mNhqFK3PFM9KHVNYavfEtBAAiVsbbrg-liMcspTU4P-aSKf7YEKExNOJzPxp4R15ajtuGUQf99xY1nChcGPY20yRyqKJx_Wut7vkpdlDGbAl7YkXvaZMUNkRlLtXz1KCKQJpVvziXdIs2FaLDu8JTKbxC89v8P1hdhmoXEpsId71fYcSwdM2WO6g5iANpczp0fKpMBv-aqMVrXRHDLBl22ilKefBFjODbsD95mtSAMmS7a99Xt90acchcbVD6my5Vzvsqv6-oSugRNDYrPhEL1PBsMDKVWuzrWAsD0FmM-Q',
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return json_decode($response);
    }

    private function getTamaraOrder($orderRef)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api-sandbox.tamara.co/merchants/orders/reference-id/' . $orderRef,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhY2NvdW50SWQiOiI4OTg2NGYxYy02ZjM2LTRmN2ItOGRkOS1jY2EzZmI4ZDBiOWYiLCJ0eXBlIjoibWVyY2hhbnQiLCJzYWx0IjoiNDA0YzliOTJmY2E1MWNjOGMzZGQyZTdjYzg2NjkxYTUiLCJpYXQiOjE2NjYyNTI2NTYsImlzcyI6IlRhbWFyYSJ9.suKYxzDwVdc3ZX30Wtp9skirfhQnx30kUCqShQ06aT5mNhqFK3PFM9KHVNYavfEtBAAiVsbbrg-liMcspTU4P-aSKf7YEKExNOJzPxp4R15ajtuGUQf99xY1nChcGPY20yRyqKJx_Wut7vkpdlDGbAl7YkXvaZMUNkRlLtXz1KCKQJpVvziXdIs2FaLDu8JTKbxC89v8P1hdhmoXEpsId71fYcSwdM2WO6g5iANpczp0fKpMBv-aqMVrXRHDLBl22ilKefBFjODbsD95mtSAMmS7a99Xt90acchcbVD6my5Vzvsqv6-oSugRNDYrPhEL1PBsMDKVWuzrWAsD0FmM-Q',
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return json_decode($response);
    }
}
