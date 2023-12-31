<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Cart;
use App\Models\TraceError;
use App\Models\Country;
use App\Models\Customer;
use App\Models\DeliveryDate;
use App\Models\DeliveryDateCity;
use App\Models\DeliveryFee;
use App\Models\Discount;
use App\Models\MinOrder;
use App\Models\Order;
use App\Models\Payment;
use App\Models\OrderProduct;
use App\Models\PaymentType;
use App\Models\Product;
use App\Models\Shalwata;
use App\Services\AlRajhiPaymentService;
use App\Services\TamaraApiService;
use App\Services\TamaraApiServiceV2;
use App\Services\CallNetsuiteApi;
use App\Services\NgeniusPaymentService;
use App\Services\MyFatoorahApiService;
use App\Services\TabbyApiService;
use App\Services\CallOrderNetsuiteApi;
use App\Services\PointLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Size;
use App\Models\ProductImage;

class OrderTest2Controller extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    function __construct()
    {
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function getOrders(Request $request)
    {
       
        $orders = Order::where('customer_id', auth()->user()->id)->with('orderProducts','orderState', 'products', 'deliveryPeriod', 'selectedAddress')->orderBy('id', 'desc')->get();

        return response()->json(['success' => true,'data'=> $orders,
            'message'=> 'Products retrieved successfully', 'description'=> '', 'code'=>'200'],200);
    }
    
    
    //       public function getOrdersV2(Request $request)
    // {
    //      $perPage = 6;
    //     if ($request->has('per_page'))
    //         $perPage = $request->get('per_page');

    //     if ($perPage == 0)
    //         $perPage = 6;
            
    //     $orders = Order::select('ref_no','order_state_id','created_at','delivery_date', 'total_amount_after_discount')
    //     ->with('orderProducts:order_ref_no,size_id,quantity,product_id',
    //     'orderProducts.product.productImages:id,image,thumbnail,is_default',
    //     'orderProducts.size:id,name_ar',
    //     'orderState:code,state_ar,state_en')
    //     ->where('customer_id', auth()->user()->id)->get();    
    //     //  $ordersP = OrderProduct::select('quantity', 'size_id', 'product_id')->whereIn('order_ref_no', $orders->pluck('ref_no'));    
    //     // $size = Size::select("name_ar", "name_en")->where("id", $ordersP->pluck("size_id"))->get();
    //     // $prod = ProductImage::select("image", "thumbnail", "is_default")->where("product_id", $ordersP->pluck("product_id"))->get();
    //     // // $orders = Order::where('customer_id', auth()->user()->id)->with('orderProducts','orderState', 'deliveryPeriod', 'selectedAddress')->orderBy('id', 'desc')->paginate($perPage);
        
    //     // $res[0] = $orders->get();
    //     // $res[0][1]["orderProduct"] = $ordersP->get()->toArray();
    //     // $res[0][1]["productNames"] = $size->toArray();
    //     // $res[0][1]["product_images"] = $prod->toArray();
    //     return response()->json(['success' => true,'data'=> $orders,
    //         'message'=> 'Products retrieved successfully', 'description'=> "", 'code'=>'200'],200);
    // }
    
      public function getOrdersV2(Request $request)
    {
         $perPage = 6;
        if ($request->has('per_page'))
            $perPage = $request->get('per_page');

        if ($perPage == 0)
            $perPage = 6;
        
        $orders = Order::where('customer_id', auth()->user()->id)->with('orderProducts','orderState', 'deliveryPeriod', 'selectedAddress')->orderBy('id', 'desc')->paginate($perPage);
        
        return response()->json(['success' => true,'data'=> $orders,
            'message'=> 'Products retrieved successfully', 'description'=> "", 'code'=>'200'],200);
    }

    public function getOrdersDashboard(Request $request)
    {
        $perPage = 6;
        if ($request->has('per_page'))
            $perPage = $request->get('per_page');

        if ($perPage == 0)
            $perPage = 6;
            
        $orders = Order::with('customer','orderProducts','orderState', 'products', 'deliveryPeriod', 'selectedAddress')->orderBy('id', 'desc')->paginate($perPage);

        return response()->json(['success' => true,'data'=> $orders,
            'message'=> 'retrieved successfully', 'description'=> '', 'code'=>'200'],200);
    }

    public function getOrderByRefNo($order)
    {
        $order = Order::where([['customer_id', auth()->user()->id], ['ref_no', $order]])->with('orderProducts', 'orderState', 'deliveryPeriod', 'selectedAddress')->get()->first();
        if($order != null)
            return response()->json(['success' => true,'data'=> $order,
                'message'=> 'Products retrieved successfully', 'description'=> '', 'code'=>'200'],200);
        else
            return response()->json(['success' => false,'data'=> null,
                'message'=> 'order not found!', 'description'=> '', 'code'=>'404'],404);
    }
    
    

    public function createOrder(Request $request)
    {
           TraceError::create(['class_name' => "create order 351", 'method_name' => "Get_Payment_Status", 'error_desc' => json_encode($request->all())]);  
          $customer = Customer::find(auth()->user()->id);
    
        if($customer->integrate_id == null){
            TraceError::create(['class_name'=> "CallOrderNetsuiteApi::responce", 'method_name'=>"sendOrderToNS 1", 'error_desc' => json_encode($customer)]);
            $this->registerNSS($customer,$request);
         } else {
              TraceError::create(['class_name'=> "CallOrderNetsuiteApi::responce", 'method_name'=>"sendOrderToNS 2", 'error_desc' => json_encode($customer)]);
         }
         

        $app = ($request->query('app') == 1? 1 : 0);
        $point = $request->query('longitude') . " " . $request->query('latitude');
        $countryId = $request->query('countryId');
        $country = Country::where('code', $countryId)->get()->first();

        if ($country === null)
            return response()->json(['data' => [],
                'success' => true, 'message' => 'success', 'description' => 'this service not available in your country!', 'code' => '200'], 200);

        $currentCity = app(PointLocation::class)->getLocatedCity($country, $point);

        if ($currentCity === null)
            return response()->json(['data' => [],
                'success' => true, 'message' => 'success', 'description' => 'this service not available in your city!', 'code' => '200'], 200);

        //todo: remove "delivery_date" the one that not used!
        $validated = $request->validate([
            "comment"  => 'nullable|string',
            "delivery_date" => 'required|date',
            "delivery_date" => array('required', 'regex:(^(0[1-9]|1[012])\-(0[1-9]|[12][0-9]|3[01])+$)'), // 01-29 or 12-29
            "delivery_period_id" => array('required', 'exists:delivery_periods,id'),
            "payment_type_id" => 'required|exists:payment_types,id',
            "using_wallet" => 'required|boolean',
            'address_id' => 'required|exists:addresses,id',
            'tamara_payment_name' => array('required_if:payment_type_id,==,4', 'in:PAY_BY_INSTALMENTS,PAY_BY_LATER'), // add 'tamara in payment_types table with id 4
            'no_instalments' => array('required_if:tamara_payment_name,==,PAY_BY_INSTALMENTS', 'numeric')
        ]);

        if(!isset($validated["comment"])){
            $validated["comment"] = null;
        }
        
        if ($validated["using_wallet"] == 1 && $customer->wallet == 0) {
            return response()->json(['success' => false, 'data' => [],
                'message' => 'failed', 'description' => 'your wallet is empty!', 'code' => '400'], 400);
        }

        $deliveryDate = DeliveryDate::where('date', $validated["delivery_date"])->get()->first();
        //if null, means allowed date, period.
        if ($deliveryDate != null) {

            $deliveryPeriodCity = DeliveryDateCity::where([
                ['city_id', $currentCity->id],
                ['delivery_date', $deliveryDate],
                ['delivery_period_id', $validated['delivery_period_id']]
            ])->get()->first();

            if ($deliveryPeriodCity != null)
                return response()->json(['success' => false, 'data' => [],
                    'message' => 'failed', 'description' => 'select valid delivery date/period!', 'code' => '400'], 400);
        }


        $cart = Cart::where([['customer_id', auth()->user()->id],['city_id', $currentCity->id]])->get();

        if (count($cart) == 0) {
            return response()->json(['success' => false, 'data' => [],
                'message' => 'failed', 'description' => 'add itmes to your cart first!', 'code' => '400'], 400);
        }

        $address = Address::where([['customer_id', auth()->user()->id], ['id', $validated["address_id"]]])->get()->first();
        if ($address === null)
            return response()->json(['success' => false, 'data' => [],
                'message' => 'failed', 'description' => 'invalid address', 'code' => '400'], 400);

        if($address->city_id != $currentCity->id){
            return response()->json(['success' => false, 'data' => [],
                'message' => 'failed', 'description' => 'invalid address, your location does not match with your selected address!', 'code' => '400'], 400);
        }

        $shalwata = Shalwata::first();
        $totalItemsAmount = 0.0;
        $totalAddonsAmount = 0.0;
        $TotalAmountBeforeDiscount = 0.0;
        $TotalAmountAfterDiscount = 0.0;
        $orderProducts = [];
        $discountCode = null;
        $discountAmount = 0;
        $comment = null;
    
        $applied_discount_code = $cart[0]['applied_discount_code'];
        list($cartProduct, $discountCode, $totalAddonsAmount, $totalItemsAmount, $orderProducts) = $this->calculateProductsAmount($cart, $applied_discount_code, $shalwata, $totalAddonsAmount, $totalItemsAmount, $orderProducts);
      
        $TotalAmountBeforeDiscount = $totalAddonsAmount + $totalItemsAmount;

        $miniOrderValue = MinOrder::where("country_id", $country->id)->get()->first();

        $minOrderPerCity = MinOrder::where('city_id', $currentCity->id)->first();

        if ($miniOrderValue != null && $miniOrderValue->min_order > $TotalAmountBeforeDiscount)
            return response()->json(['success' => false, 'data' => [],
                'message' => 'failed', 'description' => "minimum order value should be more that or equal $miniOrderValue->min_order $country->currency_en!", 'code' => '400'], 400);

        if ($minOrderPerCity != null && $minOrderPerCity->min_order > $TotalAmountBeforeDiscount)
            return response()->json(['success' => false, 'data' => [],
                'message' => 'failed', 'description' => "minimum order value should be more that or equal $miniOrderValue->min_order $country->currency_en!", 'code' => '400'], 400);


        if ($discountCode != null) {
            list($couponValid, $discountAmount, $TotalAmountAfterDiscount, $couponValidatingResponse) = app(CouponController::class)->discountProcess($discountCode, $cartProduct, $TotalAmountBeforeDiscount, $discountAmount, $TotalAmountAfterDiscount, $country->id, $currentCity->id);
            if ($couponValid == null) {
                return response()->json(['success' => false, 'data' => Cart::where('customer_id', auth()->user()->id)->get(),
                    'message' => $couponValidatingResponse[0] .":". $couponValidatingResponse[1], 'description' => 'invalid coupon used', 'code' => '400'], 400);
            }
        } else {
            $TotalAmountAfterDiscount = $TotalAmountBeforeDiscount;
        }

        $customer = Customer::find(auth()->user()->id);
        #$delivery = DeliveryFee::where('city_id', $currentCity->id)->get()->first();
        $delivery = 0;
        $walletAmountUsed = 0;
        $wallet = $customer->wallet;
        $TotalAmountAfterDiscount = $TotalAmountAfterDiscount + $delivery;
        if ($validated["using_wallet"] == 1) {

            if ($TotalAmountAfterDiscount >= $wallet) {
                $TotalAmountAfterDiscount = $TotalAmountAfterDiscount - $wallet;
                $walletAmountUsed = $wallet;
                $customer->wallet = 0;
                $customer->save();

            } else {
                $walletAmountUsed = $TotalAmountAfterDiscount;
                $customer->wallet = $wallet - $TotalAmountAfterDiscount;
                $customer->save();
                $TotalAmountAfterDiscount = 0;
            }


        }

        $lastOrder = Order::latest('id')->first();
        $order = [
            'ref_no' => GetNextOrderRefNo($country->code, $lastOrder != null ? $lastOrder->id + 1 : 1),
            'delivery_fee' => $delivery,
            'order_subtotal' => $TotalAmountBeforeDiscount,
            'total_amount' => $TotalAmountBeforeDiscount + $delivery,
            'total_amount_after_discount' => $TotalAmountAfterDiscount,
            'discount_applied' => $discountAmount,
            'delivery_date' => $validated["delivery_date"],
            'delivery_period_id' => $validated["delivery_period_id"],
            "comment" => $validated["comment"],
            "using_wallet" => $validated["using_wallet"],
            'wallet_amount_used' => $walletAmountUsed,
            "address_id" => $validated["address_id"],
            "address" => '',
            'customer_id' => auth()->user()->id,
            'payment_type_id' => $validated['payment_type_id'],
            'applied_discount_code' => $discountCode,
            'version_app' => $app
            // "integrate_id" => 0
        ];

        $createdOrder = Order::create($order);

        foreach ($orderProducts as $orderProduct) {
            $orderProduct['order_ref_no'] = $createdOrder->ref_no;
            OrderProduct::create($orderProduct);

            $saled = Product::find($orderProduct['product_id']);
            $saled->no_sale += 1;
            $saled->update();
        }



        Cart::where([['customer_id', auth()->user()->id],['city_id', $currentCity->id]])->delete();



        $paymentType = PaymentType::find($validated['payment_type_id']);

        if ($paymentType->code === "COD" || $TotalAmountAfterDiscount == 0) { // cod


            $res = app(CallOrderNetsuiteApi::class)->sendOrderToNS($order , $request);

            $res2 =$res->custrecord_trk_order_saleorder->internalid;

            if($res != null && $res2 != null && !isset($res->status)){

                $orderToNS = Order::Find($order['ref_no']);

                $orderToNS->update(['integrate_id' => $res->id]);

                $orderToNS->update(['saleOrderId'=> $res2]);
            }
            return response()->json(['success' => true, 'data' => $createdOrder,
                'message' => '', 'description' => '', 'code' => '200'], 200);
        } else {

            if ($country->code == 'SA' && $paymentType->code === "ARB") {
                
                if($paymentType->active === 1)
                {
                $paymentRes = app(MyFatoorahApiService::class)->Set_Payment_myfatoora($customer, $createdOrder, $paymentType, $country, 'KSA');
                }
                else{
                         return response()->json(['success' => true, 'data' => $createdOrder,
                    'message' => '', 'description' => '', 'code' => '200'], 200);
                }
               
                $res = app(CallOrderNetsuiteApi::class)->sendOrderToNS($order , $request);

                $res2 = $res->custrecord_trk_order_saleorder->internalid;

                if($res != null && $res2 != null && !isset($res->status)){

                    $orderToNS = Order::Find($order['ref_no']);

                    $orderToNS->update(['integrate_id' => $res->id]);

                    $orderToNS->update(['saleOrderId'=> $res2]);
                }

                return response()->json(['success' => true, 'data' => $paymentRes,
                    'message' => '', 'description' => '', 'code' => '200'], 200);

                // $paymentRes = app(AlRajhiPaymentService::class)->createARBpayment($customer, $createdOrder, $paymentType, $country);

                // if ($paymentRes['success'] == true){

                //     $res = app(CallOrderNetsuiteApi::class)->sendOrderToNS($order , $request);

                //     // if(isset($res->error) && $res->error->code='UNIQUE_CUST_ID_REQD'){

                //     //     $res = app(CallNetsuiteApi::class)->sendCustomerToNS($customer , $request);

                //     //     if(!isset($res->status)){
                //     //         $customer->update(['integrate_id' => $res->id]);

                //     //         $res = app(CallOrderNetsuiteApi::class)->sendOrderToNS($order , $request);
                //     //     }
                //     // }

                //     $res2 = $res->custrecord_trk_order_saleorder->internalid;

                //     if($res != null && $res2 != null && !isset($res->status)){

                //         $orderToNS = Order::Find($order['ref_no']);

                //         $orderToNS->update(['integrate_id' => $res->id]);

                //         $orderToNS->update(['saleOrderId'=> $res2]);
                //     }

                //     return response()->json(['success' => true, 'data' => $paymentRes,
                //         'message' => '', 'description' => '', 'code' => '200'], 200);
                // }else {
                //     return response()->json(['success' => false, 'data' => $paymentRes,
                //         'message' => '', 'description' => 'something went wrong, contact support!', 'code' => '400'], 400);
                // }
            }
            
            
            elseif ($paymentType->code === "tamara") {

                if (isset($validated['no_instalments'])){
                    $paymentRes = app(TamaraApiService::class)->checkoutTamara($customer,$address, $createdOrder, $validated['tamara_payment_name'], $country,$validated['no_instalments']);

                       $res = app(CallOrderNetsuiteApi::class)->sendOrderToNS($order , $request);

                   
                    $res2 = $res->custrecord_trk_order_saleorder->internalid;

                    if($res != null && $res2 != null && !isset($res->status)){

                        $orderToNS = Order::Find($order['ref_no']);

                        $orderToNS->update(['integrate_id' => $res->id]);

                        $orderToNS->update(['saleOrderId'=> $res2]);
                    }

                }else{
                    $paymentRes = app(TamaraApiService::class)->checkoutTamara($customer,$address, $createdOrder, $validated['tamara_payment_name'], $country);
                }
                        
           
               return response()->json(['success' => true, 'data' => $paymentRes,
                   'message' => '', 'description' => '', 'code' => '200'], 200);

            }
            elseif ($paymentType->code === "tamara-v2") {

                if (isset($validated['no_instalments'])){
                    $paymentRes = app(TamaraApiServiceV2::class)->checkoutTamara($customer,$address, $createdOrder, $validated['tamara_payment_name'], $country,$validated['no_instalments']);

                    //   $res = app(CallOrderNetsuiteApi::class)->sendOrderToNS($order , $request);

                   
                    // $res2 = $res->custrecord_trk_order_saleorder->internalid;

                    // if($res != null && $res2 != null && !isset($res->status)){

                    //     $orderToNS = Order::Find($order['ref_no']);

                    //     $orderToNS->update(['integrate_id' => $res->id]);

                    //     $orderToNS->update(['saleOrderId'=> $res2]);
                    // }

                }else{
                    $paymentRes = app(TamaraApiServiceV2::class)->checkoutTamara($customer,$address, $createdOrder, $validated['tamara_payment_name'], $country);
                }
                        
           
               return response()->json(['success' => true, 'data' => $paymentRes,
                   'message' => '', 'description' => '', 'code' => '200'], 200);

            } 
          
            elseif ($country->code == 'AE' && $paymentType->code === "ARB") {
              
            //   $paymentRes = app(NgeniusPaymentService::class)->createNgeniusPayment($customer, $createdOrder, $paymentType, $country);
               
            //     $res = app(CallOrderNetsuiteApi::class)->sendOrderToNS($order , $request);

            //     $res2 = $res->custrecord_trk_order_saleorder->internalid;

            //     if($res != null && $res2 != null && !isset($res->status)){

            //         $orderToNS = Order::Find($order['ref_no']);

            //         $orderToNS->update(['integrate_id' => $res->id]);

            //         $orderToNS->update(['saleOrderId'=> $res2]);
            //     }

            //     return response()->json(['success' => true, 'data' => $paymentRes,
            //         'message' => '', 'description' => '', 'code' => '200'], 200);
              
              $paymentRes = app(MyFatoorahApiService::class)->Set_Payment_myfatoora($customer, $createdOrder, $paymentType, $country);
               
                $res = app(CallOrderNetsuiteApi::class)->sendOrderToNS($order , $request);

                $res2 = $res->custrecord_trk_order_saleorder->internalid;

                if($res != null && $res2 != null && !isset($res->status)){

                    $orderToNS = Order::Find($order['ref_no']);

                    $orderToNS->update(['integrate_id' => $res->id]);

                    $orderToNS->update(['saleOrderId'=> $res2]);
                }

                return response()->json(['success' => true, 'data' => $paymentRes,
                    'message' => '', 'description' => '', 'code' => '200'], 200);
            } 
            
              elseif ($paymentType->code === "Tabby") {
    
                  $paymentRes = app(TabbyApiService::class)->createManualPayment($customer,$address, $createdOrder,$country);
       
                    $res = app(CallOrderNetsuiteApi::class)->sendOrderToNS($order , $request);
    
                    $res2 = $res->custrecord_trk_order_saleorder->internalid;
    
                    if($res != null && $res2 != null && !isset($res->status)){
    
                        $orderToNS = Order::Find($order['ref_no']);
    
                        $orderToNS->update(['integrate_id' => $res->id]);
    
                        $orderToNS->update(['saleOrderId'=> $res2]);
                    }
    
                    return response()->json(['success' => true, 'data' => $paymentRes,
                        'message' => '', 'description' => '', 'code' => '200'], 200);
                }
    
            
            elseif ($paymentType->code === "Ngenius") {
              
              $paymentRes = app(NgeniusPaymentService::class)->createNgeniusPayment($customer, $createdOrder, $paymentType, $country);
               TraceError::create(['class_name' => "OrderController", 'method_name' => 'create ngenius payment', 'error_desc' => '-'.$order['ref_no'].'- sending order to Netsuite :'.json_encode($createdOrder)]);
                    
                $res = app(CallOrderNetsuiteApi::class)->sendOrderToNS($order , $request);
                TraceError::create(['class_name' => "OrderController", 'method_name' => 'create ngenius payment', 'error_desc' => '-'.$order['ref_no'].'- sent order to Netsuite :'.json_encode($res)]);
                 
                $res2 = $res->custrecord_trk_order_saleorder->internalid;

                if($res != null && $res2 != null && !isset($res->status)){

                    $orderToNS = Order::Find($order['ref_no']);

                    $orderToNS->update(['integrate_id' => $res->id]);

                    $orderToNS->update(['saleOrderId'=> $res2]);
                }

                return response()->json(['success' => true, 'data' => $paymentRes,
                    'message' => '', 'description' => '', 'code' => '200'], 200);
            } else {
                TraceError::create(['class_name' => "create order 351", 'method_name' => "Get_Payment_Status", 'error_desc' => json_encode($createdOrder)]);  
                return response()->json(['success' => false, 'data' => $createdOrder,
                    'message' => 'Please, contact support with ref: ' . $createdOrder->ref_no, 'description' => '', 'code' => '400'], 400);
                   
            }
        }
    }


   function registerNSS($customer, Request $request)
    {
        
        if($customer->name == '')
        {
            $customer->update(['name'=>'user'.$customer->id]);
        };  
            
        $res = app(CallNetsuiteApi::class)->sendCustomerToNS($customer , $request);

        if($res != null){
             $customer->update(['integrate_id' => $res->id]);
             return 1;
        
        }else{
            return 0;
        }
    }

    /**
     * @param $cart
     * @param $discountCode
     * @param $shalwata
     * @param $totalAddonsAmount
     * @param $totalItemsAmount
     * @param array $orderProducts
     * @return array
     */
    public function calculateProductsAmount($cart, $discountCode, $shalwata, $totalAddonsAmount, $totalItemsAmount, array $orderProducts): array
    {
        TraceError::create(['class_name'=> "orderController::consumer sent data360", 'method_name'=>"checkValidation", 'error_desc' => json_encode($discountCode)]);
        foreach ($cart as $cartProduct) {
            $product = $cartProduct->product;
            $itemsAmount = 0.0;
            $addonsAmount = 0.0;
         //   $discountCode = $cartProduct->applied_discount_code;
            $comment = $cartProduct->comment;
        TraceError::create(['class_name'=> "orderController::consumer sent data368", 'method_name'=>"checkValidation", 'error_desc' => json_encode($discountCode)]);
            if ($cartProduct->preparation_id != null && $product->productPreparations()->find($cartProduct->preparation_id) != null) {
                $addonsAmount = $addonsAmount + ($cartProduct->quantity * $cartProduct->preparation->price);
            }

            if ($cartProduct->size_id != null && $product->productSizes()->find($cartProduct->size_id) != null) {
                $addonsAmount = $addonsAmount + ($cartProduct->quantity * $cartProduct->size->sale_price);
            }

            if ($cartProduct->cut_id != null && $product->productCuts()->find($cartProduct->cut_id) != null) {
                $addonsAmount = $addonsAmount + ($cartProduct->quantity * $cartProduct->cut->price);
            }

            if ($cartProduct->is_shalwata == 1 && $product->is_shalwata == 1) {
                $addonsAmount = $addonsAmount + ($cartProduct->quantity * $shalwata->price);
            }

            $totalAddonsAmount = $totalAddonsAmount + $addonsAmount;
            $totalItemsAmount = $totalItemsAmount + $itemsAmount;

            array_push($orderProducts, [
                'total_price' => $itemsAmount + $addonsAmount,
                'quantity' => $cartProduct->quantity,
                'product_id' => $product->id,
                'preparation_id' => $cartProduct->preparation_id,
                'size_id' => $cartProduct->size_id,
                'cut_id' => $cartProduct->cut_id,
                'is_kwar3' => $cartProduct->is_kwar3,
                'is_Ras' => $cartProduct->is_Ras,
                'is_lyh' => $cartProduct->is_lyh,
                'is_karashah' => $cartProduct->is_karashah,
                'shalwata_id' => $cartProduct->is_shalwata == 1 && $product->is_shalwata == 1 ? $shalwata->id : null
            ]);


        }
        return array($cart, $discountCode, $totalAddonsAmount, $totalItemsAmount, $orderProducts);
    }

    public function getTotalProductsAmount($cart, $shalwata): array
    {
        $totalAddonsAmount = 0.0;
        $totalItemsAmount = 0.0;
        foreach ($cart as $cartProduct) {
            $product = $cartProduct->product;

            if ($cartProduct->size_id != null && $product->productSizes()->find($cartProduct->size_id) != null) {
                $totalItemsAmount = $totalItemsAmount + ($cartProduct->quantity * $cartProduct->size->sale_price);
            }

            if ($cartProduct->preparation_id != null && $product->productPreparations()->find($cartProduct->preparation_id) != null) {
                $totalAddonsAmount = $totalAddonsAmount + ($cartProduct->quantity * $cartProduct->preparation->price);
            }

            if ($cartProduct->cut_id != null && $product->productCuts()->find($cartProduct->cut_id) != null) {
                $totalAddonsAmount = $totalAddonsAmount + ($cartProduct->quantity * $cartProduct->cut->price);
            }

            if ($cartProduct->is_shalwata == 1 && $product->is_shalwata == 1) {
                $totalAddonsAmount = $totalAddonsAmount + ($cartProduct->quantity * $shalwata->price);
            }
        }

        return array($totalItemsAmount,$totalAddonsAmount);
    }



}
