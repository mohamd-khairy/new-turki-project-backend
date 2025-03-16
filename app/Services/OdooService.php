<?php

namespace App\Services;

use App\Models\OrderProduct;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class OdooService
{
    public $url = 'http://213.136.77.102:8069/api/sale_orders';
    public $auth_url = 'https://turkishop.shop/web/session/authenticate';
    public $token = 'd93095a67ff516c273d19b1d9d2db21f549d898b';
    public $session_id;

    public function __construct()
    {
        $setting = DB::table('setting_apps')->where('key', 'session_id')->first();
        if ($setting) {
            $this->session_id = $setting->value;
        } else {
            $this->session_id = $this->getSessionId();
        }
    }

    function sendOrderToTurkishop($order)
    {
        $products = OrderProduct::with('preparation', 'size', 'cut', 'shalwata')
            ->where('order_ref_no', $order->ref_no)
            ->get();

        $new_products = [];
        foreach ($products as $key => $product) {
            #product_1
            if (in_array($product->size_id, [17, 2, 3, 4, 18, 19, 20, 21, 26, 27, 28, 29])) {

                if ($product->is_kwar3 == false) {
                    $new_products[] = [
                        'size' =>   [
                            'id' => 1049,
                            'name_ar' => 'كوارع'
                        ],
                        'preparation' =>  (object)[],
                        'cut' =>  (object)[],
                        'shalwata' =>  (object)[],
                        'quantity' => 1,
                    ];
                }
                if ($product->is_karashah == false) {
                    $new_products[] = [
                        'size' =>   [
                            'id' => 1051,
                            'name_ar' => 'كرشة'
                        ],
                        'preparation' =>  (object)[],
                        'cut' =>  (object)[],
                        'shalwata' =>  (object)[],
                        'quantity' => 1,
                    ];
                }
                if ($product->is_lyh == false) {
                    $new_products[] = [
                        'size' =>   [
                            'id' => 1054,
                            'name_ar' => 'لية'
                        ],
                        'preparation' =>  (object)[],
                        'cut' =>  (object)[],
                        'shalwata' =>  (object)[],
                        'quantity' => 1,
                    ];
                }
                if ($product->is_ras == false) {
                    $new_products[] = [
                        'size' =>   [
                            'id' => 1053,
                            'name_ar' => 'رأس'
                        ],
                        'preparation' =>  (object)[],
                        'cut' =>  (object)[],
                        'shalwata' =>  (object)[],
                        'quantity' => 1,
                    ];
                }
            }
            if (in_array($product->size_id, [6, 178, 8, 9, 22, 23, 24, 25, 30, 31, 32, 33])) {

                if ($product->is_kwar3 == false) {
                    $new_products[] = [
                        'size' =>   [
                            'id' => 1050,
                            'name_ar' => 'كوارع'
                        ],
                        'preparation' =>  (object)[],
                        'cut' =>  (object)[],
                        'shalwata' =>  (object)[],
                        'quantity' => 1,
                    ];
                }
                if ($product->is_karashah == false) {
                    $new_products[] = [
                        'size' =>   [
                            'id' => 1052,
                            'name_ar' => 'كرشة'
                        ],
                        'preparation' =>  (object)[],
                        'cut' =>  (object)[],
                        'shalwata' =>  (object)[],
                        'quantity' => 1,
                    ];
                }
                if ($product->is_lyh == false) {
                    $new_products[] = [
                        'size' =>   [
                            'id' => 1055,
                            'name_ar' => 'لية'
                        ],
                        'preparation' =>  (object)[],
                        'cut' =>  (object)[],
                        'shalwata' =>  (object)[],
                        'quantity' => 1,
                    ];
                }
            }
            if ($product->shalwata_id != null) {
                $new_products[] = [
                    'size' =>   [
                        'id' => 1056,
                        'name_ar' => 'شلوطة'
                    ],
                    'preparation' =>  (object)[],
                    'cut' =>  (object)[],
                    'shalwata' =>  (object)[],
                    'quantity' => 1,
                ];
            }
            # code...
            $new_products[] = [
                'size' => $product->size ?  [
                    'id' => $product->size->id,
                    'name_ar' => $product->size->name_ar
                ] : (object)[],
                'preparation' => $product->preparation ?  [
                    'id' => $product->preparation->id,
                    'name_ar' => $product->preparation->name_ar
                ] : (object)[],
                'cut' => $product->cut ?  [
                    'id' => $product->cut->id,
                    'name_ar' => $product->cut->name_ar
                ] : (object)[],
                'shalwata' => $product->shalwata ?  [
                    'id' => $product->shalwata->id,
                    'name_ar' => $product->shalwata->name_ar
                ] : (object)[],
                'quantity' => $product->quantity,
            ];
        }

        $payload = [
            "api_order_id" => $order['id'], // Order ID
            "customer" => [
                "name" => $order['customer']['name'],
                "mobile" => $order['customer']['mobile'],
                "address" => isset($order['selectedAddress']['address']) ? $order['selectedAddress']['address'] : "",
                "city" => isset($order['selectedAddress']['city']['name_ar']) ? $order['selectedAddress']['city']['name_ar'] : "",
            ],
            "using_wallet" => $order['using_wallet'],
            "wallet_amount_used" => $order['wallet_amount_used'],
            "applied_discount_code" => $order['applied_discount_code'],
            "discount_applied" => $order['discount_applied'],
            "delivery_date" => $order['delivery_date'],
            "date_order" => date("Y-m-d H:i:s", strtotime($order["created_at"])),
            "comment" => $order['comment'] ?? "",
            "day" =>  strtolower(date("l", strtotime($order["created_at"]))),
            "paid" => $order['paid'],
            "city" => isset($order['selectedAddress']['city']['name_ar']) ? $order['selectedAddress']['city']['name_ar'] : "",
            "custom_state" =>  isset($order['orderState']['odoo_status']) ? $order['orderState']['odoo_status'] : "",
            "delivery_time" => date('H:i:s', strtotime($order['created_at'])),
            "delivery_period" => isset($order['deliveryPeriod']['name_ar']) ? $order['deliveryPeriod']['name_ar'] : "",
            "payment_method" => isset($order['paymentType']['name_ar']) ? $order['paymentType']['name_ar'] : "",
            "products" => $new_products
        ];

        if (isset($order['selectedAddress']['long'])) {
            $payload['long'] = $order['selectedAddress']['long'];
        }
        if (isset($order['selectedAddress']['lat'])) {
            $payload['lat'] = $order['selectedAddress']['lat'];
        }

        return $this->sendCurlRequest($payload);
    }

    public function sendCurlRequest($payload)
    {
        $headers = [
            'Authorization' => $this->token,
            'Content-Type' => 'application/json',
            'Cookie' => 'session_id=' . $this->session_id,
        ];

        $response = Http::withHeaders($headers)->post($this->url, $payload);

        $httpCode = $response->status();

        if ($httpCode == 404) {
            $this->getSessionId();
        }

        return [
            'status_code' => $httpCode,
            'response' => json_decode($response, true),
        ];
    }

    public function getSessionId()
    {
        $headers = [
            'Content-Type: application/json',
        ];

        $payload = [
            "jsonrpc" => "2.0",
            "params" => [
                "db" => "turkishop",
                "login" => "admin",
                "password" => "Om123"
            ]
        ];

        $headers = [
            'Content-Type' => 'application/json',
        ];

        $response = Http::withHeaders($headers)->post($this->auth_url, $payload);

        $cookies = $response->cookies();

        $session_id =  $cookies->getCookieByName('session_id')->getValue();

        $httpCode = $response->status();

        if ($httpCode == 200) {

            if (isset($session_id)) {
                $setting = DB::table('setting_apps')->where('key', 'session_id')->first();
                if ($setting) {
                    DB::table('setting_apps')->where('key', 'session_id')
                        ->update([
                            'key' => 'session_id',
                            'value' => $session_id
                        ]);
                } else {
                    DB::table('setting_apps')->insert([
                        'key' => 'session_id',
                        'value' => $session_id
                    ]);
                }

                return $session_id;
            }
        }

        return null;
    }
}
