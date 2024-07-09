<?php


namespace App\Services;

/*
Description: The point-in-polygon algorithm allows you to check if a point is
inside a polygon or outside of it.
Author: Michaël Niessen (2009)
Website: http://AssemblySys.com

If you find this script useful, you can show your
appreciation by getting Michaël a cup of coffee ;)
donation to Michaël


As long as this notice (including author name and details) is included and
UNALTERED, this code is licensed under the GNU General Public License version 3:
http://www.gnu.org/licenses/gpl.html
*/

use App\Models\City;

class PointLocation
{
    var $pointOnVertex = true; // Check if the point sits exactly on one of the vertices?

    function PointLocation()
    {
    }

    function pointInPolygon($point, $polygon, $pointOnVertex = true)
    {
        $this->pointOnVertex = $pointOnVertex;

        // Transform string coordinates into arrays with x and y values
        $point = $this->pointStringToCoordinates($point);
        $vertices = array();
        foreach ($polygon as $vertex) {
            $vertices[] = $this->pointStringToCoordinates($vertex);
        }



        // Check if the point sits exactly on a vertex
        if ($this->pointOnVertex == true and $this->pointOnVertex($point, $vertices) == true) {
            //"vertex"
            return true;
        }

        // Check if the point is inside the polygon or on the boundary
        $intersections = 0;
        $vertices_count = count($vertices);

        for ($i = 1; $i < $vertices_count; $i++) {
            $vertex1 = $vertices[$i - 1];
            $vertex2 = $vertices[$i];
            if ($vertex1['y'] == $vertex2['y'] and $vertex1['y'] == $point['y'] and $point['x'] > min($vertex1['x'], $vertex2['x']) and $point['x'] < max($vertex1['x'], $vertex2['x'])) { // Check if point is on an horizontal polygon boundary
                // "boundary"
                return true;
            }
            if ($point['y'] > min($vertex1['y'], $vertex2['y']) and $point['y'] <= max($vertex1['y'], $vertex2['y']) and $point['x'] <= max($vertex1['x'], $vertex2['x']) and $vertex1['y'] != $vertex2['y']) {
                $xinters = ($point['y'] - $vertex1['y']) * ($vertex2['x'] - $vertex1['x']) / ($vertex2['y'] - $vertex1['y']) + $vertex1['x'];
                if ($xinters == $point['x']) { // Check if point is on the polygon boundary (other than horizontal)
                    // "boundary"
                    return true;
                }
                if ($vertex1['x'] == $vertex2['x'] || $point['x'] <= $xinters) {
                    $intersections++;
                }
            }
        }
        // If the number of edges we passed through is odd, then it's in the polygon.
        if ($intersections % 2 != 0) {
            // "inside"
            return true;
        } else {
            // "outside"
            return false;
        }
    }

    function pointOnVertex($point, $vertices)
    {
        foreach ($vertices as $vertex) {
            if ($point == $vertex) {
                return true;
            }
        }
    }

    function pointStringToCoordinates($pointString)
    {
        $coordinates = explode(" ", $pointString);
        return array("x" => $coordinates[0], "y" => $coordinates[1]);
    }

    /**
     * @param $country
     * @param string $point
     * @return null
     */
    function getLocatedCity($country, string $point)
    {
        $polygonList = json_decode(City::where('country_id', $country->id)->pluck('polygon'));
        $pointLocation = new PointLocation();
        $currentCity = null;
        // dd($polygonList);
        if ($polygonList) {
            foreach ($polygonList as $polygon) {

                $polygon = is_string($polygon) ? json_decode($polygon) : $polygon;


                if ($polygon && $pointLocation->pointInPolygon($point, $polygon)) {
                    $currentCity = City::where('polygon', json_encode($polygon))->get()->first();
                    break;
                }
            }
        }
        return $currentCity ?? null;
    }

    function getPolygonOfCity($country, string $point)
    {
        $polygonList = json_decode(City::where('country_id', $country->id)->pluck('polygon'));
        $pointLocation = new PointLocation();
        $currentCity = null;
        // dd($polygonList);
        if ($polygonList) {
            foreach ($polygonList as $polygon) {

                $polygon = is_string($polygon) ? json_decode($polygon) : $polygon;


                if ($polygon && $pointLocation->pointInPolygon($point, $polygon)) {
                    return json_encode($polygon);
                }
            }
        }
        return null;
    }
}
