<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Country;
use App\Models\Customer;
use App\Models\WalletLog;
use App\Services\UploadService;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function __construct()
    {
        if (env('PERMISSIONS', false)) {
            $this->middleware('permission:read-customer', ['only' => ['index', 'show']]);
            $this->middleware('permission:create-customer', ['only' => ['store']]);
            $this->middleware('permission:update-customer', ['only' => ['update']]);
            $this->middleware('permission:delete-customer', ['only' => ['delete']]);
        }
    }

    public function getAll()
    {
        $customers = Customer::select('id', 'name')->get();
        return successResponse($customers);
    }

    public function index(Request $request)
    {

        $perPage = 15;
        if ($request->has('per_page'))
            $perPage = $request->get('per_page');

        if ($perPage == 0)
            $perPage = 6;

        $customers = Customer::with('addresses')->latest();

        if (request('search')) {
            $customers = $customers->where(function ($q) {
                $q->where('name', 'like', '%' . request('search') . '%')
                    ->orWhere('mobile', 'like', '%' . request('search') . '%');
            });
        }

        if (request('wallet') == 'all') {
            $customers = $customers->latest();
        } elseif (request('wallet') == true) {
            $customers = $customers->where('wallet', '>', 0)->latest();
        } elseif (request('wallet') == false) {
            $customers = $customers->where('wallet', '<=', 0)->latest();
        } else {
            $customers = $customers->latest();
        }

        if (request('search')) {
            $customers = $customers->where(function ($q) {
                $q->where('name', 'like', '%' . request('search') . '%')
                    ->orWhere('mobile', 'like', '%' . request('search') . '%');
            });
        }


        if (request('country_id') == 1) {
            $customers = $customers->where('mobile_country_code', '+966');
        } elseif (request('country_id') == 4) {
            $customers = $customers->where('mobile_country_code','!=', '+966');
        }

        $customers = request('per_page') == -1 ? $customers->get() : $customers->paginate($perPage);


        return successResponse($customers);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'nullable|unique:customers,email',
            'mobile' => 'required|unique:customers,mobile',
            'foodics_integrate_id' => 'nullable',
        ]);

        $data = $request->except('avatar');

        if ($request->avatar) {
            $avatar = UploadService::store($request->avatar, 'profile');
            $data['avatar'] = $avatar;
            $data['avatar_thumb'] = $avatar;
        }

        if ($request->is_active) {
            $data['is_active'] = $request->is_active == 'true' ? 1 : 0;
        }

        $customer = Customer::create($data);

        return successResponse($customer ?? null);
    }

    public function update(Request $request, Customer $customer)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'nullable', //|unique:customers,email,' . $customer->id,
            'mobile' => 'required|unique:customers,mobile,' . $customer->id,
            'foodics_integrate_id' => 'nullable',
            'wallet' => 'nullable|numeric|min:1'
        ]);

        $data = $request->except('avatar');

        if ($request->avatar) {
            $avatar = UploadService::store($request->avatar, 'profile');
            $data['avatar'] = $avatar;
            $data['avatar_thumb'] = $avatar;
        }

        if ($request->is_active) {
            $data['is_active'] = $request->is_active == 'true' ? 1 : 0;
        }

        if ($request->wallet) {

            WalletLog::create([
                'user_id' => auth()->user()->id,
                'customer_id' => $customer->id,
                'last_amount' => $customer->wallet,
                'new_amount' => (float)$request->wallet + $customer->wallet,
                'action' => 'induction',
                'message_ar' => 'تعويض',
                'message_en' => 'Compensation',
            ]);

            $data['wallet'] = $customer->wallet + (float)$request->wallet;
        }

        $customer->update($data);

        return successResponse($customer ?? null);
    }


    public function show(Customer $customer)
    {
        return successResponse($customer ?? null);
    }

    public function delete(Customer $customer)
    {
        $customer->delete();

        return successResponse($customer ?? null);
    }

    public function getAddress($id)
    {
        $address = Address::where('customer_id', $id)->get();

        return response()->json([
            'success' => true,
            'data' => $address,
            'message' => 'Address retrieved successfully',
            'description' => 'list Of Products',
            'code' => '200'
        ], 200);
    }

    public function addAddress(Request $request)
    {
        $validateData = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'country_id' => 'required|exists:countries,id',
            'city_id' => 'required|exists:cities,id',
            'address' => 'required|min:1|max:255',
            'comment' => 'nullable|max:255',
            'label' => 'required|max:100',
            'is_default' => 'required|boolean',
            'long' => 'nullable|numeric',
            'lat' =>  'nullable|numeric',

        ]);

        $address = Address::create($validateData);

        return successResponse($address);
    }
}
