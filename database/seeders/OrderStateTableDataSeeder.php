<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\OrderState;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderStateTableDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // DB::table('order_states')->truncate();
        DB::table('order_states')->insert(
            [
                [
                    'state_en' => "order received",
                    'state_ar' => "تم استلام الطلب",
                    'customer_state_en' => "order received",
                    'customer_state_ar' => "تم استلام الطلب",
                    'code' => "100",
                    'is_active' => 1
                ],
                [
                    'state_en' => "order confirmed",
                    'state_ar' => "تم تأكيد الطلب",
                    'customer_state_en' => "order confirmed",
                    'customer_state_ar' => "تم تأكيد الطلب",
                    'code' => "101",
                    'is_active' => 1
                ],
                [
                    'state_en' => "Pending",
                    'state_ar' => "معلق",
                    'customer_state_en' => "Pending",
                    'customer_state_ar' => "معلق",
                    'code' => "102",
                    'is_active' => 1
                ],
                [
                    'state_en' => "reject",
                    'state_ar' => "ملغي",
                    'customer_state_en' => "reject",
                    'customer_state_ar' => "ملغي",
                    'code' => "103",
                    'is_active' => 1
                ],
                [
                    'state_en' => "preparing order",
                    'state_ar' => "جاري التجهيز",
                    'customer_state_en' => "preparing order",
                    'customer_state_ar' => "جاري التجهيز",
                    'code' => "104",
                    'is_active' => 1
                ], [
                    'state_en' => "preparing done",
                    'state_ar' => "تم التجهيز",
                    'customer_state_en' => "preparing done",
                    'customer_state_ar' => "تم التجهيز",
                    'code' => "105",
                    'is_active' => 1
                ],
                [
                    'state_en' => "out for delivery",
                    'state_ar' => "جاري التوصيل",
                    'customer_state_en' => "out for delivery",
                    'customer_state_ar' => "جاري التوصيل",
                    'code' => "106",
                    'is_active' => 1
                ],
                [
                    'state_en' => "returned",
                    'state_ar' => "تم الارجاع",
                    'customer_state_en' => "returned",
                    'customer_state_ar' => "تم الارجاع",
                    'code' => "107",
                    'is_active' => 1
                ],
                [
                    'state_en' => "partialy returned",
                    'state_ar' => "تم الارجاع بشكل جزئي",
                    'customer_state_en' => "partialy returned",
                    'customer_state_ar' => "تم الارجاع بشكل جزئي",
                    'code' => "108",
                    'is_active' => 1
                ],
                [
                    'state_en' => "problem in delivered",
                    'state_ar' => "يوجد مشكلة في التوصيل",
                    'customer_state_en' => "problem in delivered",
                    'customer_state_ar' => "يوجد مشكلة في التوصيل",
                    'code' => "109",
                    'is_active' => 1
                ],
                [
                    'state_en' => "delivered",
                    'state_ar' => "تم التوصيل",
                    'customer_state_en' => "delivered",
                    'customer_state_ar' => "تم التوصيل",
                    'code' => "200",
                    'is_active' => 1
                ],
            ]
        );
    }
}
