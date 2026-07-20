<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderDetail extends Model
{
    use HasFactory;


    protected $fillable = [
        'order_id',
        'product_id',
        'variant_id',
        'user_id',
        'product_variant_id',
        'payment_method_id',
        'purchase_price',
        'purchase_discount',
        'current_symbol',
        'qty',
        'place_type',
        'status'
    ];

    public function order()
    {
    	return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(Variant::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
