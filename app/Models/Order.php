<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

class Order extends Model
{
    // use  LogsActivity;

    protected static $logAttributes = ['order_state_id'];

    protected static $logOnlyDirty = true;

    public $inPermission = true;

    use HasFactory, SoftDeletes;

    protected $fillable = [
        'id',
        'ref_no',
        'delivery_fee',
        'order_subtotal',
        'total_amount',
        'total_amount_after_discount',
        'total_amount_before_discount',
        "comment",
        "using_wallet",
        'wallet_amount_used',
        "address",
        'customer_id',
        'order_state_id',
        'payment_type_id',
        'applied_discount_code',
        'address_id',
        'delivery_date',
        'shalwata_id',
        'delivery_period_id',
        'payment_id',
        'integrate_id',
        'saleOrderId',
        'version_app',
        'user_id',
        'discount_applied',
        'paid',
        'boxes_count',
        'dishes_count',
        'sales_representative_id',
        'driver_name',
        'printed_at'
    ];

    protected $hidden = ['address'];
    protected $primaryKey = 'ref_no';
    public $incrementing = false;

    public $appends = ['tax_fees', 'total_amount_after_tax', 'qr', 'qr_string', 'remain_amount', 'discount_code', 'is_printed'];

    public function getIsPrintedAttribute()
    {
        return  $this->printed_at ?  true : false;
    }

    public function getRemainAmountAttribute()
    {
        return  ($this->payment && $this->payment->status == 'Paid' ?  $this->total_amount_after_discount - $this->payment->price : $this->total_amount_after_discount ?? 0) + $this->wallet_amount_used;
    }

    public function getTotalAmountAfterTaxAttribute()
    {
        $per = 1.15;
        if (isset($this->selectedAddress->country_id) && $this->selectedAddress->country_id == 4) {
            $per = 1;//1.05;
        }
        return  $this->total_amount_after_discount ? round($this->total_amount_after_discount / $per, 2) : 0;
    }

    public function getDiscountCodeAttribute()
    {
        return  $this->applied_discount_code ? $this->applied_discount_code : null;
    }

    public function getTaxFeesAttribute()
    {
        return round(($this->total_amount_after_discount ?? 0) - ($this->total_amount_after_tax ?? 0), 2);
    }

    public function getQrAttribute()
    {
        $writer = new PngWriter();

        // Create QR code
        $qrCode = QrCode::create(generateQrInvoice($this))
            ->setEncoding(new Encoding('UTF-8'))
            ->setSize(300)
            ->setMargin(10)
            ->setForegroundColor(new Color(0, 0, 0))
            ->setBackgroundColor(new Color(255, 255, 255));


        $result = $writer->write($qrCode);

        $result->saveToFile(public_path('qr/' . $this->ref_no . '.png'));

        return config('app.url') . '/' . 'qr/' . $this->ref_no . '.png';
    }

    public function getQrStringAttribute()
    {
        return generateQrInvoice($this);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function orderState()
    {
        return $this->belongsTo(OrderState::class, 'order_state_id');
    }

    public function paymentType()
    {
        return $this->belongsTo(PaymentType::class, 'payment_type_id');
    }

    public function selectedAddress()
    {
        return $this->belongsTo(Address::class, 'address_id')->select(['id', 'address', 'comment', 'label', 'long', 'lat', 'country_id']);
    }

    public function deliveryPeriod()
    {
        return $this->belongsTo(DeliveryPeriod::class, 'delivery_period_id');
    }

    public function paidpayment()
    {
        return $this->belongsTo(Payment::class, 'payment_id')->where('status' , 'Paid')->latest();
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class, 'payment_id')->latest();
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'order_products', 'order_ref_no', 'product_id')->with('productImages');
    }

    public function orderProducts()
    {
        return $this->hasMany(OrderProduct::class, 'order_ref_no')->with('preparation', 'size', 'cut', 'shalwata', 'product.productImages');
    }

    public function order_products()
    {
        return $this->hasMany(OrderProduct::class, 'order_ref_no', 'ref_no');
    }

    public function salesRepresentative()
    {
        return $this->belongsTo(User::class, 'sales_representative_id');
    }
}
