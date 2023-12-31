<?php

namespace App\Http\Controllers\API;
;
use App\Models\SettingApp;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;


class SettingAppController extends Controller
{

    public function getVersion(Request $request)
    {
        $version = SettingApp::get();
        return response()->json(['success' => true, 'message' => '', 'description' => "", "code" => "200",
            "data" => $version ], 200);
    }

    public function getVersionById(Request $request , SettingApp $version)
    {
        return response()->json(['success' => true, 'message' => '', 'description' => "", "code" => "200",
            "data" => $version ], 200);
    }
}
