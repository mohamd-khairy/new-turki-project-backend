<?php

namespace App\Http\Middleware;

use App\Models\City;
use App\Models\Country;
use App\Models\NotDeliveryDateCity;
use App\Services\PointLocation;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\JsonResponse;

class AppendToAllResponses
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Get the response
        $response = $next($request);

        // Check if the response is a JSON response
        if ($response instanceof JsonResponse && $request->has(['latitude', 'longitude', 'countryId'])) {

            $point = $request->query('longitude') . " " . $request->query('latitude');
            $countryId = $request->query('countryId');
            $country = Country::where('code', $countryId)->get()->first();
            $polygon = app(PointLocation::class)->getPolygonOfCity($country, $point);

            $currentCity = City::select('id', 'name_en','name_ar', 'allow_cash', 'min_price')
                ->with('days', 'periods.delivery_period')
                ->where('polygon', $polygon)
                ->first();

            // Get the original data
            $originalData = $response->getData(true);

            $additionalData = [
                'currentCity' => $this->getNextFourdates($currentCity)
            ];

            // Merge the additional data with the original data
            $newData = array_merge($originalData, $additionalData);

            // Set the new data to the response
            $response->setData($newData);
        }

        return $response;
    }



    function getNextFourdates($currentCity)
    {
        $days = $currentCity->days; //->pluck('day')->toArray();

        $all_days = [
            'saturday' =>  Carbon::SATURDAY,
            'sunday' =>  Carbon::SUNDAY,
            'monday' =>  Carbon::MONDAY,
            'tuesday' =>  Carbon::TUESDAY,
            'wednesday' =>  Carbon::WEDNESDAY,
            'thursday' =>  Carbon::THURSDAY,
            'friday' =>  Carbon::FRIDAY,
        ];

        $dates = [];
        foreach ($days as $item) {
            $day = $item->day;
            $date = Carbon::now();

            // Find the next Saturday
            if ($date->dayOfWeek !== $all_days[$day]) {
                $date->modify('next ' . $day);
            }

            // Collect the dates of the next 4 dates
            for ($i = 0; $i < 3; $i++) {
                $d = $date->copy()->toDateString();
                if (!NotDeliveryDateCity::where('delivery_date', $d)->first()) {
                    $dates[] = $d;
                }
                $date->addWeek();
            }
        }

        sort($dates);

        // $currentCity->dates = $dates;

        return [
            "id" => $currentCity->id,
            "name_en" => $currentCity->name_en,
            "name_ar" => $currentCity->name_ar,
            "allow_cash" => $currentCity->allow_cash,
            "min_price" => $currentCity->min_price,
            "dates" => $dates,
            "delivery_period" => isset($currentCity->periods) ? $currentCity->periods->pluck('delivery_period') : null,
        ];
    }
}
