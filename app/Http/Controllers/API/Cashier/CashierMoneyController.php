<?php

namespace App\Http\Controllers\API\Cashier;

use App\Http\Controllers\Controller;
use App\Models\CashierMoney;
use Illuminate\Http\Request;

class CashierMoneyController extends Controller
{

    public function index(Request $request)
    {
        $start_date = $request->start_date ?? date('Y-m-d', strtotime('-1 year'));
        $end_date = $request->end_date ?? date('Y-m-d', strtotime('+1 year'));

        $cashierMoney = CashierMoney::with('user.branch')
            ->when($start_date, fn($query) => $query->whereDate('date', '>=', $start_date))
            ->when($end_date, fn($query) => $query->whereDate('date', '<=', $end_date))
            ->latest()->paginate(request("per_page", 10));
        return successResponse($cashierMoney);
    }

    public function store(Request $request)
    {
        $data = $request->all();
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
