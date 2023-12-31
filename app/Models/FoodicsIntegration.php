<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FoodicsIntegration extends Model
{
    use HasFactory;

    protected $fillable = ["foodics_id",
                            "total_price",
                            "customer_notes",
                            "discount_amount",
                            "branch_id",
                            "branch_name",
                            "full_response",
                            'sent_to_3rd_party'];
}
