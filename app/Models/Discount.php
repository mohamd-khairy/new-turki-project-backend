<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;

class Discount extends Model
{
    public $inPermission = true;

    use HasFactory, SoftDeletes;

    use  LogsActivity;

    protected static $logAttributes = ['*'];

    protected static $logOnlyDirty = true;

    protected $fillable = [
        'name',
        'code',
        'product_ids',
        'size_ids',
        'discount_amount_percent',
        'min_applied_amount',
        'max_discount',
        'is_for_all',
        'is_percent',
        'is_active',
        'city_ids',
        'country_ids',
        'category_parent_ids',
        'category_child_ids',
        'expire_at',
        'use_times_per_user',
        'integrate_id',
        'is_by_city',
        'is_by_country',
        'is_by_category',
        'is_by_subcategory',
        'is_by_product',
        'is_by_size',
        // new field
        'client_ids',
        'for_clients_only',
        'foodics_integrate_id'
    ];


    protected $casts = [
        'expire_at' => 'datetime:Y-m-d H:m',
        'created_at' => 'datetime:Y-m-d H:m',
        'updated_at' => 'datetime:Y-m-d H:m',
    ];

    public function getCategoryParentIdsAttribute()
    {
        return $this->attributes['category_parent_ids'] ? array_map('intval', explode(',', $this->attributes['category_parent_ids']) ?? null) : null;
    }

    public function getCategoryChildIdsAttribute()
    {
        return $this->attributes['category_child_ids']  ? array_map('intval', explode(',', $this->attributes['category_child_ids']) ?? null) : null;
    }

    public function getClientIdsAttribute()
    {
        return $this->attributes['client_ids']  ? array_map('intval', explode(',', $this->attributes['client_ids']) ?? null) : null;
    }

    public function getCountryIdsAttribute()
    {
        return $this->attributes['country_ids']  ? array_map('intval', explode(',', $this->attributes['country_ids']) ?? null) : null;
    }

    public function getCityIdsAttribute()
    {
        return $this->attributes['city_ids']  ? array_map('intval', explode(',', $this->attributes['city_ids']) ?? null) : null;
    }

    public function getProductIdsAttribute()
    {
        return $this->attributes['product_ids']  ? array_map('intval', explode(',', $this->attributes['product_ids']) ?? null) : null;
    }

    public function getSizeIdsAttribute()
    {
        return $this->attributes['size_ids']  ? array_map('intval', explode(',', $this->attributes['size_ids']) ?? null) : null;
    }


    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    public function setExpireAtAttribute($value)
    {
        $this->attributes['expire_at'] = (new Carbon($value))->format('Y-m-d H:m');
    }

    public static function isValid($coupon, $code, $productIds, $total)
    {
        if ($productIds == null || $productIds == [])
            return null;

        if ($coupon == null || !$coupon->is_active) {
            return null;
        }

        $expire_at = Carbon::make($coupon->expire_at)->timestamp;

        //if not valid reject
        if ($expire_at < Carbon::now()->timestamp) {
            return null;
        }

        //if not valid reject
        if ($coupon->min_applied_amount != null && $total < $coupon->min_applied_amount)
            return null;

        $usedTimes = Order::where([['customer_id', auth()->user()->id], ['applied_discount_code', $code]])->get();

        if ($usedTimes != [] && count($usedTimes) >= $coupon->use_times_per_user)
            return null;

        if ($coupon->for_clients_only == true && $coupon->client_ids != null && empty($coupon->client_ids) == false) {
            $clientId = auth()->user()->id;
            $validClientIds = explode(',', $coupon->client_ids);
            if (!in_array($clientId, $validClientIds)) {
                return null;
            }
        }

        if ($coupon->is_for_all == false) {

            $validCategoryIds = [];
            $validSubCategoryIds = [];
            $validProductIds = [];
            $validCityIds = [];
            $validCountryIds = [];

            if ($coupon->product_ids != null)
                $validProductIds = explode(',', $coupon->product_ids);

            if ($coupon->size_ids != null)
                $validSizeIds = explode(',', $coupon->size_ids);

            if ($coupon->category_parent_ids != null)
                $validCategoryIds = explode(',', $coupon->category_parent_ids);

            if ($coupon->category_child_ids != null)
                $validSubCategoryIds = explode(',', $coupon->category_child_ids);

            if ($coupon->city_ids != null)
                $validCityIds = explode(',', $coupon->city_ids);

            if ($coupon->country_ids != null)
                $validCountryIds = explode(',', $coupon->country_ids);

            // products in cart
            foreach ($productIds as $productId) {
                $product = Product::where('id', $productId)->active()->get()->first();
                $cities = $product->cities;
                $sizes = $product->sizes;

                $validCity = false;
                $validCountry = false;

                //if one not valid reject
                foreach ($cities as $city) {

                    if (count($validCountryIds) != 0 && in_array($city->country_id, $validCountryIds)) {
                        $validCountry = true;
                        break;
                    }

                    if (count($validCityIds) != 0 && in_array($city->id, $validCityIds)) {
                        // dd("invaild country", $city, $validCityIds);
                        $validCity = true;
                        break;
                    }
                }

                if (count($validCountryIds) != 0 && !$validCountry) {
                    return null;
                }
                //if one not valid reject
                if (count($validCityIds) != 0 && !$validCity) {
                    return null;
                }

                //if one not valid reject
                if (count($validProductIds) != 0 && !in_array($productId, $validProductIds)) {
                    return null;
                }

                foreach ($sizes as $size) {

                    if (count($validSizeIds) != 0 && in_array($size->id, $validSizeIds)) {
                        $validSize = true;
                        break;
                    }
                }

                if (count($validSizeIds) != 0 && !$validSize) {
                    return null;
                }

                //if one not valid reject
                if (count($validCategoryIds) != 0 && !in_array($product->category_id, $validCategoryIds)) {
                    return null;
                }

                //if one not valid reject
                if (count($validSubCategoryIds) != 0 && !in_array($product->sub_category_id, $validSubCategoryIds)) {
                    return null;
                }
            }
        }

        return $coupon;
    }

    public static function isValidV2($coupon, $code, $productIds, $total, $countryId, $cityId, $sizeIds)
    {
        if ($productIds == null || $productIds == [])
            return [1, "add items to cart"];

        if ($coupon == null || !$coupon->is_active) {
            return [2, "coupon is disabled"];
        }

        $expire_at = Carbon::make($coupon->expire_at)->timestamp;
        $currentTimestamp = Carbon::now()->timestamp;
        //if not valid reject
        if ((int)$expire_at < (int)$currentTimestamp) {
            return [3, "coupon is expired"];
        }

        //if not valid reject (default 0)
        if ($coupon->min_applied_amount > $total)
            return [4, "coupon not met minimum value " . $coupon->min_applied_amount];


        $usedTimes = Order::where([['customer_id', auth()->user()->id], ['applied_discount_code', $code]])->get();

        if (count($usedTimes) >= $coupon->use_times_per_user)
            return [5, "coupon is used at maximum!"];

        if ($coupon->is_for_all) {
            return [400, $coupon];
        }

        $entryCount = 0;

        //if not valid reject
        if ($coupon->is_by_country) {
            if ($coupon->country_ids != null) {
                $validCountryIds = is_array($coupon->country_ids) ? $coupon->country_ids : explode(',', trim($coupon->country_ids));
                if (!in_array($countryId, $validCountryIds)) {
                    return [6, "coupon is not valid in this country"];
                } else {
                    $entryCount = $entryCount + 1;
                }
            }
        }


        //if not valid reject
        if ($coupon->is_by_city) {
            if ($coupon->city_ids != null) {
                $validCityIds = is_array($coupon->city_ids) ? $coupon->city_ids :  explode(',', trim($coupon->city_ids));
                if (!in_array($cityId, $validCityIds)) {
                    return [7, "coupon is not valid in this city"];
                } else {
                    $entryCount = $entryCount + 1;
                }
            }
        }

        $validSizeIds = [];
        if ($coupon->is_by_size) {
            if ($coupon->size_ids != null) {
                $validSizeIds = is_array($coupon->size_ids) ? $coupon->size_ids : explode(',', trim($coupon->size_ids));
                // products in cart
                foreach ($sizeIds as $sizeId) {
                    if (!in_array($sizeId, $validSizeIds)) {
                        return [8, "coupon is not valid for some sizes"];
                    } else {
                        $entryCount = $entryCount + 1;
                    }
                }
            }
        }


        $notApplicableProducts = [];
        // is applied for any products
        if (!$coupon->is_for_all) {

            $validProductIds = [];
            if ($coupon->is_by_product) {
                if ($coupon->product_ids != null) {
                    $validProductIds = is_array($coupon->product_ids) ? $coupon->product_ids : explode(',', trim($coupon->product_ids));
                    // products in cart
                    foreach ($productIds as $productId) {
                        if (!in_array($productId, $validProductIds)) {
                            $notApplicableProducts[] = $productId;
                            //                            return [8, "coupon is not valid for some products"];
                        } else {
                            $entryCount = $entryCount + 1;
                        }
                    }
                }
            }


            $validCategoryIds = [];
            if ($coupon->is_by_category) {
                if ($coupon->category_parent_ids != null) {
                    $validCategoryIds = is_array($coupon->category_parent_ids) ? $coupon->category_parent_ids : explode(',', trim($coupon->category_parent_ids));
                    // products in cart
                    foreach ($productIds as $productId) {
                        $product = Product::where('id', $productId)->active()->get()->first();
                        if (!in_array($product->category_id, $validCategoryIds)) {
                            $notApplicableProducts[] = $productId;
                            //                            return [9, "coupon is not valid for some category"];
                        } else {
                            $entryCount = $entryCount + 1;
                        }
                    }
                }
            }


            $validSubCategoryIds = [];
            if ($coupon->is_by_subcategory) {
                if ($coupon->category_child_ids != null) {
                    $validSubCategoryIds = is_array($coupon->category_child_ids) ? $coupon->category_child_ids : explode(',', trim($coupon->category_child_ids));
                    // products in cart
                    foreach ($productIds as $productId) {
                        $product = Product::where('id', $productId)->active()->get()->first();
                        if ($product->sub_category_id != null) {
                            if (!in_array($product->sub_category_id, $validSubCategoryIds)) {
                                $notApplicableProducts[] = $productId;
                                //                                return [10, "coupon is not valid for some subcategory"];
                            } else {
                                $entryCount = $entryCount + 1;
                            }
                        }
                    }
                }
            }
        }


        if ($coupon->for_clients_only) {
            if ($coupon->client_ids != null) {
                $clientId = auth()->user()->id;
                $validClientIds = is_array($coupon->client_ids) ? $coupon->client_ids : explode(',', trim($coupon->client_ids));
                if (!in_array($clientId, $validClientIds)) {
                    return [11, "coupon is not valid for this client"];
                } else {
                    $entryCount = $entryCount + 1;
                }
            }
        }


        if ($entryCount > 0 && count($notApplicableProducts) != 0)
            return [401, $coupon, $notApplicableProducts];
        else if ($entryCount > 0)
            return [400, $coupon];
        else
            return [500, "coupon is not valid"];
    }

    public static function isValidForCashier($coupon, $cart, $total, $countryId, $cityId)
    {
        $expire_at = Carbon::make($coupon->expire_at)->timestamp;
        $currentTimestamp = Carbon::now()->timestamp;
        $total = 0;

        if ((int)$expire_at < (int)$currentTimestamp) {
            return 0;
        }

        if ($coupon->min_applied_amount > $total)
            return 0;


        foreach ($cart['products'] as $key => $item) {
            $item = (object)$item;

            if (isset($item->total_price)) {
                $item_amount = $item->total_price;
            } else if (isset($item->price)) {
                $item_amount = $item->price * $item->quantity;
            } else if (isset($item->size_id)) {
                $size = Size::find($item->size_id);
                $item_amount = $size->sale_price * $item->quantity;
            }else{
                $item_amount = 0;
            }

            if ($coupon->is_for_all) {
                if ($coupon->is_percent) {
                    $total = $total + ($item_amount * $coupon->discount_amount_percent / 100);
                } else {
                    $total = $total + $coupon->discount_amount_percent;
                }
            } else {
                if ($coupon->is_by_size) {
                    if ($coupon->size_ids != null) {
                        $validSizeIds = is_array($coupon->size_ids) ? $coupon->size_ids : explode(',', trim($coupon->size_ids));
                        if (in_array($item->size_id, $validSizeIds)) {
                            if ($coupon->is_percent) {
                                $total = $total + ($item_amount * $coupon->discount_amount_percent / 100);
                            } else {
                                $total = $total + $coupon->discount_amount_percent;
                            }
                        }
                    }
                } else if ($coupon->is_by_product) {

                    if ($coupon->product_ids != null) {
                        $validProductIds = is_array($coupon->product_ids) ? $coupon->product_ids : explode(',', trim($coupon->product_ids));

                        if (in_array($item->product_id, $validProductIds)) {

                            if ($coupon->is_percent) {
                                $total = $total + ($item_amount * $coupon->discount_amount_percent / 100);
                            } else {
                                $total = $total + $coupon->discount_amount_percent;
                            }
                        }
                    }
                } else if ($coupon->is_by_country) {
                    if ($coupon->country_ids != null) {
                        $validCountryIds = is_array($coupon->country_ids) ? $coupon->country_ids : explode(',', trim($coupon->country_ids));
                        if (in_array($countryId, $validCountryIds)) {
                            if ($coupon->is_percent) {
                                $total = $total + ($item_amount * $coupon->discount_amount_percent / 100);
                            } else {
                                $total = $total + $coupon->discount_amount_percent;
                            }
                        }
                    }
                } else if ($coupon->is_by_city) {
                    if ($coupon->city_ids != null) {
                        $validCityIds = is_array($coupon->city_ids) ? $coupon->city_ids :  explode(',', trim($coupon->city_ids));
                        if (in_array($cityId, $validCityIds)) {
                            if ($coupon->is_percent) {
                                $total = $total + ($item_amount * $coupon->discount_amount_percent / 100);
                            } else {
                                $total = $total + $coupon->discount_amount_percent;
                            }
                        }
                    }
                }
            }
        }

        return $total;
    }

    public function product()
    {
        return $this->belongsToMany(Product::class);
    }

    public function category()
    {
        return  $this->belongsToMany(Category::class);
    }

    public function cart()
    {
        return  $this->belongsTo(Cart::class);
    }

    public function discountCities()
    {
        return $this->belongsToMany(City::class, 'discount_cities');
    }
}
