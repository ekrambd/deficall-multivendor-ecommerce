<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Session;
use App\Models\Cart;
use App\Models\PaymentMethod;
use App\Models\DeliveryChargeSetting;

class CheckoutController extends Controller
{
    public function checkout()
    {   
        $sum = Cart::selectRaw('MAX(unit_total) as total')
		    ->where('cart_session_id', Session::get('cart_session_id'))
		    ->groupBy('product_id')
		    ->pluck('total')
		    ->sum();
		    
		if($sum == 0)
		{
		    return back();
		}

         $charge = DeliveryChargeSetting::find(1);

         $perAmt = $charge->inside_city_charge / 100;

         $delivery_charge = $sum * $perAmt;

         //return $sum * ($charge->inside_city_charge / 100);

        $paymentMethods = PaymentMethod::get();
    	return view('checkout',compact('sum','paymentMethods','charge', 'delivery_charge','charge'));
    }
}
