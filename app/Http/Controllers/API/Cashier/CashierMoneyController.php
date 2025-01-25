<?php

namespace App\Http\Controllers\API\Cashier;

use App\Http\Controllers\Controller;
use App\Models\CashierMoney;
use Illuminate\Http\Request;

class CashierMoneyController extends Controller
{

    public function index()
    {
        $cashierMoney = CashierMoney::with('user.branch')->latest()->paginate(request("per_page", 10));
        return successResponse($cashierMoney);
    }

    public function store(Request $request)
    {
        $data=$request->all();
        $data['user_id'] = auth()->id();
        $cashierMoney = CashierMoney::create($data);
        return successResponse($cashierMoney);
    }

    public function show($id)
    {
        $cashierMoney = CashierMoney::find($id);
        return successResponse($cashierMoney);
    }

    public function update(Request $request, $id)
    {
        $cashierMoney = CashierMoney::find($id);
        $cashierMoney->update($request->all());

        return successResponse($cashierMoney);
    }

    public function destroy($id)
    {
        $cashierMoney = CashierMoney::find($id);
        $cashierMoney->delete();
        return successResponse($cashierMoney);
    }
}
