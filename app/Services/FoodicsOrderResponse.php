<?php

namespace App\Services;

class FoodicsOrderResponse
{

}

class Business {
    public $name; //String
    public $reference; //int

}
class Branch {
    public $id; //String
    public $name; //Date
    public $name_localized; //array( undefined )
    public $reference; //String
    public $type; //int
    public $latitude; //array( undefined )
    public $longitude; //array( undefined )
    public $phone; //array( undefined )
    public $opening_from; //String
    public $opening_to; //String
    public $inventory_end_of_day_time; //String
    public $receipt_header; //array( undefined )
    public $receipt_footer; //array( undefined )
    public $settings;  //array( undefined )
    public $created_at; //Date
    public $updated_at; //Date
    public $deleted_at; //array( undefined )
    public $receives_online_orders; //boolean
    public $accepts_reservations; //boolean
    public $reservation_duration; //int
    public $reservation_times; //array( undefined )
    public $address; //array( undefined )

}
class Payment_method {
    public $id; //String
    public $name; //String
    public $name_localized; //array( undefined )
    public $type; //int
    public $code; //array( undefined )
    public $auto_open_drawer; //boolean
    public $is_active; //boolean
    public $created_at; //Date
    public $updated_at; //Date
    public $deleted_at; //array( undefined )
    public $index; //int

}
class Payments {
    public $user; //array( undefined )
    public $payment_method; //Payment_method
    public $meta;  //array( undefined )
    public $id; //String
    public $amount; //int
    public $tendered; //int
    public $tips; //int
    public $business_date; //array( undefined )
    public $added_at; //Date

}
class Category {
    public $id; //String
    public $name; //String
    public $name_localized; //array( undefined )
    public $reference; //array( undefined )
    public $image; //array( undefined )
    public $created_at; //Date
    public $updated_at; //Date
    public $deleted_at; //array( undefined )

}
class Product {
    public $category; //Category
    public $ingredients;  //array( undefined )
    public $id; //String
    public $sku; //Date
    public $barcode; //array( undefined )
    public $name; //String
    public $name_localized; //array( undefined )
    public $description; //array( undefined )
    public $description_localized; //array( undefined )
    public $image; //array( undefined )
    public $is_active; //boolean
    public $is_stock_product; //boolean
    public $is_ready; //boolean
    public $pricing_method; //int
    public $selling_method; //int
    public $costing_method; //int
    public $preparation_time; //array( undefined )
    public $price; //int
    public $cost; //array( undefined )
    public $calories; //array( undefined )
    public $created_at; //Date
    public $updated_at; //Date
    public $deleted_at; //array( undefined )

}
class Products {
    public $product; //Product
    public $promotion; //array( undefined )
    public $discount; //array( undefined )
    public $options;  //array( undefined )
    public $taxes;  //array( undefined )
    public $timed_events;  //array( undefined )
    public $void_reason; //array( undefined )
    public $creator; //array( undefined )
    public $voider; //array( undefined )
    public $discount_type; //array( undefined )
    public $quantity; //int
    public $returned_quantity; //int
    public $unit_price; //int
    public $discount_amount; //int
    public $total_price; //int
    public $total_cost; //int
    public $tax_exclusive_discount_amount; //int
    public $tax_exclusive_unit_price; //int
    public $tax_exclusive_total_price; //int
    public $status; //int
    public $is_ingredients_wasted; //int
    public $delay_in_seconds; //array( undefined )
    public $kitchen_notes; //array( undefined )
    public $meta; //array( undefined )
    public $added_at; //Date
    public $closed_at; //array( undefined )

}
class Order {
    public $branch; //Branch
    public $promotion; //array( undefined )
    public $original_order; //array( undefined )
    public $table; //array( undefined )
    public $creator; //array( undefined )
    public $closer; //array( undefined )
    public $driver; //array( undefined )
    public $customer; //array( undefined )
    public $customer_address; //array( undefined )
    public $discount; //array( undefined )
    public $tags;  //array( undefined )
    public $coupon; //array( undefined )
    public $gift_card; //array( undefined )
    public $charges;  //array( undefined )
    public $payments; //array( Payments )
    public $products; //array( Products )
    public $combos;  //array( undefined )
    public $id; //String
    public $app_id; //String
    public $promotion_id; //array( undefined )
    public $discount_type; //array( undefined )
    public $reference_x; //array( undefined )
    public $number; //array( undefined )
    public $type; //int
    public $source; //int
    public $status; //int
    public $delivery_status; //array( undefined )
    public $guests; //int
    public $kitchen_notes; //array( undefined )
    public $customer_notes; //String
    public $business_date; //array( undefined )
    public $subtotal_price; //int
    public $discount_amount; //int
    public $rounding_amount; //int
    public $total_price; //int
    public $tax_exclusive_discount_amount; //int
    public $delay_in_seconds; //array( undefined )
    public $meta; //array( undefined )
    public $opened_at; //Date
    public $accepted_at; //array( undefined )
    public $due_at; //Date
    public $driver_assigned_at; //array( undefined )
    public $dispatched_at; //array( undefined )
    public $driver_collected_at; //array( undefined )
    public $delivered_at; //array( undefined )
    public $closed_at; //array( undefined )
    public $created_at; //Date
    public $updated_at; //Date
    public $reference; //int
    public $check_number; //array( undefined )

}
class Application {
    public $timestamp; //int
    public $event; //String
    public $business; //Business
    public $order; //Order

}
