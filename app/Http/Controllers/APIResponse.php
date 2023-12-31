<?php


namespace App\Http\Controllers;


class APIResponse
{
    public function apiResponse(bool $success, string $message, $data = null, string $code = '200', string $description = ''): \Illuminate\Http\JsonResponse
    {
        return response()->json(['data'=> $data,
            'success' => $success, 'message'=> $message, 'description'=> $description, 'code'=>$code],200);
    }
}
