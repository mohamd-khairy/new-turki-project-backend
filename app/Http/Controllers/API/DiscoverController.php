<?php


namespace App\Http\Controllers\API;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Country;
use App\Models\Discover;
use App\Models\DiscoverCity;
use App\Models\Product;
use App\Models\Category;
use App\Http\Resources\DiscoverListResource;
use App\Http\Resources\DiscoverDashboardResource;
use App\Http\Resources\DiscoverDetailsResource;
use Illuminate\Support\Facades\Storage;
use App\Services\Google_Map_API\GeocodingService;
use App\Services\Google_Map_API\Polygon;
use App\Services\PointLocation;
use App\Services\PolygonCalculation;
use App\Services\sbPolygonEngine;
use geoPHP;



class DiscoverController extends Controller
{

    public function list(Request $request){

        $active = $request->query('active');

          if($active == "1")
         {
             $data = Discover::where('is_active', '1')->get();

               $data = DiscoverDashboardResource::Collection($data);

              return response()->json(['success' => true,  "data" => $data, 'message' => '', 'description' => "", "code" => "200",
           ], 200);
         }
         elseif($active == "0"){

             $data = Discover::get();

               $data = DiscoverDashboardResource::Collection($data);

              return response()->json(['success' => true, "data" => $data , 'message' => '', 'description' => "", "code" => "200",
            ], 200);
         }

      }



    // public function listDiscover(Category $category , Request $request){

    //      $active = $request->query('active');

    //       if (Discover::where('category_id', $category->id)->get()->isEmpty())
    //         return response()->json([],200);

    //       $data = Discover::where('category_id', $category->id)->get();

    //       if($active == "1")
    //      {
    //          $data = Discover::where('is_active', '1')->get();

    //           $data = DiscoverListResource::Collection($data);

    //           return response()->json(['success' => true,  "data" => $data, 'message' => '', 'description' => "", "code" => "200",
    //       ], 200);
    //      }
    //      elseif($active == "0"){
    //          $data = Discover::get();

    //           $data = DiscoverListResource::Collection($data);

    //           return response()->json(['success' => true, "data" => $data , 'message' => '', 'description' => "", "code" => "200",
    //         ], 200);
    //      }

    // }

     public function listDiscover(Category $category , Request $request){

         $active = $request->query('active');

          //  if (Discover::where('category_id', $category->id)->get()->isEmpty())
          //   return response()->json([],200);
              //get by location
    $point = $request->query('longitude') . " " . $request->query('latitude');
    $countryId = $request->query('countryId');
    $country = Country::where('code', $countryId)->get()->first();

    if ($country === null)
        return response()->json(['data'=> [],
            'success' => true, 'message'=> 'success', 'description'=>'this service not available in your country!', 'code'=>'200'],200);

    $currentCity = app(PointLocation::class)->getLocatedCity($country, $point);

    if ($currentCity != null){

        $discoverIds = DiscoverCity::where('city_id', $currentCity->id)->distinct()->pluck('discover_id');
        $discovers = Discover::whereIn('id', $discoverIds)->where('category_id', $category->id)->where('is_active', '1')->orderBy('id', 'DESC')->get();

    }
    else
    $discovers = [];

        $data =  DiscoverListResource::collection($discovers);
        return response()->json(['success' => true ,'data'=> $data,
        'message'=> 'Categories retrieved successfully', 'description'=> 'list Of Categories', 'code'=>'200'],200);

    }




       public function getById(Discover $discover)

    {
        return response()->json(['data' => new DiscoverDetailsResource($discover), 'message' => "success",
            'description' => "", 'code' => "200"], 200);
    }


    public function create(Request $request){

        $validatedData = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'titel_ar' => 'required|string',
            'titel_en' => 'required|string',
            'sub_titel_ar' => 'required|string',
            'sub_titel_en' => 'required|string',
            'description_ar' => 'string',
            'description_en' => 'string',
            'is_active' => 'required|bool',
            'image'=> 'required|mimes:png,jpeg,jpg',
            'sub_image'=> 'required|mimes:png,jpeg,jpg',
            'product_ids' => array('required', 'regex:(^([-+] ?)?[0-9]+(,[0-9]+)*$)'),
            'city_ids' => array('required', 'regex:(^([-+] ?)?[0-9]+(,[0-9]+)*$)'),

        ]);
        $imageName = $request->file('sub_image')->hashName();

        $validatedData['sub_image'] = $imageName;


        $product_ids = explode(',', $validatedData['product_ids']);
        $city_ids = explode(',', $validatedData['city_ids']);

        $product = Product::whereIn('id', $product_ids)->get();
        $cities = City::whereIn('id', $city_ids)->get();

        $hasCreated = Discover::create($validatedData);

        $hasCreated->products()->attach($product);
        $hasCreated->discoverCities()->attach($cities);

        $hasUploaded = Discover::uploadImage($request, $hasCreated, $validatedData);

        $request->file('sub_image')->storeAs('app/public/discover_sub_image/', $imageName);


        if(!$hasCreated->update($hasUploaded))
            return response()->json(['message' => 'has not created or image not uploaded,
             contact support please'],500);

             return response()->json(['success' => true ,'data'=> $hasCreated,
             'message'=> 'Successfully Added!', 'description'=> '', 'code'=>'200'],200);
        //return response()->json($hasCreated,201);
    }

       public function update(Request $request , Discover $discover){

        $validatedData = $request->validate([
            'category_id' => 'sometimes|exists:categories,id',
            'titel_ar' => 'sometimes|string',
            'titel_en' => 'sometimes|string',
            'sub_titel_ar' => 'sometimes|string',
            'sub_titel_en' => 'sometimes|string',
            'description_ar' => 'sometimes|string',
            'description_en' => 'sometimes|string',
            'is_active' => 'sometimes|bool',
            'image'=> 'sometimes|mimes:png,jpeg,jpg',
            'sub_image'=> 'sometimes|mimes:png,jpeg,jpg',
            'product_ids' => array('sometimes', 'regex:(^([-+] ?)?[0-9]+(,[0-9]+)*$)'),
            'city_ids' => array('sometimes', 'regex:(^([-+] ?)?[0-9]+(,[0-9]+)*$)'),

        ]);

         if ($request->has('sub_image')){
            $imageName = $request->file('sub_image')->hashName();
             $validatedData['sub_image'] = $imageName;
        }

        $product_ids = explode(',', $validatedData['product_ids']);
        $city_ids = explode(',', $validatedData['city_ids']);

        $product = Product::whereIn('id', $product_ids)->get();
        $cities = City::whereIn('id', $city_ids)->get();

        $discover->products()->sync($product);
        $discover->discoverCities()->sync($cities);

       if(isset($validatedData['sub_image'])){

            Storage::delete('discover_sub_image/', $imageName);
        }


        if(isset($validatedData['image'])){

              $validatedData = Discover::uploadImage($request, $discover, $validatedData);
        }



       if($discover->update($validatedData)){

        if ($request->file('sub_image')){
            $imageName = $request->file('sub_image')->storeAs('app/public/discover_sub_image/', $imageName);
            $validatedData['sub_image'] = $imageName;
        }
       }
        if(!$discover->update($validatedData))
            return response()->json(['message' => 'has not created or image not uploaded,
             contact support please'],500);

             return response()->json(['success' => true ,'data'=> $discover,
             'message'=> 'Successfully Added!', 'description'=> '', 'code'=>'200'],200);
        //return response()->json($hasCreated,201);
    }

     public function delete(Discover $discover)
    {
        $id = $discover->id;

        if($discover->delete()){

            return response()->json(['massage'=>'deleted successfully'],200);
        }
        else{
            return response()->json(['message' => 'ERROR PLEASE TRY AGAIN LATER'], 500);
        }

    }

}
