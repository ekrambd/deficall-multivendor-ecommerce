<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Session;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $appends = ['product_image','discount_price','convert_price','current_symbol','original_price','short_description'];

    protected $fillable = [
        'user_id',
        'category_id',
        'subcategory_id',
        'unit_id',
        'product_name',
        'slug',
        'product_price',
        'product_discount',
        'stock_qty',
        'description',
        'featured_image',
        'status',
        'admin_verify',
    ];

    // protected $casts = [
    //     'product_price' => 'decimal:2',
    //     'product_discount' => 'decimal:2',
    //     'stock_qty' => 'decimal:2',
    // ];

    public function carts()
    {
        return $this->hasMany(Cart::class);
    }
    
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function productVariants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function productdeliverycharge()
    {
        return $this->hasOne(Productdeliverycharge::class);
    }

    public function getProductImageAttribute()
    {
        $path = url('/').env('FILE_PATH_URL')."/".$this->featured_image;
        return $path;
    }

    public function getConvertPriceAttribute()
    {
        

        $getCurrency = Session::get('currency');

        $amount = currency()->$getCurrency;

        $price = $this->product_price * $amount;

        return $price;
    }

    public function getCurrentSymbolAttribute()
    {   
        
        //if(user()->device_token = )
        $getCurrency = Session::get('currency');
        if($getCurrency == 'bdt_rate'){
            return "৳";
        }elseif($getCurrency == 'usd_rate'){
            return "$";
        }elseif($getCurrency == 'jpn_rate'){
            return "¥";
        }elseif($getCurrency == 'uae_rate'){
            return "AED";
        }else{
            return "SAR"; 
        }
    }


    public function getShortDescriptionAttribute()
    {   
        $html = $this->description;
        $shortDescription = Str::limit(
            preg_replace('/\s+/', ' ', strip_tags($html)),
            180
        );

        return $shortDescription;
    }



    public function getOriginalPriceAttribute()
    {
        $getCurrency = Session::get('currency');

        $amount = currency()->$getCurrency;

        $price = $this->product_price * $amount;

        return $price;
    }


    public function getDiscountPriceAttribute()
    {   


        // $getCurrency = Session::get('currency');

        // $amount = currency()->$getCurrency;

        // $price = $this->product_price * $amount;

        // return $price;

        $item = Self::find($this->id);
        $original_price = $item->product_price; 
        $discount_rate = $item->product_discount/100; // % discount expressed as a decimal

        $discount_amount = $original_price * $discount_rate;

        $finalAmount = $item->product_price - $discount_amount;

        $getCurrency = Session::get('currency');

        $amount = currency()->$getCurrency;

        if($amount > 1)
        {
            return $finalAmount * $amount;
        }

        return $finalAmount;

        //return "0";
         
    }


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function whishlists()
    {
        return $this->hasMany(Whishlist::class);
    }

    public function subcategory()
    {
        return $this->belongsTo(Subcategory::class);
    }

}
