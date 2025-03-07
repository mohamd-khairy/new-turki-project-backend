<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Spatie\Activitylog\Traits\LogsActivity;

class Order extends Model
{
    use LogsActivity;

    protected static $logAttributes = ['*'];

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
        'printed_at',
        'foodics_integrate_id',
        'later',
        'other_discount'
    ];

    protected $hidden = ['address'];
    protected $primaryKey = 'ref_no';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();

        static::created(function ($model) {
            // This code will be executed when a new record is being created

            try {
                streamOrder($model->ref_no, 'message');

                $order = $model->load(
                    'paymentType',
                    'customer',
                    'orderState',
                    'deliveryPeriod',
                    'selectedAddress',
                );

                info('test order');
                info($order->ref_no);
                info($order->toArray());

                $products = OrderProduct::with('preparation', 'size', 'cut', 'shalwata')
                    ->where('order_ref_no', $order->ref_no)
                    ->get();

                $result = sendOrderToTurkishop($order, $products);
                info($order->ref_no);
                info(json_encode($result));

                // OrderToFoodics($model->ref_no);
            } catch (\Throwable $th) {
                //throw $th;
                info($order->ref_no);
                info($th->getMessage());
            }
        });

        static::updated(function ($model) {
            // This code will be executed when a new record is being created
            try {
                streamOrder($model->ref_no, 'update-order');
            } catch (\Throwable $th) {
                //throw $th;
            }
        });
    }
    public $appends = [
        'tax_fees',
        'total_amount_after_tax',
        'qr',
        'qr_string',
        'remain_amount',
        'discount_code',
        'is_printed',
        'final_amount'
    ];

    public $casts = ['payment_types' => 'array'];

    public function getIsPrintedAttribute()
    {
        return  $this->printed_at ?  true : false;
    }

    public function getFinalAmountAttribute()
    {
        return round(($this->order_subtotal - $this->discount_applied - $this->other_discount), 2) ?? 0;
    }

    public function getRemainAmountAttribute()
    {
        return round(($this->final_amount - (($this->payment && $this->payment->status == 'Paid' ? $this->payment->price : 0) + ($this->wallet_amount_used ?? 0))), 2);
    }

    public function getTotalAmountAfterTaxAttribute()
    {
        $per = 1.15;
        if (isset($this->selectedAddress->country_id) && $this->selectedAddress->country_id == 4) {
            $per = 1; //1.05;
        }
        return  round($this->final_amount / $per, 2);
    }

    public function getTaxFeesAttribute()
    {
        return round($this->final_amount - ($this->total_amount_after_tax ?? 0), 2);
    }

    public function getDiscountCodeAttribute()
    {
        return  $this->applied_discount_code ? $this->applied_discount_code : null;
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
        return $this->belongsTo(Customer::class)->withTrashed();
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
        return $this->belongsTo(Address::class, 'address_id')->with('city')->select(['id', 'address', 'comment', 'label', 'long', 'lat', 'country_id', 'foodics_integrate_id', 'city_id']);
    }

    public function deliveryPeriod()
    {
        return $this->belongsTo(DeliveryPeriod::class, 'delivery_period_id');
    }

    public function paidpayment()
    {
        return $this->belongsTo(Payment::class, 'payment_id')->where('status', 'Paid')->latest();
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

    public function cashier_payments()
    {
        return $this->hasMany(CashierPayment::class, 'order_ref_no', 'ref_no')->with('payment_type');
    }
}
