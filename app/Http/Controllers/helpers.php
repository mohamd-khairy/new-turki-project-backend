<?php

use App\Models\OrderState;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;


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

            if ($b >= 48 && $b <= 57)
                $b -= 48;
            else if ($b >= 65 && $b <= 70)
                $b -= 55;

            $dec += $b * pow(16, (($hexLength - $i) - 1));
        }

        return (int)$dec;
    }
}



if (!function_exists('toHex')) {
    function toHex($number)
    {
        $hexvalues = array(
            '0', '1', '2', '3', '4', '5', '6', '7',
            '8', '9', 'A', 'B', 'C', 'D', 'E', 'F'
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
                'status' =>  ['100', '101', '102', '103', '104', '105', '106', '107', '108', '109', '200'],
                'orders' =>    ['100', '101', '102', '103', '104', '105', '106', '107', '108', '109', '200']
            ];
        }

        if (in_array('production_manager', $roles)) { // 'production_manager' => 'مسئول الانتاج',/////////////////
            $data = [
                'status' => ['104', '105', '106'],
                'orders' => ['101', '104', '105', '106', '200']
            ];
        }

        if (in_array('production_supervisor', $roles)) { // 'production_manager' => 'مشرف الانتاج',/////////////////
            $data = [
                'status' => ['104', '105', '106'],
                'orders' => ['101', '104', '105', '106', '200']
            ];
        }

        if (in_array('logistic_manager', $roles)) { // 'logistic_manager' => 'مسئول لوجيستي',///////////////////
            $data = [
                'status' =>  ['103', '106', '200'],
                'orders' =>   ['101', '104', '105', '106', '109', '200']
            ];
        }
        if (in_array('store_manager', $roles)) { // 'store_manager' => 'مسئول المبيعات', //////////////
            $data = [
                'status' =>  ['101', '102', '103', '106', '200'],
                'orders' =>  ['100', '101', '102', '103', '104', '105', '106', '200']
            ];
        }
        if (in_array('general_manager', $roles)) { // 'general_manager' => 'مشرف المبيعات',/////////////////
            $data = [
                'status' =>  ['101', '102', '103', '106', '200'],
                'orders' =>   ['100', '101', '102', '103', '104', '105', '106', '107', '108', '109', '200']
            ];
        }
        if (in_array('delegate', $roles)) { // 'delegate' => 'مندوب',///////////////////
            $data = [
                'status' => ['103', '109', '200'],
                'orders' => ['106', '109']
            ];
        }

        if ($data) {
            if (Schema::hasColumn('order_states', 'new_code')) {
                $data = [
                    'status' => OrderState::whereIn('new_code', $data['status'])->pluck('code')->toArray(),
                    'orders' => OrderState::whereIn('new_code', $data['orders'])->pluck('code')->toArray()
                ];
            }

            return $data;
        } else {
            return [];
        }
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
            'code' => '200'
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
            'code' => $code
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
        if ($request->has('per_page'))
            $perPage = $request->get('per_page');

        if ($perPage == 0)
            $perPage = 6;
        return $perPage;
    }
}

if (!function_exists('GetNextOrderRefNo')) {
    /**
     * @return string
     */
    function GetNextOrderRefNo($countryCode, $id)
    {
        $genId = str_pad($id, 9, "0", STR_PAD_LEFT);
        return $countryCode . 'O' . $genId;
    }
}

if (!function_exists('GetNextPaymentRefNo')) {
    /**
     * @return string
     */
    function GetNextPaymentRefNo($countryCode, $id)
    {
        $genId = str_pad($id, 9, "0", STR_PAD_LEFT);
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
        return (string)Str::orderedUuid();
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
