<?php
namespace App\Enums\Payment;
use App\Enums\BaseEnums;

class ProductTypeEnums extends BaseEnums
{
    public static $values = [
        "1" => 'course',
        "2" => 'webinar',
        "3" => 'meeting',
        "4" => 'package',
    ];
}

