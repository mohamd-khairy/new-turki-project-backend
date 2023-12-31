<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class Payment extends Model
{
    public $inPermission = true;

    use HasFactory;

    use  LogsActivity;

    protected static $logAttributes = ['*'];

    protected static $logOnlyDirty = true;

    protected $fillable = ['ref_no', 'customer_id', 'payment_type_id', 'order_ref_no', 'bank_ref_no', 'price', 'description', 'status', 'manual'];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_ref_no', 'ref_no');
    }
}
