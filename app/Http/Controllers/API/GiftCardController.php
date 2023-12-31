<?php

namespace App\Http\Controllers\API;

use App\Enums\OrderStateEnums;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderCollection;
use App\Models\Address;
use App\Models\Cart;
use App\Models\City;
use App\Models\Customer;
use App\Models\GiftCard;
use App\Models\Shalwata;
use App\Models\CartInfo;
use App\Models\Cut;
use App\Models\Discount;
use App\Models\Favorite;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\PaymentType;
use App\Models\Preparation;
use App\Models\Product;
use App\Models\Category;
use App\Models\ProductCity;
use App\Models\ProductCut;
use App\Models\ProductPaymentType;
use App\Models\ProductPreparation;
use App\Models\ProductSize;
use App\Models\Size;
use App\Models\ProductRating;
use App\Models\SubCategory;
use App\Http\Resources\ProductListResource;
use App\Http\Resources\SubcategoryListResource;
use App\Http\Resources\ProductDetailsResource;
use Illuminate\Http\Request;
use App\Models\productImage;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\ImageManagerStatic as Image;
use Illuminate\Support\Facades\DB;

class GiftCardController extends Controller

{

   public function getAll()
    {
        $data = GiftCard::get();
        return response()->json(['success' => 'true','data'=> $data,
        'message'=> 'retrieved successfully', 'description'=> '', 'code'=>'200'],200);
    }
    
    public function getById($GiftCard)
    {
        return response()->json(['success' => true, 'message' => '', 'description' => "", "code" => "200",
            "data" => GiftCard::find($GiftCard)], 200);
    }
    public function createGiftCard(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string',
            'code' => array('required', 'regex:(^[a-zA-Z0-9_]*$)', 'min:16', 'max:16'),
            'amount' => 'required|numeric',
            'is_active' => 'required|boolean',
            'expire_at' => array('required', 'date_format:Y-m-d H:m')
        ]);

        $giftCard = GiftCard::create($validatedData);

        return response()->json(['success' => true ,'data'=> $giftCard,
             'message'=> 'Successfully Added!', 'description'=> '', 'code'=>'200'],200);
    }
    
        public function delete(GiftCard $giftcard)
    {
        if ($giftcard->delete()) {

            return response()->json(['message' => 'Successfully Deleted!'], 200);
            
        }

        return response()->json(['message' => 'Something went wrong!'], 500);

    }

   public function updateGiftCard(Request $request,GiftCard $giftcard)
    {
        
        $validatedData = $request->validate([
            'name' => 'sometimes|string',
            'code' => array('sometimes', 'regex:(^[a-zA-Z0-9_]*$)', 'min:16', 'max:16'),
            'amount' => 'sometimes|numeric',
            'is_active' => 'sometimes|boolean',
            'expire_at' => array('sometimes', 'date_format:Y-m-d H:m')
        ]);

        $giftcard->update($validatedData);

        return response()->json(['success' => true ,'data'=> $validatedData,
             'message'=> 'Successfully Added!', 'description'=> '', 'code'=>'200'],200);
    }
    
    function redeemGiftCard(Request $request) {

        $validate = $request->validate([
            'code' => 'required|exists:gift_cards,code',
        ]);
      $code = $validate['code'];
       // $giftCard = GiftCard::where(['code', $validate['code'], ['is_active', 1]])->get()->last();
        $giftCard = GiftCard::where([['code', $code ],['is_active', 1]])->get()->first();

        if (GiftCard::isValid($giftCard) == null)
            return response()->json(['success' => false ,'data'=> null,
                'message'=> 'Gift Card not valid!', 'description'=> '', 'code'=>'400'],400);

        $customer = Customer::find(auth()->user()->id);
   
        $customer->wallet = $customer->wallet + $giftCard->amount;
        $customer->save();

        return response()->json(['success' => true ,'data'=> null,
            'message'=> 'Successfully redeemed!', 'description'=> '', 'code'=>'200'],200);

    }
}
