<?php

namespace App\Services\Google_Map_API;


class segment
{
    /*------------------------------------------------------------------------------
    ** This class contains the information about the segments between vetrices. In
    ** the original algorithm these were just lines. In this extended form they
    ** may also be arcs. By creating a separate object for the segment and then
    ** referencing to it forward & backward from the two vertices it links it is
    ** easy to track in various directions through the polygon linked list.
    */
    var $xc, $yc;               // Coordinates of the center of the arc
    var $d;                         // Direction of the arc, -1 = clockwise, +1 = anti-clockwise,
    // A 0 indicates this is a line
    /*
    ** Construct a segment
    */
    function segment($xc = 0, $yc = 0, $d = 0)
    {
        $this->xc = $xc;
        $this->yc = $yc;
        $this->d = $d;
    }

    /*
    ** Return the contents of a segment
    */
    function Xc()
    {
        return $this->xc;
    }

    function Yc()
    {
        return $this->yc;
    }

    function d()
    {
        return $this->d;
    }

    /*
    ** Set Xc/Yc
    */
    function setXc($xc)
    {
        $this->xc = $xc;
    }

    function setYc($yc)
    {
        $this->yc = $yc;
    }
}
