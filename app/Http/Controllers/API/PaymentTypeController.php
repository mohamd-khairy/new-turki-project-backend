<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PaymentType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\TamaraApiService;

class PaymentTypeController extends Controller
{
    /**
     * @param Request $request
     * @return PaymentType[]|Collection
     */
    public function getAll()
    {
        $paymentType = PaymentType::all();
        return response()->json(['success' => true ,'data'=> $paymentType,
        'message'=> 'Payment Types retrieved successfully', 'description'=> 'list Of Payment Types', 'code'=> 200 ],200);
    }

    public function getById(PaymentType $paymentType)
    {
        return response()->json(['success' => true ,'data'=> $paymentType,
        'message'=> 'Payment Type retrieved successfully', 'description'=> 'Payment Types Details', 'code'=> 200 ],200);
    }

      public function getPaymentTypesTamara(Request $request)
    {
        $countryIsoCode = $request->query('countryIsoCode');
        $currencyCode = $request->query('currencyCode');
        $orderValue = $request->query('orderValue');

        $paymentResTamara = app(TamaraApiService::class)->getPaymentTypes($countryIsoCode, $currencyCode, $orderValue);

        return response()->json(['success' => true ,'data'=> $paymentResTamara,
        'message'=> 'Payment Type retrieved successfully', 'description'=> 'Payment Types Details', 'code'=> 200 ],200);
    }

    public function getActivePaymentTypes()
    {
        return response()->json(['data' => PaymentType::where('is_active', '1')->get(), 'message' => "success", 'description' => "", 'code' => "200"], 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function add(Request $request)
    {
        $validateDate = $request->validate([
            'code' => 'required|max:50',
            'name_ar' => 'required|max:150',
            'name_en' => 'required|max:150',
            'is_active' => 'required'
        ]);

        $validateDate['active'] = $request->is_active;
        $paymentType = PaymentType::create($validateDate);
        if ($paymentType) {
            return response()->json(['success' => true ,'data'=> $paymentType,
                'message'=> 'Successfully Added!', 'description'=> 'Add Payment Type', 'code'=> 200 ],200);
        }

        return response()->json(['message' => 'Something went wrong!'], 500);

    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function delete(PaymentType $paymentType)
    {
        if ($paymentType->delete()) {

            return response()->json(['message' => 'Successfully Deleted!'], 200);
        }

        return response()->json(['message' => 'Something went wrong!'], 500);

    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function updateStatus(PaymentType  $paymentType)
    {
            $paymentType->is_active = !$paymentType->is_active;
            if ($paymentType->update()) {
                return response()->json(['message' => 'Successfully updated!', 'data' => $paymentType], 200);
            }
        return response()->json(['message' => 'Something went wrong!'], 500);

    }

    public function update(Request $request, PaymentType $paymentType)
    {

        $validateDate = $request->validate([
            'name_ar' => 'required|max:150',
            'name_en' => 'required|max:150',
            'code' => 'required|max:50',
            'is_active' => 'required'
        ]);

        $validateDate['active'] = $request->is_active;
        if ($paymentType->update($validateDate)) {

            return response()->json(['success' => true ,'data'=> $paymentType,
            'message'=> 'Successfully updated!', 'description'=> 'Update Payment Types', 'code'=> 200 ],200);

        }
        return response()->json(['message' => 'Something went wrong!'], 500);
    }

}
