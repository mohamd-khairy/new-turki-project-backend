<?php


namespace App\Services\Google_Map_API;


use App\Services\HttpServices;
use Illuminate\Support\Arr;

class GeocodingService
{
    protected HttpServices $http;
    protected array $config = [];
    protected string $BASE_URL = "https://maps.googleapis.com/maps/api/geocode/json";
    protected string $Params = "";
    protected array $location = [];

    public function __construct()
    {
        $this->http = new HttpServices();
        $this->config = array_merge($this->config, $this->getConfig());
    }

    public function getConfig($env = 'key')
    {
        return config('http.headers');
    }

    public function addConfig(array $config)
    {
        $this->config = array_merge($this->config, $this->getConfig());

        return $this;
    }

    public function searchByName($cityName, $address)
    {
        $this->location = [
            "address" => $address . ',' . $cityName,
            "key" => config('services.google.geocoding_api.key')
        ];

        $this->buildParams();
        $res = $this->http->get($this->BASE_URL, $this->location);

        $location = null;
        $formatted_address = null;

        if (isset($res['results'])) {
            $data = Arr::pull($res, 'results');

            if (isset($data[0])) {
                if (isset($data[0]['geometry']['location']))
                    $location = $data[0]['geometry']['location'];

                if (isset($data[0]['formatted_address']))
                    $formatted_address = $data[0]['formatted_address'];
            }
//            $d3 = Arr::pull($d2, 'geometry');
//            $location = Arr::pull($d3, 'location');
//            $formatted_address = Arr::pull($d2, 'formatted_address');
        }

        if ($location && $formatted_address)
            return array('formatted_address' => $formatted_address, 'location' => $location);
        else
            return $res;
    }

    public function buildParams()
    {
        $this->Params = http_build_query($this->location);
        return $this;
    }

    public function searchByCoordination($latitude, $longitude)
    {
        $this->location = [
            "latlng" => $latitude . ',' . $longitude,
            "key" => config('services.google.geocoding_api.key')
        ];

        $this->buildParams();
        $res = $this->http->get($this->BASE_URL, $this->Params);

       return ($res);
    }

    function pointInPolygon($point, $polygon) {

        $polygon = json_decode($polygon, true);
        //if you operates with (hundred)thousands of points
        set_time_limit(60);
        $c = 0;
        $p1 = $polygon[0];
        $n = count($polygon);

        for ($i=1; $i<=$n; $i++) {
            $p2 = $polygon[$i % $n];
            if ($point['long'] > min($p1->long, $p2->long)
                && $point['long'] <= max($p1->long, $p2->long)
                && $point['lat'] <= max($p1->lat, $p2->lat)
                && $p1->long != $p2->long) {
                $xinters = ($point['long'] - $p1->long) * ($p2->lat - $p1->lat) / ($p2->long - $p1->long) + $p1->lat;
                if ($p1->lat == $p2->lat || $point['lat'] <= $xinters) {
                    $c++;
                }
            }
            $p1 = $p2;
        }
        // if the number of edges we passed through is even, then it's not in the poly.
        return $c%2!=0;
    }
    
    function ipInfo($ip = NULL, $purpose = "location", $deep_detect = TRUE) {
    $output = NULL;
    if (filter_var($ip, FILTER_VALIDATE_IP) === FALSE) {
        $ip = $_SERVER["REMOTE_ADDR"];
        if ($deep_detect) {
            if (filter_var(@$_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP))
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            if (filter_var(@$_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP))
                $ip = $_SERVER['HTTP_CLIENT_IP'];
        }
    }
    $purpose    = str_replace(array("name", "\n", "\t", " ", "-", "_"), NULL, strtolower(trim($purpose)));
    $support    = array("country", "countrycode", "state", "region", "city", "location", "address");
    $continents = array(
        "AF" => "Africa",
        "AN" => "Antarctica",
        "AS" => "Asia",
        "EU" => "Europe",
        "OC" => "Australia (Oceania)",
        "NA" => "North America",
        "SA" => "South America"
    );
    if (filter_var($ip, FILTER_VALIDATE_IP) && in_array($purpose, $support)) {
        $ipdat = @json_decode(file_get_contents("http://www.geoplugin.net/json.gp?ip=" . $ip));
        if (@strlen(trim($ipdat->geoplugin_countryCode)) == 2) {
            switch ($purpose) {
                case "location":
                    $output = array(
                        "city"           => @$ipdat->geoplugin_city,
                        "state"          => @$ipdat->geoplugin_regionName,
                        "country"        => @$ipdat->geoplugin_countryName,
                        "country_code"   => @$ipdat->geoplugin_countryCode,
                        "continent"      => @$continents[strtoupper($ipdat->geoplugin_continentCode)],
                        "continent_code" => @$ipdat->geoplugin_continentCode
                    );
                    break;
                case "address":
                    $address = array($ipdat->geoplugin_countryName);
                    if (@strlen($ipdat->geoplugin_regionName) >= 1)
                        $address[] = $ipdat->geoplugin_regionName;
                    if (@strlen($ipdat->geoplugin_city) >= 1)
                        $address[] = $ipdat->geoplugin_city;
                    $output = implode(", ", array_reverse($address));
                    break;
                case "city":
                    $output = @$ipdat->geoplugin_city;
                    break;
                case "state":
                    $output = @$ipdat->geoplugin_regionName;
                    break;
                case "region":
                    $output = @$ipdat->geoplugin_regionName;
                    break;
                case "country":
                    $output = @$ipdat->geoplugin_countryName;
                    break;
                case "countrycode":
                    $output = @$ipdat->geoplugin_countryCode;
                    break;
            }
        }
    }
    return $output;
}


}
