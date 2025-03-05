<?php

use App\Models\Cashback;
use App\Models\Discount;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\OrderState;
use App\Models\Stock;
use App\Models\StockLog;
use App\Models\Store;
use App\Models\WalletLog;
use App\Models\WelcomeMoney;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

use Illuminate\Support\Facades\Http;

function sendOrderToTurkishop($order, $products)
{
    $url = 'https://turkishop.shop/api/sale_orders';
    $token = 'd93095a67ff516c273d19b1d9d2db21f549d898b'; // Replace with your actual token

    $payload = [
        "api_order_id" => $order['id'], // Order ID
        "customer" => [
            "name" => $order['customer']['name'],
            "mobile" => $order['customer']['mobile'],
            "address" => $order['selectedAddress']['address'],
            "city" => $order['selectedAddress']['city']['name_ar'],
        ],
        "using_wallet" => $order['using_wallet'],
        "wallet_amount_used" => $order['wallet_amount_used'],
        "applied_discount_code" => $order['applied_discount_code'],
        "discount_applied" => $order['discount_applied'],
        "long" => $order['selectedAddress']['long'],
        "lat" => $order['selectedAddress']['lat'],
        "delivery_date" => $order['delivery_date'],
        "date_order" => date("Y-m-d H:i:s", strtotime($order["created_at"])),
        "comment" => $order['comment'] ?? "",
        "day" => date("l", strtotime($order["created_at"])),
        "paid" => $order['paid'],
        "custom_state" => $order['orderState']['odoo_status'],
        "delivery_time" => date('H:i:s', strtotime($order['created_at'])),
        "delivery_period" => $order['deliveryPeriod']['name_ar'],
        "payment_method" => $order['paymentType']['name_ar'],
        "products" => $products->toArray()
    ];
    // return $payload;

    // dd($payload);

    // $headers = [
    //     'Authorization: ' . $token,
    //     'Content-Type: application/json',
    //     'Cookie: session_id=3e594e3f81312915f022b090f71dfbc42999b1ad',
    // ];

    // $ch = curl_init();
    // curl_setopt($ch, CURLOPT_URL, $url);
    // curl_setopt($ch, CURLOPT_POST, true);
    // curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL verification if needed
    // curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    // $response = curl_exec($ch);
    // $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    // $error = curl_error($ch);

    // curl_close($ch);

    // return [
    //     'status_code' => $httpCode,
    //     'response' => json_decode($response, true),
    //     'error' => $error
    // ];

    // $client = new Client();
    // $response = $client->post($url, [
    //     'headers' => [
    //         'Authorization' => $token,
    //         'Content-Type'  => 'application/json',
    //         'Cookie'        => 'session_id=3e594e3f81312915f022b090f71dfbc42999b1ad',
    //         'Accept'        => 'application/json',
    //     ],
    //     'json' => $payload
    // ]);
    // return [
    //     'status_code' => $response->getStatusCode(),
    //     'body' => json_decode($response->getBody(), true)
    // ];

    $response = Http::withHeaders([
        'Authorization' => $token,
        'Content-Type'  => 'application/json',
        'Cookie'        => 'session_id=3e594e3f81312915f022b090f71dfbc42999b1ad',
    ])->post($url, $payload);

    return [
        'status' => $response->status(),
        'body' => $response->json(),
    ];
}


function touchStock($order)
{
    // try {
    //     $order_products = OrderProduct::where('order_ref_no', $order->ref_no)->get();

    //     foreach ($order_products as $order_product) {

    //         if ($order_product->size) {

    //             $size_stores = $order_product->size->size_store;

    //             foreach ($size_stores as $size_store) {

    //                 $store_id = null;
    //                 if (isset($order->selectedAddress->city_id) && $order->selectedAddress->city_id != null) {
    //                     $store = Store::where('city_id', $order->selectedAddress->city_id)->first();
    //                     $store_id = $store ? $store->id : null;
    //                 } elseif (isset($order->user->city_id) && $order->user->city_id != null) {
    //                     $store = Store::where('city_id', $order->user->city_id)->first();
    //                     $store_id = $store ? $store->id : null;
    //                 }

    //                 if ($store_id == null) {
    //                     $store_id = $size_store->store_id;
    //                 }

    //                 $stock = Stock::where('product_id', $size_store->product_id)
    //                     ->where('store_id', $store_id)->first();

    //                 // $stock = Stock::where('product_id', $size_store->product_id)
    //                 //         ->where('store_id', $size_store->store_id)->first();

    //                 $qty = ($order_product->quantity * $size_store->quantity);

    //                 $new_quantity = $stock->quantity - $qty;

    //                 if (!StockLog::where([
    //                     'order_product_id' => $size_store->product_id,
    //                     'action' => 'order',
    //                     'order_ref_no' => $order->ref_no,
    //                     'quantity' => $qty
    //                 ])->exists()) {
    //                     $log = [
    //                         'stock_id' => $stock->id,
    //                         'quantity' => $qty,
    //                         'old_quantity' => $stock->quantity,
    //                         'new_quantity' => $new_quantity,
    //                         'order_ref_no' => $order->ref_no,
    //                         'order_product_id' => $size_store->product_id,
    //                         'action' => 'order',
    //                         'customer_id' => $order->customer_id,
    //                         'user_id' => auth()->user()->id,
    //                         'size_id' => $order_product->size_id
    //                     ];

    //                     StockLog::create($log);

    //                     $stock->update([
    //                         'quantity' => $new_quantity,
    //                     ]);
    //                 }
    //                 // }
    //             }
    //         }
    //     }
    // } catch (\Throwable $th) {
    //     // throw $th;
    //     info($th->getMessage());
    // }
}

function welcome($customer)
{
    try {
        $customer = $customer->refresh();

        if ($customer->mobile_country_code == '+966' || substr($customer->mobile, 0, 4) == "+966") {
            $country_id = 1;
        } else {
            $country_id = 4;
        }

        $welcome = WelcomeMoney::where('is_active', 1)
            ->where('country_id', $country_id)
            ->where(function ($q) use ($customer) {
                $q->whereDate('welcome_start_date', '<= ', $customer->created_at)
                    ->whereDate('welcome_end_date', '>=', $customer->created_at);
            })
            ->first();

        if ($welcome) {
            $new_amount = $customer->wallet + $welcome->welcome_amount;
            $log = WalletLog::updateOrCreate([
                'action_id' => $welcome->id,
                'customer_id' => $customer->id,
                'action' => 'welcome_money',
            ], [
                'user_id' => null,
                'customer_id' => $customer->id,
                'last_amount' => $customer->wallet ?? 0,
                'new_amount' => $new_amount,
                'action_id' => $welcome->id,
                'action' => 'welcome_money',
                'expired_days' => $welcome->expired_days,
                'expired_at' => $welcome->expired_at,
                'message_ar' => 'رصيد ترحيبي',
                'message_en' => 'welcome money',
            ]);

            $customer->update([
                'wallet' => $new_amount
            ]);
        }
        //code...
    } catch (\Throwable $th) {
        //throw $th;
        info($th->getMessage());
    }
}

function cashBack($order)
{
    try {

        $order->load('selectedAddress');

        $cashBack = Cashback::where('is_active', 1)
            ->where('country_id', $order->selectedAddress->country_id)
            ->where(function ($q) use ($order) {
                $q->whereDate('cash_back_start_date', '<=', $order->created_at)
                    ->whereDate('cash_back_end_date', '>=', $order->created_at);
            })
            ->orderBy('id', 'desc')->first();

        if ($cashBack) {
            if (
                count($cashBack->city_ids ?? []) > 0 && !in_array($order->selectedAddress->city_id, $cashBack->city_ids)
            ) {
                return;
            }

            if (count($cashBack->customer_ids ?? []) > 0 && !in_array($order->customer_id, $cashBack->customer_ids)) {
                return;
            }

            $cash_back_amount = ($order->total_amount_after_discount * $cashBack->cash_back_amount) / 100;

            if (
                count($cashBack->category_ids) > 0 || count($cashBack->sub_category_ids) > 0 || count($cashBack->product_ids) > 0
            ) {

                $order_products = OrderProduct::where('order_ref_no', $order->ref_no)->get();
                $cash_back_amount = 0;
                $discount = $order->discount_applied > 0 ? $order->discount_applied / count($order_products) : 0;

                foreach ($order_products as $key => $order_product) {

                    if (
                        (count($cashBack->category_ids ?? []) > 0 &&
                            !in_array($order_product->product->category_id, $cashBack->category_ids))
                    ) {
                        break;
                    }

                    if (
                        (count($cashBack->sub_category_ids ?? []) > 0 && !in_array($order_product->product->sub_category_id, $cashBack->sub_category_ids))
                    ) {
                        break;
                    }

                    if (
                        (count($cashBack->product_ids ?? []) > 0 && !in_array($order_product->product_id, $cashBack->product_ids))
                    ) {
                        break;
                    }

                    $cash_back_amount += ((($order_product->total_price - $discount) * $cashBack->cash_back_amount) / 100);
                }
            }

            // dd(round($cash_back_amount, 2));
            if ($cash_back_amount) {
                addCashBack($order, $cashBack, round($cash_back_amount, 2));
            }
        }
        //code...
    } catch (\Throwable $th) {
        //throw $th;
        info($th->getMessage());
    }
}

function addCashBack($order = null, $cash_back = null, $cash_back_amount = null)
{
    $log = WalletLog::where([
        'action_id' => $order->ref_no,
        'customer_id' => $order->customer_id,
        'action' => 'cash_back',
    ])->first();

    if (!$log) {
        $new_amount =  $order->customer->wallet + $cash_back_amount;

        $log = WalletLog::create([
            'action_id' => $order->ref_no,
            'customer_id' => $order->customer_id,
            'action' => 'cash_back',
            'user_id' => null,
            'customer_id' => $order->customer_id,
            'last_amount' => $order->customer->wallet,
            'new_amount' => $new_amount,
            'action_id' =>  $order->ref_no,
            'action' => 'cash_back',
            'expired_days' => $cash_back->expired_days,
            'expired_at' => $cash_back->expired_days ? Carbon::today()->addDays($cash_back->expired_days) : null,
            'message_ar' => 'كاش باك للطلب رقم ' . $order->ref_no,
            'message_en' => 'Cashback on order number ' . $order->ref_no
        ]);

        $order->customer->update([
            'wallet' => $new_amount
        ]);
    }
}

function OrderToFoodics($ref_no)
{
    // try {

    //     $order = Order::with('deliveryPeriod', 'customer', 'selectedAddress', 'paymentType')->where('ref_no', $ref_no)->first();

    //     if ($order->selectedAddress->country_id == 1) {

    //         $order_products = OrderProduct::with('size', 'preparation', 'cut')->where('order_ref_no', $order->ref_no)->get();

    //         $price = 0;

    //         $to = strlen($order->deliveryPeriod->to) < 2 ? '0' . $order->deliveryPeriod->to : $order->deliveryPeriod->to;
    //         $deliveryPeriod = $to . ':00:00';
    //         $delivery_date = date('Y-m-d H:i:s', strtotime($order->delivery_date . $deliveryPeriod . ' -3 hours'));

    //         $notes = ($order->deliveryPeriod ? $order->deliveryPeriod->name_ar . " - " : '') .
    //             (isset($order->selectedAddress->city) ? $order->selectedAddress->city->name_ar . " - " : '') .
    //             ($order->comment ?? "");
    //         $json = [
    //             "type" => 2,
    //             "status" => true,
    //             "business_date" => date('Y-m-d H:i:s', strtotime($order->created_at)),
    //             "discount_amount" => $order->discount_applied ?? 0,
    //             'branch_id' => "960fb2d5-4bd4-4d7c-bbef-538e977682ea",
    //             "due_at" => $delivery_date,
    //             "customer_notes" => $order->ref_no ?? "",
    //             "kitchen_notes" => $notes ?? '',
    //             "coupon_code" => $order->applied_discount_code ?? "",
    //             "tax_exclusive_discount_amount" => $order->tax_fees ?? "",
    //             "meta" => [
    //                 "3rd_party_order_number" => $order->ref_no ?? "111",
    //             ],
    //         ];

    //         if ($order->applied_discount_code) {
    //             $coupon = Discount::where('code', $order->applied_discount_code)->first();
    //             if ($coupon && $coupon->foodics_integrate_id) {
    //                 $json['coupon_id'] = $coupon->foodics_integrate_id ?? null;
    //             }
    //         }

    //         foreach ($order_products as $k => $pro) {

    //             if ($pro->size && isset($pro->size->foodics_integrate_id)) {
    //                 $total_price = ($pro->size->sale_price * $pro->quantity ?? 1);
    //                 $json['products'][$k] = [
    //                     "product_id" => $pro->size->foodics_integrate_id,
    //                     "quantity" => $pro->quantity ?? 1,
    //                     "unit_price" => $pro->size->sale_price,
    //                     "total_price" => $total_price,
    //                 ];

    //                 if ($pro->preparation && isset($pro->preparation->foodics_integrate_id)) {
    //                     $json['products'][$k]['options'][] = [
    //                         "modifier_option_id" => $pro->preparation->foodics_integrate_id,
    //                         "quantity" => 1,
    //                         "unit_price" => 0,
    //                     ];
    //                 }

    //                 if ($pro->cut && isset($pro->cut->foodics_integrate_id)) {
    //                     $json['products'][$k]['options'][] = [
    //                         "modifier_option_id" => $pro->cut->foodics_integrate_id,
    //                         "quantity" => 1,
    //                         "unit_price" => 0,
    //                     ];
    //                 }

    //                 if ($pro->is_kwar3) { //  without
    //                     $json['products'][$k]['options'][] = [
    //                         "modifier_option_id" => '9b9c4179-13bd-4877-b334-fbd316be7bba',
    //                         "quantity" => 1,
    //                         "unit_price" => 0,
    //                     ];
    //                 }

    //                 if ($pro->is_Ras) { // without
    //                     $json['products'][$k]['options'][] = [
    //                         "modifier_option_id" => '9b9c4194-dc59-4793-9b76-903220f255c1',
    //                         "quantity" => 1,
    //                         "unit_price" => 0,
    //                     ];
    //                 }

    //                 if ($pro->is_lyh) { //  without
    //                     $json['products'][$k]['options'][] = [
    //                         "modifier_option_id" => '9b9c4155-aa01-4061-a774-1dfc729fbb1c',
    //                         "quantity" => 1,
    //                         "unit_price" => 0,
    //                     ];
    //                 }

    //                 if ($pro->is_karashah) { //  without
    //                     $json['products'][$k]['options'][] = [
    //                         "modifier_option_id" => '9b9c41a9-a5ed-404d-a876-313bb73fbba7',
    //                         "quantity" => 1,
    //                         "unit_price" => 0,
    //                     ];
    //                 }

    //                 $price += $total_price;
    //             }
    //         }

    //         $json['subtotal_price'] = $order->order_subtotal ?? $price;
    //         $json['total_price'] = $order->total_amount_after_discount ?? $price;

    //         if (foodics_payment_methods($order->paymentType->code)) {
    //             $json['payments'][] = [
    //                 'payment_method_id' => foodics_payment_methods($order->paymentType->code),
    //                 'amount' => $order->total_amount_after_discount,
    //             ];
    //         }

    //         if ($order->customer->foodics_integrate_id) {
    //             $json['customer_id'] = $order->customer->foodics_integrate_id;
    //         } else if ($customer_id = foodics_create_or_update_customer($order->customer)) {
    //             $json['customer_id'] = $customer_id;
    //         }

    //         if (isset($json['customer_id']) && $json['customer_id']) {
    //             if ($order->selectedAddress->foodics_integrate_id) {
    //                 $json['customer_address_id'] = $order->selectedAddress->foodics_integrate_id;
    //                 $json['type'] = 3;
    //             } else if ($customer_address_id = foodics_create_or_update_customer_address($order->selectedAddress)) {
    //                 $json['customer_address_id'] = $customer_address_id;
    //                 $json['type'] = 3;
    //             }
    //         }

    //         $res = httpCurl('post', 'orders', $json);

    //         if (isset($res['id'])) {
    //             $order->update(['foodics_integrate_id' => $res['id']]);

    //             foodics_create_or_update_customer($order->customer); //

    //             return $res['id'];
    //         }
    //         return null;
    //     }
    //     return null;
    //     //code...
    // } catch (\Throwable $th) {
    //     //throw $th;
    //     return null;
    // }
    //code...
}

function foodics_create_or_update_customer($item)
{
    // try {

    //     $customer = \App\Models\Customer::find($item->id);

    //     if (!isset($customer->id)) {
    //         return null;
    //     }

    //     $data = [
    //         "name" => $customer->name ?? $customer->mobile ?? "new",
    //         "dial_code" => 966,
    //         "phone" => substr($customer->mobile ?? '+966000000000', 4) ?? '',
    //         "email" => $customer->email ?? '',
    //         "gender" => 1,
    //         "birth_date" => "1996-09-17",
    //         "tags" => [],
    //         "is_blacklisted" => false,
    //         "is_house_account_enabled" => false,
    //         "house_account_limit" => 1000,
    //         "is_loyalty_enabled" => false,
    //     ];

    //     if ($customer->foodics_integrate_id && $customer->foodics_integrate_id != 'null') {

    //         $res = httpCurl('put', 'customers/' . $customer->foodics_integrate_id, ['name' => $data['name'], 'email' => $data['email']]);

    //         return $item->foodics_integrate_id;
    //     } else {
    //         $res = $customer->mobile ? httpCurl('get', 'customers?filter[phone]=' . substr($customer->mobile, -9)) : null;
    //         if (isset($res[0]['id'])) {
    //             $id = $res[0]['id'];
    //             $customer->update(['foodics_integrate_id' => $id]);

    //             $res = httpCurl('put', 'customers/' . $id, ['name' => $data['name'], 'email' => $data['email']]);

    //             return $id;
    //         } else {

    //             $res = httpCurl('post', 'customers', $data);

    //             if (isset($res['id'])) {
    //                 $customer->update(['foodics_integrate_id' => $res['id']]);
    //                 return $res['id'];
    //             }
    //         }
    //     }

    //     return null;
    //     //code...
    // } catch (\Throwable $th) {
    //     //throw $th;
    //     return null;
    // }
}

function foodics_create_or_update_customer_address($item)
{
    // try {

    //     $address = \App\Models\Address::with('customer')->find($item->id);

    //     $data = [
    //         "name" => $address->label ?? '',
    //         "description" => $address->address . ' ' . $address->comment,
    //         "latitude" => $address->lat ?? '',
    //         "longitude" => $address->long ?? '',
    //         "delivery_zone_id" => "9bf2c28b-ea66-4f47-ab1a-e6c017d0a653",
    //     ];
    //     if ($address->customer->foodics_integrate_id) {
    //         $data["customer_id"] = $address->customer->foodics_integrate_id;
    //     } else if ($customer_id = foodics_create_or_update_customer($address->customer)) {
    //         $data['customer_id'] = $customer_id;
    //     }

    //     $res = httpCurl('post', 'addresses', $data);

    //     if (isset($res['id'])) {
    //         $address->update(['foodics_integrate_id' => $res['id']]);
    //         return $res['id'];
    //     }
    //     return null;
    //     //code...
    // } catch (\Throwable $th) {
    //     //throw $th;
    //     return null;
    // }
}

function httpCurl($method, $route, $json = [])
{
    return null;

    // try {
    // $token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiI5YmUwN2NjMS1hYjcwLTQzNzQtYTAyOC1lMmNiZjhlYjk4MDMiLCJqdGkiOiJmMDY4MzJiY2E4Y2Q2OWQ2ZTgzMWVjNDZhNWI2ZTdlNDA4ZjE5M2RkZWY3YTliMjVhNDY2YmIyMTk4ZWI2Yjg5NDdjNWIyMzk2MjdhYzRjMiIsImlhdCI6MTcxMzk1NjMyNS4zMDEyMTMsIm5iZiI6MTcxMzk1NjMyNS4zMDEyMTMsImV4cCI6MTg3MTcyMjcyNS4yNjc1MjMsInN1YiI6Ijk2MGZiMmE3LTEyYTQtNGY1OC04MDYwLTMyMDExMzMyNWUyZCIsInNjb3BlcyI6W10sImJ1c2luZXNzIjoiOTYwZmIyYTctMTJmMS00ZWZhLWI1NjctY2FlYmI2MDZiMDMxIiwicmVmZXJlbmNlIjoiOTQ5NDMwIn0.kJXEXgFsfgSyFU4TH2VU_Y5y1YsHs1ZUw2ndQ4zv7bMo-AxRUiHxN9ZwCO8RceeMWyMr5zyCbg1kwDXtp_okXUYb0BN5ytHw3s3Vz8bNFGuJVYVPwo1InvY_z8yeNT3LmwJMO6xvIgv9LbicH1cS1u0fYRbjegWBNHXDlLkPSj4LhkN3ZPP4XcxjNJXsn2QfGaKtk-TiHY2DUqhf-WSPG6O2YlD-sLXVM3QP8ir6ggYuznQ81YYhsKj6Ha_TnSX8hxdCVDeSYN2HBp91f98jMKf8YBHgiFSfmFm_NBvprNcf7JcCiqubpWPmeeT0lfpuNQvFOQknjMXAMbfMAGN8YOf7d38Z3HSzv5yVjb18bf3ZFat0y3EIOHLHmYC_oceC4OaCQvaz0yuFsdjnG0_Lz2kqtDPGW40aKURYMarW4IqELH8dsUG7F1ZJ5W62WCYfToGzEsRdhZ2d1TA6vPS_62B1Hgu9xg4BAVKb_edhNEYp8xA6nbh3CL8U3MUUSKEJ7AVW3T-vPHjk7C1xqd7oXPlzjplz40i5tSLxlGerEQVRGaiypKzjc3Dpgq4IqqkJHi5Rs1nVTcI7JPs-UtCw9-PYbkviRhpebvtsLh53qfgPjA583U0KiLzN6Nhl4vFRwZ7cLcxNgUffcYqpMt4XKGsXG_pGpc6WEdPu0HP8Le0";

    // $httpClient = new Client();

    // $data = [
    //     'headers' => [
    //         'Authorization' => "Bearer $token",
    //         'Content-Type' => 'application/json',
    //     ],
    // ];

    // if ($method == 'post' && $json) {
    //     $data['json'] = $json;
    //     $response = $httpClient->post('https://api.foodics.com/v5/' . $route, $data);
    // } elseif ($method == 'get') {
    //     $response = $httpClient->get('https://api.foodics.com/v5/' . $route, $data);
    // } elseif ($method == 'put') {
    //     $data['json'] = $json;
    //     $response = $httpClient->put('https://api.foodics.com/v5/' . $route, $data);
    // }

    // $statusCode = $response->getStatusCode();
    // $responseBody = $response->getBody()->getContents();

    // if ($statusCode == 200) {
    //     // Successful response
    //     $res = is_array($responseBody) ? $responseBody : json_decode($responseBody, true);
    //     if (isset($res['data'])) {
    //         return $res['data'];
    //     }
    // }
    // return null;
    //code...
    // } catch (\Throwable $th) {
    //throw $th;
    // info('erorrrrrrrrrr');
    // info($th->getMessage());
    // return null;
    // }
}

function streamOrder($number, $event = null) // solution 2 for server
{
    if (in_array('logistic_manager', auth()->user()->roles->pluck('name')->toArray())) {
        return null;
    }

    $sse = DB::table('orders')
        ->select(
            'orders.*',
            'customers.name as customer_name',
            'customers.mobile as customer_mobile',
            'order_states.state_ar as order_state_ar',
            'order_states.state_en as order_state_en',
            'shalwatas.name_ar as shalwata_name',
            'shalwatas.price as shalwata_price',
            'payment_types.name_ar as payment_type_name',
            'payment_types.code as payment_type_code',
            'delivery_periods.name_ar as delivery_period_name',
            'delivery_periods.time_hhmm as delivery_period_time',
            'payments.price as payment_price',
            'payments.status as payment_status',
            'addresses.address as address_address',
            'addresses.lat as address_lat',
            'addresses.long as address_long',
            'addresses.country_id as address_country_id',
            'addresses.city_id as address_city_id',
            'cities.name_ar as city_name',
            'users.username as sales_officer_name',
            'u.username as driver_name',
            'u.id as driver_id'
        )
        ->where('orders.ref_no', $number)
        ->join('customers', 'customers.id', '=', 'orders.customer_id')
        ->leftJoin('users as u', 'u.id', '=', 'orders.user_id')
        ->leftJoin('users', 'users.id', '=', 'orders.sales_representative_id')
        ->leftJoin('order_states', 'order_states.code', '=', 'orders.order_state_id')
        ->leftJoin('shalwatas', 'shalwatas.id', '=', 'orders.shalwata_id')
        ->leftJoin('payment_types', 'payment_types.id', '=', 'orders.payment_type_id')
        ->leftJoin('delivery_periods', 'delivery_periods.id', '=', 'orders.delivery_period_id')
        ->leftJoin('payments', 'payments.id', '=', 'orders.payment_id')
        ->leftJoin('addresses', 'addresses.id', '=', 'orders.address_id')
        ->leftJoin('cities', 'cities.id', '=', 'addresses.city_id')
        ->first();

    sse_notify(json_encode($sse), null, $event);
}

function foodics_payment_methods($type)
{
    $data = [
        "COD" => [
            "id" => "9bf0d631-7acc-4cde-abea-af87d663f9bf",
            "name" => "Cash",
        ],
        "ARB" => [
            "id" => "9bf0d637-0193-44fd-accc-49d1d68a613a",
            "name" => "MyFatoorah",
        ],
        "MyFatoorah" => [
            "id" => "9bf0d637-0193-44fd-accc-49d1d68a613a",
            "name" => "MyFatoorah",
        ],
        "tamara" => [
            "id" => "9bf0d65a-b6b1-4c4f-97ff-67c86e456afb",
            "name" => "تمارا",
        ],
        "Tabby" => [
            "id" => "9bf0d677-e7aa-4b7b-a0d4-49db0c1d3495",
            "name" => "تابي",
        ],
    ];

    if (isset($data[$type]['id'])) {
        return $data[$type]['id'];
    }
    return null;
}

function generateQrInvoice($order)
{
    if (!$order) {
        return;
    }

    $vat = $order->tax_fees;
    $total = $order->total_amount_after_discount;
    $date = $order->created_at;

    return base64_encode(
        ConvertHex("01") . ConvertHex(toHex(strlen("تركي للذبائح"))) . "تركي للذبائح"
            . ConvertHex("02") . ConvertHex(toHex(strlen("310841577800003"))) . "310841577800003"
            . ConvertHex("03") . ConvertHex(toHex(strlen($date))) . $date
            . ConvertHex("04") . ConvertHex(toHex(strlen($total))) . $total
            . ConvertHex("05") . ConvertHex(toHex(strlen($vat))) . $vat
    );
}

if (!function_exists('ConvertHex')) {

    function ConvertHex($hex)
    {
        $ascii = "";
        $hexLen = strlen($hex);

        for ($i = 0; $i < $hexLen; $i += 2) {
            $ascii .= chr(HexadecimalToDecimal(substr($hex, $i, 2)));
        }

        return $ascii;
    }
}

if (!function_exists('HexadecimalToDecimal')) {

    function HexadecimalToDecimal($hex)
    {
        $hex = strtoupper($hex);

        $hexLength = strlen($hex);
        $dec = 0;

        for ($i = 0; $i < $hexLength; $i++) {
            $b = ord($hex[$i]);

            if ($b >= 48 && $b <= 57) {
                $b -= 48;
            } else if ($b >= 65 && $b <= 70) {
                $b -= 55;
            }

            $dec += $b * pow(16, (($hexLength - $i) - 1));
        }

        return (int) $dec;
    }
}

if (!function_exists('toHex')) {
    function toHex($number)
    {
        $hexvalues = array(
            '0',
            '1',
            '2',
            '3',
            '4',
            '5',
            '6',
            '7',
            '8',
            '9',
            'A',
            'B',
            'C',
            'D',
            'E',
            'F',
        );
        $hexval = '';
        while ($number != '0') {
            $hexval = $hexvalues[bcmod($number, '16')] . $hexval;
            $number = bcdiv($number, '16', 0);
        }
        return $hexval;
    }
}

if (!function_exists('handleRoleOrderState')) {
    function handleRoleOrderState($roles)
    {

        //     'state_ar' => "تم استلام الطلب",
        //     'code' => "100",

        //     'customer_state_ar' => "تم تأكيد الطلب",
        //     'code' => "101",

        //     'customer_state_ar' => "معلق",
        //     'code' => "102",

        //     'customer_state_ar' => "ملغي",
        //     'code' => "103",

        //     'customer_state_ar' => "جاري التجهيز",
        //     'code' => "104",

        //     'customer_state_ar' => "تم التجهيز",
        //     'code' => "105",

        //     'customer_state_ar' => "جاري التوصيل",
        //     'code' => "106",

        //     'customer_state_ar' => "تم الارجاع",
        //     'code' => "107",

        //     'customer_state_ar' => "تم الارجاع بشكل جزئي",
        //     'code' => "108",

        //     'customer_state_ar' => "يوجد مشكلة في التوصيل",
        //     'code' => "109",

        //     'customer_state_ar' => "تم التوصيل",
        //     'code' => "200",

        if (in_array('admin', $roles)) { // 'admin' => 'مدير النظام',
            $data = [
                'status' => OrderState::pluck('new_code')->toArray(),
                'orders' => OrderState::pluck('new_code')->toArray(),
            ];
        }

        if (in_array('production_manager', $roles)) { // 'production_manager' => 'مسئول الانتاج',/////////////////
            $data = [
                'status' => ['104', '105',  '204', '208', '202', '203', '205', '206', '207', '209', '300', '301'],
                'orders' => ['101', '104', '105', '106', '200', '204', '208', '202', '300', '301'],
            ];
        }

        if (in_array('production_supervisor', $roles)) { // 'production_manager' => 'مشرف الانتاج',/////////////////
            $data = [
                'status' => ['104', '105',  '204', '208', '202', '203', '205', '206', '207', '209', '300', '301'],
                'orders' => ['101', '104', '105', '106', '200', '204', '208', '202', '300', '301'],
            ];
        }

        if (in_array('logistic_manager', $roles)) { // 'logistic_manager' => 'مسئول لوجيستي',///////////////////
            $data = [
                'status' => ['103', '104', '106', '200', '201', '206'],
                'orders' => ['101', '104', '105', '106', '109', '200', '201', '206'],
            ];
        }
        if (in_array('store_manager', $roles)) { // 'store_manager' => 'مسئول المبيعات', //////////////
            $data = [
                'status' => ['101', '102', '103', '106', '109', '200', '204', '208', '202', '203', '205', '206', '207', '209', '300', '301'],
                'orders' => ['100', '101', '102', '103', '104', '105', '106', '109', '200', '204', '208', '202', '203', '205', '206', '207', '209', '300', '301'],
            ];
        }
        if (in_array('general_manager', $roles)) { // 'general_manager' => 'مشرف المبيعات',/////////////////
            $data = [
                'status' => ['101', '102', '103', '106', '109', '200', '204', '208', '202', '203', '205', '206', '207', '209', '300', '301'],
                'orders' => ['100', '101', '102', '103', '104', '105', '106', '107', '108', '109', '200', '204', '208', '202', '203', '205', '206', '207', '209', '300', '301'],
            ];
        }
        if (in_array('delegate', $roles)) { // 'delegate' => 'مندوب',///////////////////
            $data = [
                'status' => ['103', '109', '200'],
                'orders' => ['106', '109'],
            ];
        }
        if (in_array('cashier', $roles)) { // 'cashier' => 'مندوب',///////////////////
            $data = [
                'status' => ['201', '202', '203', '206', '207'],
                'orders' => ['201', '202', '203', '206', '207'],
            ];
        }

        if (isset($data)) {
            if (Schema::hasColumn('order_states', 'new_code')) {
                $data = [
                    'status' => OrderState::whereIn('new_code', $data['status'])->pluck('code')->toArray(),
                    'orders' => OrderState::whereIn('new_code', $data['orders'])->pluck('code')->toArray(),
                ];
            }

            return $data;
        } else {
            return [
                'status' => OrderState::pluck('new_code')->toArray(),
                'orders' => OrderState::pluck('new_code')->toArray(),
            ];
        }
    }
}

if (!function_exists('handleDate')) {

    function handleDate($date)
    {
        $all = explode('-', $date);

        if (isset($all[0]) && strlen($all[0]) > 3) {
            $date = date('Y-m-d', strtotime($date));
        } elseif (isset($all[2]) && strlen($all[2]) > 3) {
            $date = date('Y-m-d', strtotime($all[2] . '-' . $all[1] . '-' . $all[0]));
        } elseif (!isset($all[2])) {
            $date = date('Y-m-d', strtotime(date('Y') . '-' . $date));
        } else {
            $date = date('Y-m-d', strtotime($date));
        }

        return $date;
    }
}

if (!function_exists('Paginator')) {

    function successResponse($data = [], $msg = 'success')
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => $msg,
            'description' => $msg,
            'code' => '200',
        ], 200);
    }
}

if (!function_exists('Paginator')) {

    function failResponse($data = null, $msg = 'fail', $code = 400)
    {
        return response()->json([
            'success' => false,
            'data' => $data,
            'message' => $msg,
            'description' => $msg,
            'code' => $code,
        ], $code);
    }
}

if (!function_exists('Paginator')) {
    /**
     * array manually pagination helper.
     *
     * @param Request $request
     * @param int $perPage
     * @param array $array
     * @return LengthAwarePaginator
     */
    function Paginator(Request $request, $array = [])
    {
        $page = isset($request->page) ? $request->page : 1; // Get the page=1 from the url
        $perPage = isset($request->per_page) ? $request->per_page : 6; // Number of items per page
        $offset = ($page * $perPage) - $perPage;

        $entries = new LengthAwarePaginator(
            array_slice($array, $offset, $perPage, true),
            count($array), // Total items
            $perPage, // Items per page
            $page, // Current page
            // this can keep all old query parameters from the url
            ['path' => $request->url(), 'query' => $request->query()]
        );
        return $entries;
    }
}

if (!function_exists('PerPage')) {
    /**
     * array manually pagination helper.
     *
     * @param Request $request
     * @param int $perPage
     * @param array $array
     * @return LengthAwarePaginator
     */
    function PerPage(Request $request)
    {
        $perPage = 6;
        if ($request->has('per_page')) {
            $perPage = $request->get('per_page');
        }

        if ($perPage == 0) {
            $perPage = 6;
        }

        return $perPage;
    }
}

if (!function_exists('GetNextOrderRefNo')) {
    /**
     * @return string
     */
    function GetNextOrderRefNo($countryCode, $id)
    {
        $genId = date('y') . str_pad($id, 9, "0", STR_PAD_LEFT);
        return $countryCode . 'O' . $genId;
    }
}

if (!function_exists('GetNextPaymentRefNo')) {
    /**
     * @return string
     */
    function GetNextPaymentRefNo($countryCode, $id)
    {
        $genId = date('y') . str_pad($id, 9, "0", STR_PAD_LEFT);
        return $countryCode . 'P' . $genId;
    }
}

if (!function_exists('trackingIdGenerator')) {
    /**
     * trackingId Generator.
     * @param $validated
     * @return string
     */
    function trackingIdGenerator($validated)
    {
        $t = Carbon::now()->unix();
        $t3 = $validated->id . ($t / $validated->receiver_phone);
        return floor($t3 * 100000);
    }
}

if (!function_exists('OTPGenerator')) {
    /**
     * OTP Generator.
     * @return string
     * @throws Exception
     */
    function OTPGenerator()
    {
        return random_int(0000, 9999);
    }
}

if (!function_exists('UIDGenerator')) {
    /**
     * Uniq Generator.
     * @return string
     * @throws Exception
     */
    function UIDGenerator()
    {
        return (string) Str::orderedUuid();
    }
}

if (!function_exists('HaversineGreatCircleDistance')) {
    /**
     * Calculates the great-circle distance between two points, with
     * the Haversine formula.
     * @param float $latitudeFrom Latitude of start point in [deg decimal]
     * @param float $longitudeFrom Longitude of start point in [deg decimal]
     * @param float $latitudeTo Latitude of target point in [deg decimal]
     * @param float $longitudeTo Longitude of target point in [deg decimal]
     * @param float $earthRadius Mean earth radius in [m]
     * @return float Distance between points in [m] (same as earthRadius)
     */
    function HaversineGreatCircleDistance(
        $latitudeFrom,
        $longitudeFrom,
        $latitudeTo,
        $longitudeTo,
        $earthRadius = 6371000
    ) {
        // convert from degrees to radians
        $latFrom = deg2rad($latitudeFrom);
        $lonFrom = deg2rad($longitudeFrom);
        $latTo = deg2rad($latitudeTo);
        $lonTo = deg2rad($longitudeTo);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
        return $angle * $earthRadius;
    }
}
