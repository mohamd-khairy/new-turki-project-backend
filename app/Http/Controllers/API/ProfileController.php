<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{

    public function editProfile(Request $request){

        $user = auth()->user();

        $validateData = Validator::make($request->post(),[
            'username' => 'sometimes|string|max:100',
            'email' => ['sometimes','string','email','max:255'],
            'avatar' => 'sometimes|max:1024|mimes:png,jpeg,jpg',
            'description'=> 'sometimes|string|max:255',
            'mobile' => ['sometimes', 'numeric'],
            'mobile_country_code' => 'sometimes|regex:/(^(\+\d{1,3})$)/',
            'country_code' => 'sometimes|string|max:2', // iso country code
            'age' => 'sometimes|numeric|min:8',
            'gender' => 'sometimes|boolean', // 0 -> male, 1 -> female
        ]);

        if ($validateData->fails())
            return response() -> json(["message" => "The given data was invalid.", "errors" => $validateData->errors()], 500);

        $validateData = $validateData->validated();

        $res = [];

            $res = array_merge($res, ['message' => "successfully updated."]);


        //$user->uploadAvatar($request);


        return response()->json($res, 200);
    }

    public function showProfile(){
        $user = User::find(auth()->id());
        if (is_null($user))
            return response() -> json(['message' => 'user not found'], 404);

        $response = ['data' => new UserResource($user)];

        $response = (new UserController())->getInstituteProfile($user, $response);

        return response() -> json($response, 200);
    }

}
