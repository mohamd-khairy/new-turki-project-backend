<?php

namespace App\Services;


use App\Models\FoodicsIntegration;
use App\Models\TraceError;
use Illuminate\Http\Request;
use App\Services\CallOrderPosNetsuiteApi;

class FoodicsApiService
{


    public function sendOrderFoodicsToNS()
    {
        $SAND_BASE_URL = "api-sandbox.foodics.com/v5/orders";
        $BASE_URL = "";
        $Token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6ImEzOTQ0ZGM3YWExYzAxMTMxZTFhYTRkYTI5MDMwYzQwZWQxMjA1OGYzYTY2MzM5OWY0YTAyNTVhY2EzNTAyMTYxNTFhYjJmMTk0OGM0MWU3In0.eyJhdWQiOiI4ZjllYjNmNi02ZWZhLTRmZWYtODk1ZS1kMWJjNDRiYTQ4MWQiLCJqdGkiOiJhMzk0NGRjN2FhMWMwMTEzMWUxYWE0ZGEyOTAzMGM0MGVkMTIwNThmM2E2NjMzOTlmNGEwMjU1YWNhMzUwMjE2MTUxYWIyZjE5NDhjNDFlNyIsImlhdCI6MTY1Mjc5NTE5NCwibmJmIjoxNjUyNzk1MTk0LCJleHAiOjE4MTA1NjE1OTQsInN1YiI6Ijk2NTIyMjg5LTI3NGItNGI4Ni1iYjkxLTNjYjRmNzY0OTc5NSIsInNjb3BlcyI6WyJnZW5lcmFsLnJlYWQiLCJvcmRlcnMubGltaXRlZC5jcmVhdGUiLCJvcmRlcnMubGltaXRlZC5kZWNsaW5lIiwib3JkZXJzLmxpbWl0ZWQucmVhZCIsIm9yZGVycy5saW1pdGVkLnBheSJdLCJidXNpbmVzcyI6Ijk2NTIyMjg5LTNjYzktNDI2OC05NDJjLTRhYzE1MzRiNTYxZCIsInJlZmVyZW5jZSI6IjYxOTU5MyJ9.JmCkqYIea1MNghVAlIQ9vc5qro4xMewuJjmq0nqn4Tfqc4MOoloOxq1HNnnK1bYHGUp563MwY-XeGMlfcCn3S4mD7ZR7edrySFPclx6j2GcMEpP-TBkUYh_uHk2YpNFivnGr-69C2VyL4vBZhd9bexWFMJnY6qp5UzRoFFx3EQSr3ve11WtseQ4KjsQURd1gYVZNxHmHd7lhgJ3_A9ynIJYapv95eJu1JraIqdPpSMOWuitfFz2NXdavz2IvMJM0sjn1Lplfyi7cAFkzCn4SpRt13VPAik5M8t6d5Qk--KdVRZzlFOLL1CzsmDepOtEFNXjRUql_AtbD02Fv0v1s5-gVWnshx1n9NyB-G3Rv_edO1wBN8inTepppdOqn8_gVUadTLCCG52kUpwumf54fz7XQLlcOH5psHkeB8Tq4I2CmMoLLR6_xdGT2neRkB6DxgFI3kcgyP_i4kjMAEtlAdyy7_Vp2TVjS6HAJXjwUr05kZzAT4nz93faClRQJ2UjP-TCRI8Wmq84ofx1EhLPqDN35t2uy7Vf4GUNy52iDPx0UQDoRj0pL52hOvCr0hl70a532imOmZK1GUszg_GPrcCzvA5DSAUAAfY29owV99uHuZPek5ONBHduz8EOvaDtnRMvNe0PprsT8IR_h3n1Ukx9gGAlhv5NNRdurQw2FoAY';
        $details = [
            "type" => 1,
            "branch_id" => "965222b8",
            "products" => [
                  [
                     "product_id" => "9652358c",
                     "quantity" => 1,
                     "unit_price" => 1000
                  ]
               ]
         ];
    $curl = curl_init();
    curl_setopt_array($curl, array(
    CURLOPT_URL => $SAND_BASE_URL,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => json_encode($details),
    CURLOPT_HTTPHEADER => array(
    'Accept: application/json',
    'Content-Type: application/json',
        "Authorization: Bearer ".$Token
    ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    return json_decode($response);

    }

     public function webhookFoodics(Request $request)
    {

         TraceError::create(['class_name' => "FoodicsApiService", 'method_name' => $request->query('webhookFoodics'), 'error_desc' => json_encode($request->all())]);

        if ($request->ip() != "63.33.178.208" && $request->ip() != "54.229.95.46")
            return response()->json(['data' => [],
                'success' => false, 'message' => 'unauthorized!', 'description' => '', 'code' => '401'], 401);
        
         $data = $request->post();
         
        

        if(isset($data['order']['id'])){
            $alreadyExists = FoodicsIntegration::where("foodics_id", $data['order']['id'])->first();
            if($alreadyExists == null){
                $foodics = FoodicsIntegration::create([
                    "foodics_id" => $data['order']['id'],
                    "total_price"=> $data['order']['total_price'],
                    "customer_notes"=> $data['order']['customer_notes'],
                    "discount_amount"=> $data['order']['discount_amount'],
                    "branch_id"=> $data['order']['branch']['id'],
                    "branch_name"=> $data['order']['branch']['name'],
                    "full_response" => json_encode($request->all())
                ]);

                //now we need to send this to netsuite
                app(CallOrderPosNetsuiteApi::class)->sendOrderPosToNS($data);

            }
        
        }
        
        
         return response()->json(['data' => [],
                'success' => true, 'message' => 'success', 'description' => '', 'code' => '200'], 200);
    }


}
