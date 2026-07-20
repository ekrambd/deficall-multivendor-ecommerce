<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OrderDetail;
use App\Models\ProductVariant;
use App\Models\DeliveryChargeSetting;

class Order extends Model
{
    use HasFactory; 

    protected $appends = ['status','current_symbol','weight_price','place_type','delivery_charge','order_status'];

    protected $fillable = [
        'user_id',
        'payment_method_id',
        'invoice_no',
        'date',
        'time',
        'timestamp',
        'sub_total',
        'vat_tax',
        'total',
        'user_name',
        'user_email',
        'user_phone',
        'user_address',
        'user_city',
        'user_country',
        'user_zipcode',
        'order_type'
    ];

    public function orderDetails()
    {
    	return $this->hasMany(OrderDetail::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getStatusAttribute()
    {
        $data = OrderDetail::where('order_id',$this->id)->where('user_id',user()->id)->pluck('status')->toArray();
        if(count($data) > 0)
        {
            $status = array_unique($data);
            return $status[0];
        }    
        
    }

    public function getOrderStatusAttribute()
    {
        $data = OrderDetail::where('order_id',$this->id)->pluck('status')->toArray();
        if(count($data) > 0)
        {
            $status = array_unique($data);
            return $status[0];
        }    
        
    }


    public function getCurrentSymbolAttribute()
    {
        $data = OrderDetail::where('order_id',$this->id)->where('user_id',user()->id)->pluck('current_symbol')->toArray();
        if(count($data) > 0)
        {
            $symbol = array_unique($data);
            return $symbol[0];
        }    
        
    }

    public function getWeightPriceAttribute()
    {
        $ids = OrderDetail::where('order_id',$this->id)->where('user_id',user()->id)->where('product_variant_id','!=',NULL)->where('variant_id',6)->pluck('product_variant_id')->toArray();

        //return count($ids);

        $sumWeight = ProductVariant::whereIn('id',$ids)->sum('variant_value');
        //return $sumWeight;
        $charge = DeliveryChargeSetting::where('user_id',user()->id)->first();
        if($sumWeight > 0)
        {
            //$count = count($data);
            $sum = $charge->per_weight_charge * $sumWeight; 
            return strval($sum);
        }

        return "0";

        
    }

    public function getPlaceTypeAttribute()
    {
        $data = OrderDetail::where('order_id',$this->id)->where('user_id',user()->id)->pluck('place_type')->toArray();
        if(count($data) > 0)
        {
            $type = array_unique($data);
            return $type[0];
        }

        return NULL;    
        
    }

    // public function getDeliveryChargeAttribute()
    // {
    //     $data = OrderDetail::where('order_id',$this->id)->where('user_id',user()->id)->pluck('place_type')->toArray();

    //     if(count($data) > 0){
    //         $type = array_unique($data);
    //         $type = $type[0];
    //         $charge = DeliveryChargeSetting::where('user_id',user()->id)->first();
    //         if($type == 'inside'){
    //             $percantageAmt = $charge->inside_city_charge / 100;
    //             $amt = $this->total * $percantageAmt;
    //         }else{
    //             $percantageAmt = $charge->outside_city_charge / 100;
    //             $amt = $this->total * $percantageAmt;
    //         }
    //         return $amt;
    //     }

    //     return "0";   
        
    // }

    public function getDeliveryChargeAttribute()
    {
        $type = OrderDetail::where('order_id', $this->id)
            ->where('user_id', user()->id)
            ->pluck('place_type')
            ->unique()
            ->first();

        if (!$type) {
            return 0;
        }

        $charge = DeliveryChargeSetting::where('user_id', user()->id)->first();

        if (!$charge) {
            return 0;
        }

        if ($type == 'inside') {

            return ($this->total * $charge->inside_city_charge) / 100;

        }

        return ($this->total * $charge->outside_city_charge) / 100;
    }

}
