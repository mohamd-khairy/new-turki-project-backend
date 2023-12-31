<?php


namespace App\Enums;


class OrderStateEnums extends BaseEnums
{
    public static $values = [
        '100' => 'order received',
        '101' => 'order confirmed',
        '102' => 'preparing order',
        '103' => 'quality assurance',
        '104' => 'out for delivery',
        '200' => 'delivered'
    ];
}
