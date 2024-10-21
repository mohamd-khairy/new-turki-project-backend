<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class Stock extends Model
{
    use HasFactory;
    use LogsActivity;

    protected static $logAttributes = ['*'];

    protected static $logOnlyDirty = true;

    public $inPermission = true;

    protected $fillable = [
        'product_id', 'product_name', 'quantity', 'price', 'invoice_id', 'store_id'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
