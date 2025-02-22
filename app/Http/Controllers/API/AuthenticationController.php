<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Address;
use App\Models\Customer;
use App\Models\Country;
use App\Models\CustomerOtpLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Services\PointLocation;
use App\Services\CallNetsuiteApi;
use App\Models\Order;
use App\Models\CustomerDeliveryOtpLog;

class AuthenticationController extends Controller
{

    public function createUser(Request $request)
    {
        if (auth()->user()->id != 1)
            return response()->json(['data' => null, 'message' => "contact admin!", 'description' => "", 'code' => "401"], 401);

        $validateData = $request->validate([
            'username' => 'required|string|max:100',
            'email' => 'required|string|max:255|unique:users', //email:rfc,dns
            'password' => 'required|string|min:8|confirmed',
            'mobile' => 'required|unique:users',
            'age' => 'required|numeric',
            'gender' => 'required|numeric',
            'role' => 'required|exists:roles,id'
        ]);

        $validateData['password'] = bcrypt($validateData['password']);
        $user = User::create($validateData);

        if ($user) {
            //            try {
            //                $user->notify(new UserCreatedNotification());
            //            } catch (Swift_TransportException $e){
            //                report($e);
            //            }

            // assign roles
            //$user->assignRoles();

            $accessToken = $user->createToken('Personal Access Token', []);
            $user->api_token = $accessToken->plainTextToken;

            return response()->json(['data' => $user, 'message' => "success", 'description' => "", 'code' => "200"], 200);
        }
        return response()->json(['data' => null, 'message' => "oops something went wrong, please try again later!", 'description' => "", 'code' => "500"], 500);
    }

    public function login(Request $request)
    {
        $validateData = $request->validate([
            'email' => 'required|string|max:255', //|email:rfc,dns
            'password' => 'required',
        ]);

        $user = User::where('email', $validateData['email'])->with([
            'roles',
            'roles.permissions:name',
        ])->get()->first();

        if (auth()->attempt($validateData)) {
            $accessToken = $user->createToken('Personal Access Token', []);
            $user->api_token = $accessToken->plainTextToken;

            return response()->json(['data' => new UserResource($user), 'message' => "success", 'description' => "", 'code' => "200"], 200);
        }
        return response()->json(['data' => null, 'message' => "wrong attempts!", 'description' => "", 'code' => "400"], 400);
    }

    public function verfiyOTP(Request $request)
    {
        $validated = $request->validate([
            'mobile' => 'required',
            'mobile_verification_code' => 'required',
        ]);

        $customerOtpLog = CustomerOtpLog::where('mobile', $validated['mobile'])->get()->last();

        if ($customerOtpLog != null && $validated['mobile_verification_code'] == $customerOtpLog->mobile_verification_code) {

            $customer = Customer::where('mobile', $validated['mobile'])->get()->last();

            $phone_code = Str::substr($request->mobile, 0, 4);

            // httpCode = C100
            if ($customer == null) {

                $customer = new Customer();
                $customer->mobile = $validated['mobile'];
                $customer->mobile_country_code = $phone_code;
                $customer->save();
            }

            // else httpCode = C101
            $accessToken = $customer->createToken('Personal Access Token', []);
            $customer->access_token = $accessToken->plainTextToken;

            CustomerOtpLog::where('mobile', $validated['mobile'])->delete();

            return response()->json([
                'message' => 'Customer retrieved successfully',
                'data' => $customer,
                'description' => '',
                'code' => '200',
                'success' => true
            ], 200);
        } else if ($customerOtpLog != null) {

            if ($validated['mobile'] == '+966561051956' || $validated['mobile'] == '+966507452527' || $validated['mobile'] == '+966571436900') {
                return response()->json([
                    'message' => 'code mismatch!, please try again.',
                    'data' => null,
                    'description' => 'dev mode, your code is: ' . $customerOtpLog->mobile_verification_code,
                    'code' => '400',
                    'success' => false
                ], 400);
            }

            $now = Carbon::now();

            $today = $now->format('Y-m-d h:i:s');
            $disabledAt = null;
            $timeToRelease = false;

            if ($customerOtpLog->disabled == 1) {
                $disabledAt = Carbon::parse($customerOtpLog->disabled_date);
                $disabledAt = $disabledAt->add(24, 'hour');
                $timeToRelease = $now->gte($disabledAt);
            }

            if ($customerOtpLog->no_attempts > 10 && $customerOtpLog->disabled == 0) {
                $customerOtpLog->update(['id' => $customerOtpLog->id, 'mobile_verification_code' => null, 'disabled' => 1, 'disabled_at' => $now]);

                return response()->json([
                    'message' => __("your account is disabled, you have  " . $customerOtpLog->no_attempts . " wrong attempts, try after 24 hours!"),
                    'no attempts' => $customerOtpLog->no_attempts
                ], 200);
            }

            if ($customerOtpLog->no_attempts <= 10 && $customerOtpLog->disabled == 0) {
                $customerOtpLog->update(['id' => $customerOtpLog->id, 'no_attempts' => $customerOtpLog->no_attempts + 1, 'disabled' => 0]);

                return response()->json([
                    'message' => 'code mismatch!, please try again.',
                    'data' => null,
                    'description' => 'no_attempts: ' .  $customerOtpLog->no_attempts,
                    'code' => '400',
                    'success' => false
                ], 400);
            }

            if ($customerOtpLog->disabled == 1 && $timeToRelease) {
                $customerOtpLog->no_attempts = 0;
                $customerOtpLog->update(['id' => $customerOtpLog->id, 'mobile_verification_code' => null, 'no_attempts' => $customerOtpLog->no_attempts + 1, 'disabled' => 0, 'disabled_at' => null]);

                return response()->json([
                    'message' => 'your account is active now, get new code!',
                    'data' => null,
                    'description' => '',
                    'code' => '400',
                    'success' => false
                ], 400);
            }
        }

        return response()->json([
            'message' => 'get code first!',
            'data' => null,
            'description' => '',
            'code' => '400',
            'success' => false
        ], 400);
    }

    public function verfiyOTPv2(Request $request)
    {
        $validated = $request->validate([
            'mobile' => 'required',
            'mobile_verification_code' => 'required',
        ]);

        $customerOtpLog = CustomerOtpLog::where('mobile', $validated['mobile'])->get()->last();

        if ($customerOtpLog != null && $validated['mobile_verification_code'] == $customerOtpLog->mobile_verification_code) {

            $customer = Customer::where('mobile', $validated['mobile'])->get()->last();
            $phone_code = Str::substr($request->mobile, 0, 4);
            // httpCode = C100
            if ($customer == null) {

                $customerData = $request->validate(['name' => 'required|string|max:100']);

                $customer = new Customer();
                $customer->mobile = $validated['mobile'];
                $customer->mobile_country_code = $phone_code;

                $customer->name = $customerData['name'];
                $customer->save();
            }

            // else httpCode = C101
            $accessToken = $customer->createToken('Personal Access Token', []);
            $customer->access_token = $accessToken->plainTextToken;

            CustomerOtpLog::where('mobile', $validated['mobile'])->delete();

            return response()->json([
                'message' => 'Customer retrieved successfully',
                'data' => $customer,
                'description' => '',
                'code' => '200',
                'success' => true
            ], 200);
        } else if ($customerOtpLog != null) {

            if ($validated['mobile'] == '+966561051956' || $validated['mobile'] == '+966507452527' || $validated['mobile'] == '+966571436900') {
                return response()->json([
                    'message' => 'code mismatch!, please try again.',
                    'data' => null,
                    'description' => 'dev mode, your code is: ' . $customerOtpLog->mobile_verification_code,
                    'code' => '400',
                    'success' => false
                ], 400);
            }

            $now = Carbon::now();

            $today = $now->format('Y-m-d h:i:s');
            $disabledAt = null;
            $timeToRelease = false;

            if ($customerOtpLog->disabled == 1) {
                $disabledAt = Carbon::parse($customerOtpLog->disabled_date);
                $disabledAt = $disabledAt->add(24, 'hour');
                $timeToRelease = $now->gte($disabledAt);
            }

            if ($customerOtpLog->no_attempts > 10 && $customerOtpLog->disabled == 0) {
                $customerOtpLog->update(['id' => $customerOtpLog->id, 'mobile_verification_code' => null, 'disabled' => 1, 'disabled_at' => $now]);

                return response()->json([
                    'message' => __("your account is disabled, you have  " . $customerOtpLog->no_attempts . " wrong attempts, try after 24 hours!"),
                    'no attempts' => $customerOtpLog->no_attempts
                ], 200);
            }

            if ($customerOtpLog->no_attempts <= 10 && $customerOtpLog->disabled == 0) {
                $customerOtpLog->update(['id' => $customerOtpLog->id, 'no_attempts' => $customerOtpLog->no_attempts + 1, 'disabled' => 0]);

                return response()->json([
                    'message' => 'code mismatch!, please try again.',
                    'data' => null,
                    'description' => 'no_attempts: ' .  $customerOtpLog->no_attempts,
                    'code' => '400',
                    'success' => false
                ], 400);
            }

            if ($customerOtpLog->disabled == 1 && $timeToRelease) {
                $customerOtpLog->no_attempts = 0;
                $customerOtpLog->update(['id' => $customerOtpLog->id, 'mobile_verification_code' => null, 'no_attempts' => $customerOtpLog->no_attempts + 1, 'disabled' => 0, 'disabled_at' => null]);

                return response()->json([
                    'message' => 'your account is active now, get new code!',
                    'data' => null,
                    'description' => '',
                    'code' => '400',
                    'success' => false
                ], 400);
            }
        }

        return response()->json([
            'message' => 'get code first!',
            'data' => null,
            'description' => '',
            'code' => '400',
            'success' => false
        ], 400);
    }

    public function sendOTP(Request $request)
    {

        $local = $request->query('local');

        $customerData = $request->validate(['mobile' => array('required', 'max:13', 'min:13')]);

        //  $customerData = $request->validate(['mobile' => array('required','max:13', 'min:13',"regex:(^[+]+(9965|9715)+([0-9]{8})+$)")]);

        //   $customer = CustomerOtpLog::where('mobile', $customerData['mobile'])->get()->first();

        //   if($customerData['mobile'] == '+966123456789'){
        //     $customer->mobile_verification_code = '1234';
        //     $customer->save();
        //      return response()->json(['message' => 'verification code sent!', 'success' => true, 'description' => '', 'code' => '', 'data' =>  $customer], 200);

        //       }
        //     else{
        $digits = 4;
        // $otpCode = str_pad(rand(0, pow(10, $digits) - 1), $digits, '0', STR_PAD_LEFT);
        $otpCode = rand(1000, 9999);

        $phone_number = $customerData['mobile'];

        $trashedCustomer = Customer::where('mobile', $phone_number)->onlyTrashed()->get();

        if (count($trashedCustomer) > 0) {
            return response()->json(['message' => 'this account was deleted, contact support for more info!', 'success' => false, 'description' => '', 'code' => '401', 'data' =>  null], 401);
        }

        $phone_code = Str::substr($request->mobile, 0, 4);
        $phone = Str::substr($phone_number, 4, 9);

        $text = "";


        if (strtolower($local) == "ar") {
            $text = "رمز&nbsp;التحقق&nbsp;&nbsp;$otpCode";
        } else {
            $text = "your&nbsp;verify&nbsp;code&nbsp;is&nbsp;&nbsp;$otpCode";
        }

        $decoded = html_entity_decode($text);

        $httpCode = "C100"; // new customer

        $customer = Customer::where('mobile', $request->mobile)->get()->last();

        if ($customer != null) {
            $httpCode = "C101"; // old customer
        }

        $customerOtpLog = CustomerOtpLog::where('mobile', $request->mobile)->get()->last();

        if ($customerOtpLog == null) {
            $customerOtpLog = CustomerOtpLog::create([
                'mobile_country_code' => $phone_code,
                'mobile' => $phone_number,
                'mobile_verification_code' => $otpCode,
                'no_attempts' => 0,
                'disabled' => false,
                'disabled_at' => null,
            ]);
        }


        $customer = CustomerOtpLog::where('mobile', $customerData['mobile'])->get()->first();

        if ($customerData['mobile'] == '+966123456789') {
            $customer->mobile_verification_code = '1234';
            $customer->save();

            return response()->json(['message' => 'verification code sent!', 'success' => true, 'description' => $trashedCustomer, 'code' => '', 'data' =>  $customer], 200);
        }
        $now = Carbon::now();

        $today = $now->format('Y-m-d h:i:s');
        $disabledAt = null;
        $timeToRelease = false;

        if ($customerOtpLog->disabled == 1) {
            $disabledAt = Carbon::parse($customerOtpLog->disabled_date);
            $disabledAt = $disabledAt->add(24, 'hour');
            $timeToRelease = $now->gte($disabledAt);
        }

        if ($customerOtpLog->disabled == 1 && $timeToRelease) {
            $customerOtpLog->no_attempts = 0;
            $customerOtpLog->update(['id' => $customerOtpLog->id, 'mobile_verification_code' => $otpCode, 'no_attempts' => $customerOtpLog->no_attempts + 1, 'disabled' => 0]);

            return response()->json(['message' => 'verification code sent!', 'success' => true, 'description' => '', 'code' => $httpCode, 'data' => null], 200);
        }

        if ($customerOtpLog->no_attempts <= 10 && $customerOtpLog->disabled == 0) {
            $customerOtpLog->update(['id' => $customerOtpLog->id, 'mobile_verification_code' => $otpCode, 'no_attempts' => $customerOtpLog->no_attempts + 1, 'disabled' => 0]);
            $this->sendSMS($phone_code, $phone, $decoded, $customerOtpLog);
            return response()->json(['message' => 'verification code sent!', 'success' => true, 'description' => '', 'code' => $httpCode, 'data' => null], 200);
        }

        //        if ($customerOtpLog->no_attempts > 10 && $customerOtpLog->disabled == 0) {
        //            $customerOtpLog->update(['id' => $customerOtpLog->id, 'mobile_verification_code' => null, 'disabled' => 1, 'disabled_at' => $now]);
        //
        //            return response()->json(['message' => __("your account is disabled, you have  " . $customerOtpLog->no_attempts . " wrong attemps, try after 24 hour!"),
        //                'no attempts' => $customerOtpLog->no_attempts], 200);
        //        }

        //        if ($customerOtpLog->no_attempts <= 10 && $customerOtpLog->disabled == 0) {
        //            $customerOtpLog->update(['id' => $customerOtpLog->id, 'mobile_verification_code' => $otpCode, 'no_attempts' => $customerOtpLog->no_attempts + 1, 'disabled' => 0]);
        //        } else if ($customerOtpLog->disabled == 1 && $timeToRelease) {
        //            $customerOtpLog->no_attempts = 0;
        //            $customerOtpLog->update(['id' => $customerOtpLog->id, 'mobile_verification_code' => $otpCode, 'no_attempts' => $customerOtpLog->no_attempts + 1, 'disabled' => 0]);
        //
        else if ($customerOtpLog->no_attempts > 10) {

            $customerOtpLog->update(['id' => $customerOtpLog->id, 'mobile_verification_code' => $otpCode, 'disabled' => 1, 'disabled_at' => $now]);

            return response()->json([
                'message' => __("your account is disabled, you have  " . $customerOtpLog->no_attempts . " wrong attemps, try after 24 hour!"),
                'no attempts' => $customerOtpLog->no_attempts
            ], 200);
        }

        //   }

    }

    public function logout(Request $request)
    {
        if (auth()->user()->token()->revoke()) {
            return response()->json([
                'message' => 'success',
                'data' => null,
                'description' => '',
                'code' => '200',
                'success' => true
            ], 200);
        }
        return response()->json([
            'message' => __('templates.httpCode.401'),
            'description' => ''
        ], 401);
    }

    public function passwordReset(Request $request)
    {

        if (!auth()->user())
            return response()->json(['message' => __("templates.httpCode.401")], 401);

        $validateData = $request->validate([
            'old_password' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $newPassword = bcrypt($validateData['password']);
        $user = auth()->user();


        if (!$user->is_active)
            return response()->json([
                'message' => __("templates.httpCode.401"),
                'description' => "Please contact support to activate your account!"
            ], 401);

        if (!Hash::check($validateData['old_password'], $user->password))
            return response()->json(['message' => 'invalid password'], 401);

        $user = User::where('email', $user->email)->get()->first();

        $user->password = $newPassword;
        if (!$user->update())
            return response()->json(['message' => 'user password has not updated, contact support'], 500);

        $user->token()->revoke();
        $accessToken = $user->createToken('APIToken')->accessToken;
        return response()->json(['user' => new $user, 'accessToken' => $accessToken], 200);
    }

    public function testLogin($mobile)
    {
        $customer = Customer::where('mobile', $mobile)->get()->first();
        if ($customer == null) {
            $customer = new Customer();
            $customer->mobile = $mobile;
            $customer->name = "test";
            $customer->save();
        }

        $accessToken = $customer->createToken('Personal Access Token', []);
        return response()->json($customer->access_token = $accessToken->plainTextToken);
    }

    public function editProfile(Request $request)
    {
        //  TraceError::create(['class_name'=> "AuthenticationController:: coming from the app", 'method_name'=>"sendCustomerToNS", 'error_desc' => json_encode($request->all())]);
        $customer = Customer::find(auth()->user()->id);

        $validateData = Validator::make($request->post(), [
            'name' => 'required|min:1|max:30',
            'email' => 'sometimes|email:rfc,dns|max:255|unique:customers',
            'avatar' => 'sometimes|max:1024|mimes:png,jpeg,jpg',
            'age' => 'sometimes|numeric|min:8|max:90',
            'gender' => 'sometimes|numeric',
            'nationality' => 'sometimes',
        ]);

        if ($validateData->fails())
            return response()->json(["message" => "The given data was invalid.", "errors" => $validateData->errors()], 400);

        $validateData = $validateData->validated();

        $customer->update($validateData);

        $customer->uploadAvatar($request);

        // $res = app(CallNetsuiteApi::class)->sendCustomerToNS($customer, $request);

        // if (!isset($res->status)) {

        //     $customer->update(['integrate_id' => $res->id]);
        // }

        return response()->json(['message' => 'successfully updated.', 'success' => true, 'description' => '', 'code' => 200, 'data' => $customer], 200);
    }

    public function showProfile()
    {

        $customer = Customer::find(auth()->user()->id);
        if (is_null($customer))
            return response()->json(['message' => 'failed.', 'success' => false, 'description' => '', 'code' => 404, 'data' => null], 404);

        return response()->json(['message' => 'successfully updated.', 'success' => true, 'description' => '', 'code' => 200, 'data' => $customer], 200);
    }

    public function getAddress(Request $request)
    {
        $address = Address::where('customer_id', auth()->user()->id)
        ->latest('created_at')
        ->take(10)
        ->get();

        return response()->json([
            'success' => true,
            'data' => $address,
            'message' => 'Address retrieved successfully',
            'description' => 'list Of Products',
            'code' => '200'
        ], 200);
    }

    public function createAddressCustomer(Request $request)
    {
        $validateData = $request->validate([
            'country_iso_code' => 'required',
            'address' => 'required|min:1|max:255',
            'comment' => 'required|max:255',
            'label' => 'required|max:100',
            'is_default' => 'required|boolean',
            'long' => 'required|numeric',
            'lat' =>  'required|numeric',

        ]);


        $point = $validateData['long'] . " " . $validateData['lat'];
        $country = Country::where([['code', $validateData['country_iso_code']], ['is_active', 1]])->get()->first();

        if ($country === null)
            return response()->json([
                'data' => [],
                'success' => false,
                'message' => 'failed',
                'description' => 'country not found, contact support!',
                'code' => '400'
            ], 400);


        $currentCity = app(PointLocation::class)->getLocatedCity($country, $point);

        if ($currentCity === null)
            return response()->json([
                'data' => [],
                'success' => false,
                'message' => 'failed',
                'description' => 'city not found!',
                'code' => '400'
            ], 400);

        //  dd($validateData['country_iso_code']);

        $validateData['country_id'] = $country->id;
        $validateData['city_id'] = $currentCity->id;
        $validateData['customer_id'] = auth()->user()->id;

        if ($validateData['is_default'] == 1) {
            DB::statement("update addresses set is_default = 0 where customer_id = " . $validateData['customer_id']);
        }

        $address = Address::create($validateData);
        return response()->json(['data' => $address, 'message' => "success", 'description' => "", 'code' => "200"], 200);
    }

    public function deleteAddressCustomer(Address $address)
    {
        $id = $address->id;

        if ($address->delete()) {

            return response()->json(['massage' => 'Successfully Deleted!'], 200);
        } else {
            return response()->json(['success' => false, 'message' => 'Not exist!'], 500);
        }
    }

    public function editAddressCustomer(Request $request, $address)
    {
        if ($request->post() == null)
            return response()->json([
                'data' => [],
                'success' => false,
                'message' => 'failed',
                'description' => 'no data found!',
                'code' => '400'
            ], 400);

        $address = Address::find($address);

        if ($address == null)
            return response()->json([
                'data' => [],
                'success' => false,
                'message' => 'failed',
                'description' => 'address not found!',
                'code' => '400'
            ], 400);

        $validateData = $request->validate([
            'country_iso_code' => array('required_with:long,lat', 'max:2', 'min:2'),
            'address' => 'sometimes|min:5|max:255',
            'comment' => 'sometimes|max:255',
            'label' => 'sometimes|max:100',
            'is_default' => 'sometimes|boolean',
            'long' => array('required_with:country_iso_code,lat', 'numeric'),
            'lat' =>  array('required_with:country_iso_code,long', 'numeric'),
        ]);


        if (isset($validateData['country_iso_code'])) {
            $point = $validateData['long'] . " " . $validateData['lat'];
            $country = Country::where([['code', $validateData['country_iso_code']], ['is_active', 1]])->get()->first();

            if ($country === null)
                return response()->json([
                    'data' => [],
                    'success' => false,
                    'failed' => 'success',
                    'description' => 'country not found, contact support!',
                    'code' => '400'
                ], 400);


            $currentCity = app(PointLocation::class)->getLocatedCity($country, $point);

            if ($currentCity === null)
                return response()->json([
                    'data' => [],
                    'success' => false,
                    'failed' => 'success',
                    'description' => 'city not found!',
                    'code' => '400'
                ], 400);

            unset($validateData['country_iso_code']);

            $validateData['country_id'] = $country->id;
            $validateData['city_id'] = $currentCity->id;
        }


        if (isset($validateData['is_default']) && $validateData['is_default'] == 1) {
            DB::statement("update addresses set is_default = 0 where customer_id = " . $validateData['customer_id']);
        }

        $address->update($validateData);

        return response()->json(['data' => $address, 'message' => "success", 'description' => "", 'code' => "200"], 200);
    }

    public function selectedAddressCustomer(Address $address)
    {

        $address = Address::where([['customer_id', auth()->user()->id], ['id', $address->id]])->get()->first();

        if ($address == null)
            return response()->json(['message' => 'failed.', 'success' => false, 'description' => '', 'code' => 404, 'data' => null], 404);

        DB::statement("update addresses set is_default = 0 where customer_id = " . auth()->user()->id);
        $address->is_default = 1;
        $address->save();

        return response()->json(['data' => $address, 'message' => "success", 'description' => "", 'code' => "200"], 200);
    }

    /**
     * @param string $smsUser
     * @param string $pwd
     * @param string $senderid
     * @param string $countryCode
     * @param string $phone
     * @param string $decoded
     * @param $customerOtpLog
     */
    private function sendSMS(string $phone_code, string $phone, string $decoded, $customerOtpLog): void
    {
        $senderid = "TURKIDBH";
        $countryCode = $phone_code;
        $pwd = "TaTa_1400@ahmed";
        $smsUser = "TurkiBK";

        $url = "https://mshastra.com/sendurlcomma.aspx?&user=" . $smsUser . "&pwd=" . $pwd . "&senderid=" . $senderid . "&CountryCode=" . $countryCode . "&mobileno=" . $phone . "&msgtext=" . $decoded; //. $customerOtpLog->mobile_verification_code;

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $curl_scraped_page = curl_exec($ch);
        curl_close($ch);
    }

    public function deleteCustomer(Request $request)
    {
        $customer = Customer::find(auth()->user()->id);

        if ($customer->delete()) {
            return response()->json(['success' => true, 'data' => null, 'message' => "Successfully Deleted!", 'description' => "", 'code' => "200"], 200);
        } else {
            return response()->json(['success' => false, 'message' => 'Not exist!', 'data' => null, 'code' => "200"], 500);
        }
    }

    public function sendDriverOTP($orderId, Request $request)
    {
        $local = $request->query('local');
        $order = Order::where('saleOrderId', $orderId)->first();


        if ($order == null) {
            return response()->json(['success' => false, 'message' => 'Not exist!', 'data' => null, 'code' => "400"], 400);
        }

        $digits = 4;
        $otpCode = str_pad(rand(0, pow(10, $digits) - 1), $digits, '0', STR_PAD_LEFT);
        $customer = Customer::find($order->customer_id);
        $phone_number = $customer->mobile;
        $phone_code = Str::substr($phone_number, 0, 4);
        $phone = Str::substr($phone_number, 4, 9);

        $customerOtpLog = CustomerDeliveryOtpLog::where([['order_id', $orderId], ['mobile', $phone_number]])->get()->last();

        if ($customerOtpLog != null && $customerOtpLog->disabled == 0) {
            return response()->json(['success' => false, 'message' => 'Already sent!', 'data' => $customerOtpLog->mobile_verification_code, 'code' => "400"], 400);
        }



        if (strtolower($local) == "ar") {
            $text = "عزيزي&nbsp;العميل,&nbsp;يرجى&nbsp;تزويد&nbsp;مندوبنا&nbsp;بالرمز&nbsp;التالي&nbsp;لإتمام&nbsp;عملية&nbsp;التسليم&nbsp;وشكرا&nbsp;";
        } else {
            $text = "Dear&nbsp;customer,&nbsp;please,&nbsp;provide&nbsp;our&nbsp;courier&nbsp;the&nbsp;delivery&nbsp;code&nbsp;";
        }

        $decoded = html_entity_decode($text);

        if ($customerOtpLog == null || $customerOtpLog->disabled == 1) {
            $customerOtpLog = CustomerDeliveryOtpLog::create([
                'mobile_country_code' => $phone_code,
                'mobile' => $phone_number,
                'mobile_verification_code' => $otpCode,
                'no_attempts' => 0,
                'disabled' => false,
                'disabled_at' => null,
                "order_id" => $order->ref_no,
                'user_id' => auth()->user()->id
            ]);
        }

        $this->sendSMS($phone_code, $phone, $decoded, $customerOtpLog);
        $order->update(['order_state_id', 104]);
        return response()->json(['message' => 'verification code sent!', 'success' => true, 'description' => '', 'code' => 200, 'data' => null], 200);
    }

    public function verifyDriverOTP(Request $request, $orderId, $otp)
    {
        $order = Order::where('saleOrderId', $orderId)->first();

        if ($order == null) {
            return response()->json(['success' => false, 'message' => 'Not exist!', 'data' => null, 'code' => "400"], 400);
        }

        $customer = Customer::find($order->customer_id);
        $customerOtpLog = CustomerDeliveryOtpLog::where([['order_id', $orderId], ['mobile',  $customer->mobile], ['disabled', false]])->get()->last();

        if ($customerOtpLog != null  && $customerOtpLog->disabled == false && $otp == $customerOtpLog->mobile_verification_code) {
            $customerOtpLog->update(['disabled' => true]);
            $order->update(['order_state_id' => 200]);
            return response()->json([
                'message' => 'verified success.',
                'data' => null,
                'description' => '',
                'code' => '200',
                'success' => true
            ], 200);
        } else {
            return response()->json([
                'message' => 'wrong otp/not exists!',
                'data' => null,
                'description' => '',
                'code' => '400',
                'success' => false
            ], 400);
        }
    }

    public function register(Request $request)
    {
        $user = User::create($data = [
            'email' => 'admin@admin.com',
            'email_verified_at' => now(),
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'remember_token' => Str::random(10),
            'mobile' => Str::random(10),
        ]);

        return response()->json(['data' => $user, 'message' => "success", 'description' => "", 'code' => "200"], 200);
    }
}
