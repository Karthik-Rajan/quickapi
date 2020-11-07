<?php

namespace App\Http\Controllers\V1\Order\User;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use App\Helpers\Helper;
use App\Models\Order\Store;
use App\Models\Order\Cuisine;
use App\Models\Common\UserAddress;
use App\Models\Common\RequestFilter;
use App\Models\Order\StoreItemAddon;
use App\Models\Order\StoreItem;
use App\Models\Order\StoreCart;
use App\Models\Common\Rating;
use App\Models\Common\User;
use App\Models\Common\State;
use App\Models\Order\StoreCityPrice;
use App\Models\Order\StoreOrderDispute;
use Auth;
use DB;
use Carbon\Carbon;
use App\Models\Common\Setting;
use App\Models\Order\StoreCartItemAddon; 
use App\Models\Common\Promocode;
use App\Models\Common\PromocodeUsage;
use App\Models\Order\StoreOrder;
use App\Models\Order\StoreOrderInvoice;
use App\Models\Order\StoreOrderStatus;
use App\Models\Common\AdminService;
use App\Models\Common\UserRequest;
use App\Models\Common\PaymentLog;
use App\Services\PaymentGateway;
use App\Models\Common\Card;
use App\Services\Transactions;
use App\Services\SendPushNotification;
use App\Services\V1\Common\UserServices;
use App\Services\V1\Order\Order;
use App\Traits\Actions;
use App\Models\Common\CompanyCity;
use App\Models\Common\Notifications;
use App\Notifications\WebPushNotification;
use Notification;
use App\Models\Common\Admin;
class HomeController extends Controller
{
    use Actions;
	//Store Type
    public function store_list(Request $request,$id)
    {
        $user = Auth::guard('user')->user();

        $company_id = $user ? $user->company_id : 1;

        $settings = json_decode(json_encode(Setting::where('company_id', $company_id)->first()->settings_data));

        $city_id = $user ? $user->city_id : $request->city_id;

		$store_list_all = Store::with('categories','storetype','StoreCusinie','StoreCusinie.cuisine')->where('company_id',$company_id)->where('store_type_id',$id)->select('id','store_type_id','company_id','store_name','store_location','latitude','longitude','picture','offer_min_amount','estimated_delivery_time','free_delivery','is_veg','rating','offer_percent');
        $store_list_all->whereHas('storetype',function($q) use ($request){
                $q->where('status',1);

            });
		if($request->has('filter') && $request->filter!=''){
			$store_list_all->whereHas('StoreCusinie',function($q) use ($request){
				$q->whereIn('cuisines_id',[$request->filter]);

			});
		}
		if($request->has('qfilter') && $request->qfilter!=''){
			if($request->qfilter=='non-veg'){
				$store_list_all->where('is_veg','Non Veg');
			}
			if($request->qfilter=='pure-veg'){
				$store_list_all->where('is_veg','Pure Veg');
			}
			if($request->qfilter=='freedelivery'){
				$store_list_all->where('free_delivery','1');
			}
			
		}
		if($request->has('latitude') && $request->has('latitude') !='' && $request->has('longitude') && $request->has('longitude')!='')
        {
            $longitude = $request->longitude;
            $latitude = $request->latitude;
            $distance = $settings->order->store_search_radius;
            // config('constants.store_search_radius', '10');
            if($distance>0){
                $store_list_all->select('*',\DB::raw("(6371 * acos( cos( radians('$latitude') ) * cos( radians(latitude) ) * cos( radians(longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(latitude) ) ) ) AS distance"))
                    ->whereRaw("(6371 * acos( cos( radians('$latitude') ) * cos( radians(latitude) ) * cos( radians(longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(latitude) ) ) ) <= $distance");
            }
        }

        $store_list_all->where('status',1);
        $store_list = $store_list_all->get();
        $store_list->map(function($shop) {
            if($shop->StoreCusinie->count()>0){
                foreach($shop->StoreCusinie as $cusine){
                    $cusines_list [] = $cusine->cuisine->name;
                }
            }else{
               $cusines_list=[]; 
            }
            $cuisinelist = implode($cusines_list,',');
            $shop->cusine_list = $cuisinelist;
            $shop->shopstatus = $this->shoptime($shop->id);
            return $shop;
        });
        return Helper::getResponse(['data' => $store_list]);
	}
	//Service Sub Category
	public function cusine_list(Request $request,$id) {

        $user = Auth::guard('user')->user();

        $company_id = $user ? $user->company_id : 1;

		$cusine_list = Cuisine::where('company_id',$company_id)->where('store_type_id',$id)
									 ->get();
        return Helper::getResponse(['data' => $cusine_list]);
	}
	//store details 
	public function store_details(Request $request,$id){

        $user = Auth::guard('user')->user();

        $company_id = $user ? $user->company_id : 1;

        $settings = json_decode(json_encode(Setting::where('company_id', $company_id)->first()->settings_data));

        $store_details = Store::with(['categories','storetype',
        'storecart' =>function($query) use ($request, $user){
            $query->where('user_id',$user ? $user->id : null);
        },'products'=>function($query) use ($request){
             $query->where('status','!=',2);
			if($request->has('filter') && $request->filter!=''){
					$query->where('store_category_id',$request->filter);
			}
			if($request->has('search') && $request->search!=''){
				$query->where('item_name','LIKE', '%' . $request->search . '%' );
			}
			if($request->has('qfilter') && $request->qfilter!=''){
				if($request->qfilter=='non-veg'){
					$query->where('is_veg','Non Veg');
				}
				if($request->qfilter=='pure-veg'){
					$query->where('is_veg','Pure Veg');
				}
				if($request->qfilter=='discount'){
					$query->where('item_discount','<>','');
				}
			}
           
        },'products.unit','products.itemsaddon','products.itemsaddon.addon'
        ,'products.itemcart' =>function($query) use ($request, $user){
            $query->where('user_id',$user ? $user->id : null);
        }])
        ->whereHas('storetype',function($q) use ($request){
                $q->where('status',1);
        });
        $store_details->where('status',1)->where('company_id',$this->company_id);
		if($request->has('latitude') && $request->has('latitude')!='' && $request->has('longitude') && $request->has('longitude')!='')
        {
            $longitude = $request->longitude;
            $latitude = $request->latitude;
            $distance = $settings->order->store_search_radius;
            // config('constants.store_search_radius', '10');
                $store_details->select('id','store_type_id','company_id','store_name','currency_symbol','store_location','latitude','longitude','picture','offer_min_amount','estimated_delivery_time','free_delivery','is_veg','rating','offer_percent',\DB::raw("(6371 * acos( cos( radians('$latitude') ) * cos( radians(latitude) ) * cos( radians(longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(latitude) ) ) ) AS distance"),'bestseller','bestseller_month')
                    ->whereRaw("(6371 * acos( cos( radians('$latitude') ) * cos( radians(latitude) ) * cos( radians(longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(latitude) ) ) ) <= $distance");
        }else{
        	$store_details->select('id','store_type_id','company_id','store_name','currency_symbol','store_location','latitude','longitude','picture','offer_min_amount','estimated_delivery_time','free_delivery','is_veg','rating','offer_percent','bestseller','bestseller_month');
        }
		$store_detail = $store_details->find($id);
        $product_timeline = $settings->order->product_timeline;
        $store_orders= $store_detail->orderInvoices->map(function($invoice){
            return $invoice->items;
        })->collapse();

            $store_detail->products->map(function($products) use ($store_orders,$store_detail,$product_timeline) {
                $new_product = $products->where('id',$products->id)->where( 'created_at', '>', Carbon::now()->subDays($product_timeline))->first();
               
                if($new_product)
                {
                    $products->shop_status = "NEW";
                
                }else{
                    $product_count=$store_orders->where('store_item_id',$products->id)->count();
                    if($product_count > $store_detail->bestseller)
                        $products->shop_status = "BESTSELLER";
                }
               
               $products->offer=0;
               if($products->item_discount)
                 $products->offer=1;

                if($products->status == 0)
                    $products->item_status="Item Not Available";
                else if($products->status == 1)
                    $products->item_status="Available";

                if($products->item_discount_type=="PERCENTAGE"){
                     $products->product_offer=($products->item_price-($products->item_discount/100)*$products->item_price);
                    
                 }else if($products->item_discount_type=="AMOUNT"){
                    $products->product_offer=$products->item_price-$products->item_discount;
                   
                 }
                 $products->product_offer=($products->product_offer>0) ? $products->product_offer:0;

               
				$products->itemsaddon->filter(function($addon) {
		        	$addon->addon_name = $addon->addon->addon_name;;
		        	unset($addon->addon);
		        	return $addon;
		    	});
             
                return $products;

            });
        $totalcartprice =0;
        $store_detail->totalstorecart = count($store_detail->storecart);
        // foreach($store_detail->storecart as $cart){
        //     $totalcartprice = $totalcartprice + $cart->total_item_price;
        // }
        unset($store_detail->orderInvoices);
		unset($store_detail->storecart);
		if(!empty($store_detail)){
			$store_detail->shopstatus = $this->shoptime($id);
		}
        $store_detail->usercart = count($this->totalusercart());
        $store_detail->totalcartprice =$this->totalusercart()->sum('total_item_price');
		return Helper::getResponse(['data' => $store_detail]);
	}

	public function shoptime($id){
		$Shop = Store::find($id);
        $day_short = strtoupper(\Carbon\Carbon::now()->format('D'));

        if($shop_timing = $Shop->timings->where('store_day','ALL')->pluck('store_start_time','store_end_time')->toArray()){
        }else{
            $shop_timing = $Shop->timings->where('store_day',$day_short)->pluck('store_start_time','store_end_time')->toArray();
        }    
        if(!empty($shop_timing)){
            $end = key($shop_timing);
            $current_time = \Carbon\Carbon::now();  
            $start_time = \Carbon\Carbon::parse($shop_timing[$end]); 
            $end_time = \Carbon\Carbon::parse($end);

            if($start_time > $end_time) $start_time->subDay();
            
            if($current_time->between($start_time, $end_time)){
                return $timeout_class = 'OPEN';
            }else{
                return $timeout_class = 'CLOSED'; 
            }
        }else{
            return 'CLOSED';
        }

    }

    public function useraddress(Request $request){
        $CartStoreDetails = StoreCart::with('store')->select('store_id')->where('user_id', $this->user->id)->first();
        if($CartStoreDetails != null){
            $storeId = $CartStoreDetails->store_id;
            $distance = isset($this->settings->order->store_search_radius)?$this->settings->order->store_search_radius:'';
            $latitude = $CartStoreDetails->store->latitude;
            $longitude = $CartStoreDetails->store->longitude;
            $user_address = UserAddress::select(
                DB::raw("(CASE WHEN ((6371 * acos( cos( radians('$latitude') ) * cos( radians(latitude) ) * cos( radians(longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(latitude) ) ) ) <= $distance)  THEN 1 ELSE 0 END) AS is_nearby"),
                DB::Raw("(6371 * acos( cos( radians('$latitude') ) * cos( radians(latitude) ) * cos( radians(longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(latitude) ) ) ) AS distance"),'id','user_id','company_id','address_type','map_address','latitude','longitude','flat_no','street','title')
            ->where('user_id',$this->user->id)
            ->where('company_id',$this->company_id)
            // ->select('id','user_id','company_id','address_type','map_address','latitude','longitude','flat_no','street','title')
            ->get();
        }else{
            $user_address = UserAddress::where('user_id',$this->user->id)->where('company_id',$this->company_id)
        ->select('id','user_id','company_id','address_type','map_address','latitude','longitude','flat_no','street','title')->get();
        }
        return Helper::getResponse(['data' => $user_address]);
    }

    public function show_addons(Request $request,$id){
    	$item_addons =$Item= StoreItem::with(['itemsaddon','itemsaddon.addon','itemcartaddon'])->where('company_id',$this->company_id)->select('id','item_name','item_price','item_discount_type','item_discount')->find($id);
            $itemcartaddon = $item_addons->itemcartaddon->pluck('store_item_addons_id','store_item_addons_id')->toArray();
            if($item_addons->item_discount_type=="PERCENTAGE"){
             $item_addons->item_price=$Item->item_price = $Item->item_price-(($Item->item_discount/100)*$Item->item_price);   
            } else if($Item->item_discount_type=="AMOUNT"){
              $item_addons->item_price = $Item->item_price=$Item->item_price-($Item->item_discount);     
            }
              $item_addons->item_price = $Item->item_price=$Item->item_price >0 ?$Item->item_price:0;
              //dd($item_addons->item_price);
            
            unset($item_addons->itemcartaddon);
            $item_addons->itemcartaddon = $itemcartaddon;
		    $item_addons->itemsaddon->map(function($da) {
		        $da->addon_name = $da->addon->addon_name;;
		        unset($da->addon);
		        return $da;
			});
        return Helper::getResponse(['data' => $item_addons]);
    }

    public function cart_addons(Request $request,$id){
        $cart = StoreCart::find($id);
        $item_addons = $Item=StoreItem::with(['itemsaddon','itemsaddon.addon','itemcartaddon' => function($query) use ($cart) {
            $query->where('store_cart_id', $cart->id);
        }, 'itemcart'])->where('company_id',Auth::guard('user')->user()->company_id)->select('id','item_name','item_price','item_discount_type','item_discount')->find($cart->store_item_id);
            $itemcartaddon = $item_addons->itemcartaddon->pluck('store_item_addons_id','store_item_addons_id')->toArray();
             if($item_addons->item_discount_type=="PERCENTAGE"){
             $item_addons->item_price=$Item->item_price = $Item->item_price-(($Item->item_discount/100)*$Item->item_price);   
            } else if($Item->item_discount_type=="AMOUNT"){
              $item_addons->item_price = $Item->item_price=$Item->item_price-($Item->item_discount);     
            }
              $item_addons->item_price = $Item->item_price=$Item->item_price >0 ?$Item->item_price:0;
            unset($item_addons->itemcartaddon);
            $item_addons->itemcartaddon = $itemcartaddon;
            $item_addons->itemsaddon->map(function($da) {
                $da->addon_name = $da->addon->addon_name;
                unset($da->addon);
                return $da;
            });
        return Helper::getResponse(['data' => $item_addons]);
    }


    public function addcart(Request $request){
        $this->validate($request, [
            'item_id'    => 'required',
            'qty' => 'required'
        ]);
        
        $addonStatus = true;
        $quantity = $request->qty;
        $cart = StoreCart::where('user_id',$this->user->id)->orderBy('id', 'desc')->first();
        $Item = StoreItem::with('unit')->find($request->item_id);
        $checkAddons = $request->addons ? @explode(',', $request->addons) : [];
        if(!empty($cart)){
            if($Item->store_id!=$cart->store_id){
                StoreCart::where('user_id',$this->user->id)->delete();
                $cart = 0;
            }else{
                $response = $this->findCart($request, $checkAddons);
                $addonStatus = $response['addonStatus'];
                ($request->repeat ? $checkAddons = $response['totalAddons'] : '');
                $cart = $response['cart'] ? StoreCart::where('user_id',$this->user->id)->where('store_item_id',$request->item_id)->where('id', $response['cart'])->first() : 0;
                $quantity = $request->repeat ? (($request->cart_id) ? $quantity : $cart->quantity + 1) : ($cart ? $cart->quantity + 1 : $quantity);
            }
        }

        if(!$cart) {
            $cart = new StoreCart();
        }

        if(trim($request->customize)) {
            $oldCart = StoreCart::where('user_id',$this->user->id)->where('id', $request->cart_id)->first();
            $quantity = $oldCart->quantity;
            if($oldCart->id != $cart->id) {
                $oldCart->delete();
                $quantity = $oldCart->quantity + $cart->quantity;
            } else {
                $quantity = $oldCart->quantity + 1;
            }
        }

        $cart->quantity = $quantity;
        $cart->item_quantity = $Item->quantity ? $Item->quantity : '';
        $cart->unit = $Item->unit ? $Item->unit->name : '';
        $cart->user_id = $this->user->id;
        $cart->store_id = $Item->store_id;
        $cart->store_item_id = $request->item_id;
        if($Item->item_discount_type=="PERCENTAGE"){
         $cart->item_price=$Item->item_price = $Item->item_price-(($Item->item_discount/100)*$Item->item_price);   
        } else if($Item->item_discount_type=="AMOUNT"){
          $cart->item_price = $Item->item_price=$Item->item_price-($Item->item_discount);     
        }
        $cart->item_price = $Item->item_price=$cart->item_price >0 ? $cart->item_price:0;
        
        $cart->company_id = $this->company_id;
        $cart->note = $request->note;
        $cart->total_item_price = ($quantity)*($Item->item_price);
        $cart->save();
        $tot_item_addon_price = 0;
        $cart = StoreCart::find($cart->id);
        if($addonStatus){
            if($request->customize) {
                StoreCartItemAddon::where('store_cart_id', $oldCart->id)->delete();
            } else {
                StoreCartItemAddon::where('store_cart_id', $cart->id)->delete();
            }
        }
        if(count($checkAddons) > 0){
            $addons = StoreItemAddon::whereIn('id',$checkAddons)->pluck('price','id')->toArray();
            foreach($addons as $key => $item){ 
                if(in_array($key, $checkAddons)){
                    $cartaddon = StoreCartItemAddon::where('store_cart_id', $cart->id)->where('store_item_addons_id', $key)->where('store_cart_item_id', $cart->store_item_id)->first();
                    $cartaddon = $cartaddon ? $cartaddon : new StoreCartItemAddon();
                    $cartaddon->store_cart_item_id = $cart->store_item_id;
                    $cartaddon->store_item_addons_id = $key;
                    $cartaddon->store_cart_id = $cart->id;
                    $cartaddon->addon_price = $item;
                    $cartaddon->company_id = $this->company_id;
                    $cartaddon->save();
                    $tot_item_addon_price += $item;
                }
            }
        }
        $cart->tot_addon_price = $quantity * $tot_item_addon_price;        

        $cart->total_item_price += ($quantity * $tot_item_addon_price);
        $cart->save();
        return $this->viewcart($request);
    }

    public function findCart($request, $checkAddons) {
        $cart = 0;
        $status = true;
        $totalAddons = [];

        if($request->repeat) {
            if($request->cart_id) {
                $cartId = StoreCart::where('user_id', Auth::guard('user')->user()->id)->where('store_item_id', $request->item_id)->where('id', $request->cart_id)->orderBy('id', 'desc')->first();
            } else {
                $cartId = StoreCart::where('user_id', Auth::guard('user')->user()->id)->where('store_item_id', $request->item_id)->orderBy('id', 'desc')->first();
            }
            $totalAddons = StoreCartItemAddon::where('store_cart_id', $cartId->id)->pluck('store_item_addons_id')->toArray();
            $cart = $cartId->id;
            $status = false;
            $response = [
                'cart' => $cart,
                'addonStatus' => $status,
                'totalAddons' => $totalAddons
            ];
            return $response;
        }
        
        $cartIds = StoreCart::where('user_id', Auth::guard('user')->user()->id)->where('store_item_id', $request->item_id)->pluck('id');
        if(trim($request->cart_id) && !$request->customize) {
            $cartIds = StoreCart::where('user_id', Auth::guard('user')->user()->id)->where('store_item_id', $request->item_id)->where('id', $request->cart_id)->pluck('id');
        }

        foreach($cartIds as $cartId) {
            $totalAddons = [];
            $addonStatus = 0;
            $addonCheck = StoreCartItemAddon::where('store_cart_id', $cartId)->count();
            if($addonCheck == 0 && count($checkAddons) == 0) {
                $cart = $cartId;
                break;
            }

            if($addonCheck == count($checkAddons)) {
                
                foreach($checkAddons as $checkAddon) {

                    $add = StoreCartItemAddon::where('store_cart_id', $cartId)->where('store_item_addons_id', $checkAddon)->first();

                    if($add) {
                        $totalAddons[] = $checkAddon;
                        $addonStatus++;
                    } else {
                        $addonStatus--;
                    }
                    if($addonStatus == count($checkAddons)) {
                        $status = false;
                        break;
                    }
                }

                if($addonStatus == count($checkAddons)) {
                    $cart = $cartId;
                    break;
                }

            }
        }

        if($request->repeat) {
            $status = false;
        }

        if($request->customize) {
            $addons = StoreCartItemAddon::where('store_cart_id', $request->cart_id)->pluck('store_item_addons_id')->toArray();
            if(count($addons) != count($checkAddons)) {
                $status = true;
            } else {
                foreach($addons as $addon) {
                    if(!in_array($addon, $checkAddons)) {
                        $status = true;
                    }
                }
            }
        }

        $response = [
            'cart' => $cart,
            'addonStatus' => $status,
            'totalAddons' => $totalAddons
        ];
        return $response;
    }

    public function viewcart(Request $request){
      
    	
        try{
        $CartItems  = StoreCart::with('product','product.unit','product.itemsaddon','product.itemsaddon.addon','store','store.storetype','store.StoreCusinie','store.StoreCusinie.cuisine','cartaddon','cartaddon.addon.addon')
        ->where('company_id',$this->company_id)
        ->where('user_id',$this->user->id)->get();

        	$tot_price = 0;
        	$discount = 0;
        	$tax  =0; 
        	$promocode_amount = 0; 
        	$total_net = 0; 
        	$total_wallet_balance = 0;
        	$payable = 0;
            $discount_promo = 0;
            $cusines_list = [];
            if(!$CartItems->isEmpty()) {
                if($CartItems[0]->store->StoreCusinie->count()>0){
                    foreach($CartItems[0]->store->StoreCusinie as $cusine){
                        $cusines_list [] = $cusine->cuisine->name;
                    }
                }
                $store_type_id=$CartItems[0]->store->store_type_id;
                $city_id=$CartItems[0]->store->city_id;
        		$cityprice=StoreCityPrice::where('store_type_id',$store_type_id)->where('company_id',$this->company_id)
                ->where('city_id',$city_id)
                ->first();
                foreach($CartItems as $Product){    
                    $tot_qty = $Product->quantity;
                    //$Product->quantity. '--' .$Product->product->item_price;
                    // if($Product->product->item_discount_type=="PERCENTAGE"){
                    //      $Product->product->item_price= $Product->product->item_price-(($Product->product->item_discount/100)*$Product->product->item_price);
                    // } else if($Product->product->item_discount_type=="AMOUNT"){
                    //      $Product->product->item_price= $Product->product->item_price-$Product->product->item_discount;
                    // }
                       $Product->product->item_price=$Product->item_price > 0 ? $Product->item_price:0;
                    $tot_price += $Product->quantity * $Product->item_price;
                    $tot_price_addons = 0;

                    if(count($Product->cartaddon)>0){
                        foreach($Product->cartaddon as $Cartaddon){
                           
                           $tot_price_addons +=$Cartaddon->addon_price; 
                        }
                    }
                    $tot_price += $tot_qty*$tot_price_addons; 
                    
                }
                //dd($tot_price);
                $tot_price = $tot_price;
                $net = $tot_price;
                $store_tax = ($net*$Product->store->store_gst/100);
                if($Product->store->offer_percent){
                    if($tot_price > $Product->store->offer_min_amount){
                       //$discount = roundPrice(($tot_price*($Order->shop->offer_percent/100)));
                       $discount = ($tot_price*($Product->store->offer_percent/100));
                       //if()
                       $net = $tot_price - $discount;
                    }
                }
                $total_wallet_balance = 0;
                
                $store_package_charge = $Product->store->store_packing_charges;

                $total_net = ($net+$store_tax+$store_package_charge);

            if(!empty($request->user_address_id))
            {

                $address_details = UserAddress::select('id','latitude','longitude','map_address','flat_no','street')->find($request->user_address_id);

                if($address_details) {
                    $details = "https://maps.googleapis.com/maps/api/directions/json?origin=".$address_details->latitude.",".$address_details->longitude."&destination=".$Product->store->latitude.",".$Product->store->longitude."&mode=driving&key=".$this->settings->site->browser_key;
               
                    $json = Helper::curl($details);
                    $details = json_decode($json, TRUE);
                
                    $route_key = (count($details['routes']) > 0) ? $details['routes'][0]['overview_polyline']['points'] : '';
            
                    $distance = (count($details['routes']) > 0) ? ($details['routes'][0]['legs'][0]['distance']['value'] ) : 0;
                    if($this->settings->site->unit_measurement=='Kms')
                        $distance = round($distance/1000,1); //TKM
                    else
                        $distance = round($distance/1609.344,1); //TMi

                    
                }
                if($cityprice){
                    if($Product->store->free_delivery==1 && $request->order_type == 'DELIVERY'){
                            
                            if($total_net >= $Product->store->free_delivery_limit){
                                $free_delivery = 0;
                            } else{
                                  $free_delivery = $cityprice->delivery_charge + ( ($distance - $cityprice->base_distance) * $cityprice->distance_price); 
                                $total_net +=  $free_delivery;
                                
                            }
                    }else{
                            if($request->order_type == 'TAKEAWAY'){
                                $free_delivery = 0;
                            } else{
                                $free_delivery = $cityprice->delivery_charge + ( ($distance - $cityprice->base_distance) * $cityprice->distance_price); 
                                
                                $total_net +=  $free_delivery;
                            }   
                    }
                }else{
                        $free_delivery = 0;
                    }
            }else{
                 $free_delivery = ($request->order_type == 'TAKEAWAY') ?  0 : null;
            }

            	

                $promocode_id = 0;
                $discount_promo = 0;
                if($request->has('promocode_id') && $request->promocode_id !='') { 
                        $find_promo = Promocode::where('id',$request->promocode_id)->first();
                        if($find_promo != null){
                            $promocode_id = $find_promo->id;
                            $my_promo_discount = Helper::decimalRoundOff($total_net*($find_promo->percentage/100));
                            if($my_promo_discount>$find_promo->max_amount){
                                $discount_promo = Helper::decimalRoundOff($find_promo->max_amount);
                                $total_net = $total_net - $find_promo->max_amount;
                            }else{
                                $discount_promo = Helper::decimalRoundOff($my_promo_discount);
                                $total_net = $total_net - $my_promo_discount;
                            }
                        }
                }
                $total_net = $payable = $total_net;
                if($request->wallet && $request->wallet != "" && $request->wallet==1){
                    if(Auth::guard('user')->user()->wallet_balance > $total_net){
                        $total_wallet_balance_left = Auth::guard('user')->user()->wallet_balance - $total_net;
                        
                        $total_wallet_balance = $total_net;
                        $payable = 0;
                        
                    }else{ 
                        //$total_net = $total_net - $request->user()->wallet_balance;
                        $total_wallet_balance = Auth::guard('user')->user()->wallet_balance;
                        if($total_wallet_balance >0){
                            $payable = ($total_net - $total_wallet_balance);
                        }
                    }
                }

                //print($CartItems);exit;
                $CartItems->map(function($data) {

                    if(count($data->product->itemsaddon)>0){
                        $data->product->itemsaddon->filter(function($itmad) {
                         $itmad->addon_name = $itmad->addon->addon_name;
                        unset($itmad->addon);
                        return $itmad;
                        });
                    }

                   if(count($data->cartaddon)>0){
                    	$data->cartaddon->filter(function($da) {
                         $da->addon_name = $da->addon->addon->addon_name;
    			        unset($da->addon);
    			        return $da;
    			    	});
                    }
			    	return $data;
				});


                $Cart = [
                'delivery_charges' => $free_delivery,
                'delivery_free_minimum' => 0,
                'tax_percentage' => 0,
                'carts' => $CartItems,
                'total_price' => round($total_net),
                'shop_discount' => round($discount,2),
                'store_type' => $CartItems[0]->store->storetype->category,
                'total_item_price' => $tot_price,
                //'tax' => round($tax,2),
                'promocode_id' => $promocode_id,
                'promocode_amount' => round($discount_promo,2),
                'net' => round($total_net,2),
                'wallet_balance' => round($total_wallet_balance,2),
                'payable' => round($payable),
                'total_cart' => count($CartItems),
                'shop_gst' => $CartItems[0]->store->store_gst,
                'shop_gst_amount' => round($store_tax,2),
                'shop_package_charge' =>  $store_package_charge,
                'store_id' => $CartItems[0]->store->id,
                'store_commision_per' => $CartItems[0]->store->commission,
                'shop_cusines' => implode($cusines_list,','),
                'rating' => ($CartItems[0]->store->rating)?$CartItems[0]->store->rating:0.00,
                'user_wallet_balance' => Auth::guard('user')->user()->wallet_balance,
                'user_currency' => Auth::guard('user')->user()->currency_symbol,
            ];
        }else{

            $Cart = [
                'delivery_charges' => 0,
                'base_distance' => 0,
                'distance_price' => 0,
                'delivery_free_minimum' => 0,
                'tax_percentage' => 0,
                'carts' => [],
                'total_price' => round($tot_price,2),
                'shop_discount' => round($discount,2),
                'store_type' => '',
                'total_item_price' => $tot_price,
                //'tax' => round($tax,2),
                'promocode_amount' => round($promocode_amount,2),
                'net' => round($total_net,2),
                'wallet_balance' => round($total_wallet_balance,2),
                'payable' => round($payable),
                'total_cart' => count($CartItems),
                'shop_gst' => 0,
                'shop_gst_amount' => 0.00,
                'shop_package_charge' =>  0,
                'store_id' => 0,
                'store_commision_per' => 0,
                'total_cart' => count($CartItems),
                'shop_cusines' => '',
                'rating' =>0.00,
                'user_wallet_balance' => Auth::guard('user')->user()->wallet_balance,
                'user_currency' => Auth::guard('user')->user()->currency_symbol,
                ];

        }

        if($request->has('checkout_status') && $request->checkout_status == 1){
            return $Cart;
        }

        return Helper::getResponse(['data' => $Cart]);
        }catch(ModelNotFoundException $e){
			return Helper::getResponse(['status' => 500, 'message' => trans('api.provider.provider_not_found'), 'error' => trans('api.provider.provider_not_found') ]);
		} catch (Exception $e) {
			return Helper::getResponse(['status' => 500, 'message' => trans('api.provider.provider_not_found'), 'error' => trans('api.provider.provider_not_found') ]);
		}
    }

    public function removecart(Request $request){
    	$this->validate($request, [
			'cart_id'    => 'required'
		]);

        $cart = StoreCart::find($request->cart_id)->delete();
        $cart_addon = StoreCartItemAddon::where('store_cart_id',$request->cart_id)->delete();
        return $this->viewcart($request);
    }

     public function totalremovecart(Request $request){
        $cart = StoreCart::where('user_id',Auth::guard('user')->user()->id)->delete();
         return Helper::getResponse(['data' => $cart]); 
    }

    public function totalusercart(){

        $user = Auth::guard('user')->user();

        $company_id = $user ? $user->company_id : 1;

    	$CartItems = StoreCart::select('total_item_price')->where('company_id',$company_id)->where('user_id',$user ? $user->id : null)->get();
    	return $CartItems;
    }


    public function promocodelist(Request $request){
     
        $Promocode = Promocode::where('status','ADDED')->where('service','ORDER')
        ->where('company_id', $this->company_id);
        if($request->has('total_amount'))
             $Promocode->Where('min_amount','<=',$request->total_amount);

        $Promocode->Where(function ($query)use ($request) {
            $query->whereNull('store_id');
            if($request->has('store_id'))
                $query->orWhere('store_id',$request->store_id);
                
        });
     
        $Promocode->where('expiration','>=',date("Y-m-d H:i"));
        
        $Promocode->Where(function ($query) {
            $query->whereNull('user_id');
            $query->orWhere('user_id',$this->user->id);    
        });
        $Promocode->Where(function ($query) {
            $query->whereNull('startdate');
            $query->orwhere('startdate','<=',Carbon::parse($this->user->created_at)->format("Y-m-d H:i"));
        });
        $Promocode->whereRaw("user_limit > (select ifnull((SELECT COUNT(*) from promocode_usages where promocode_id = promocodes.id and user_id = ".$this->user->id." group by promocode_id), 0) )");

        $Promocodes = $Promocode->get();

        return Helper::getResponse(['data' => $Promocodes]);
    }
    public function orderDeliveryTime($id){
     try {
   
            $store_order = StoreOrder::with(['provider','deliveryaddress'])->findOrFail($id);
            if($store_order->status == 'STARTED' || $store_order->status == 'PICKEDUP' || $store_order->status == 'REACHED')
            {
                $details = "https://maps.googleapis.com/maps/api/directions/json?origin=".$store_order->deliveryaddress->latitude.",".$store_order->deliveryaddress->longitude."&destination=".$store_order->provider->latitude.",".$store_order->provider->longitude."&mode=driving&key=".$this->settings->site->browser_key;
                       
                $json = Helper::curl($details);
                $details = json_decode($json, TRUE);
                $route_key = (count($details['routes']) > 0) ? $details['routes'][0]['overview_polyline']['points'] : '';

                $duration = (count($details['routes']) > 0) ? ($details['routes'][0]['legs'][0]['duration']['text'] ) : 0;

                return Helper::getResponse(['data' => $duration]);
            }

            return Helper::getResponse(['status' =>422, 'message' => "Provider Not Assigned" ]);
        } catch (Exception $e) {  
            return Helper::getResponse(['status' => 500, 'error' => $e->getMessage()]);
        }
     
    }
    public function checkout(Request $request){
        
        $messages = [
            'user_address_id.required' => trans('validation.custom.user_address_id_required')
        ];
        $this->validate($request, [
            'payment_mode'    => 'required',
            //'user_address_id' => 'required|exists:user_addresses,id,deleted_at,NULL',
        ],$messages);
    
        $request->request->add(['checkout_status' => 1]);

        $UnavailableCount  = StoreCart::whereHas('product',function($q) use ($request){
                $q->where('status',0);
        })
        ->where('company_id',$this->company_id)
        ->where('user_id',$this->user->id)->count();
        
        if($UnavailableCount > 0) return Helper::getResponse(['status' => 205]);

        $cart =  $this->viewcart($request);
      
        if(empty($cart['carts'])){
            return Helper::getResponse(['status' => 404,'message' =>'user cart is empty', 'error' => 'user cart is empty']);
        }

        $ActiveRequests = StoreOrder::PendingRequest($this->user->id, $cart['store_id'])->count();

        if($ActiveRequests > 0) {
            if($request->payment_mode == "CASH") {
                return Helper::getResponse(['status' => 422,'message' => trans('api.order.request_inprogress') ]);
            }
        }

        $address_details = UserAddress::select('id','latitude','longitude','map_address','flat_no','street')->find($request->user_address_id);

        $settings = json_decode(json_encode(Setting::where('company_id',$this->company_id)->first()->settings_data));
        $orderConfig = $settings->order;
        $distance = isset($orderConfig->store_search_radius) ? $orderConfig->store_search_radius : 100;

        $store_details = Store::with('storetype');
        $store_details->whereHas('storetype',function($q) use ($request){
                $q->where('status',1);

        });

        if($address_details && $request->order_type == 'DELIVERY') {
            $store_details->select(DB::Raw("(6371 * acos( cos( radians('$address_details->latitude') ) * cos( radians(latitude) ) * cos( radians(longitude) - radians('$address_details->longitude') ) + sin( radians('$address_details->latitude') ) * sin( radians(latitude) ) ) ) AS distance"),'id','picture','contact_number','store_type_id','latitude','longitude','store_location','store_name','currency_symbol');
            $store_details->whereRaw("(6371 * acos( cos( radians('$address_details->latitude') ) * cos( radians(latitude) ) * cos( radians(longitude) - radians('$address_details->longitude') ) + sin( radians('$address_details->latitude') ) * sin( radians(latitude) ) ) ) <= $distance");
        }
        $store_details = $store_details->find($cart['store_id']);

        if( $store_details == null ){
            return Helper::getResponse(['status' => 422,'message' =>'We won\'t deliver to this location. If beyond the city limit']);
        }
        $shopstatus = $this->shoptime($store_details->id);

        if( $shopstatus == 'CLOSED' ){
            return Helper::getResponse(['status' => 422,'message' =>'Shop is not available. Please check after some time']);
        }

        $payment_id = '';
        $is_payable =false;
        $paymentMode = $request->payment_mode;
        if($cart['payable'] == 0)
        {
            $is_payable = true;
        }
        if($request->payment_mode=='CARD'){ 
            $payable = $cart['payable'];
            if($payable!=0){
                $payment_id = $this->orderpayment($payable,$request);
                if($payment_id=='failed'){

                    return Helper::getResponse(['status' => 404,'message' => trans('Transaction Failed')]);
                }  
            }
        }
        //return $request->all();
        $order = new StoreOrder ();
        if($address_details && $request->order_type == 'DELIVERY') {
            $details = "https://maps.googleapis.com/maps/api/directions/json?origin=".$address_details->latitude.",".$address_details->longitude."&destination=".$store_details->latitude.",".$store_details->longitude."&mode=driving&key=".$this->settings->site->browser_key;
            $order->delivery_address = json_encode($address_details);
            $json = Helper::curl($details);
            $details = json_decode($json, TRUE);
        
            $route_key = (count($details['routes']) > 0) ? $details['routes'][0]['overview_polyline']['points'] : '';
            $order->route_key = $route_key;
            $order->distance = (count($details['routes']) > 0) ? ($details['routes'][0]['legs'][0]['distance']['value'] ) : 0;
            $order->user_address_id = $request->user_address_id;
        }
        $order->description = isset($request->description)?$request->description:'';
        $bookingprefix = $this->settings->order->booking_prefix;
        $order->store_order_invoice_id = $bookingprefix.time().rand('0','999');
        if(!empty($payment_id) || $is_payable){
          $order->paid=1;
        }
        $order->user_id = $this->user->id;
        $order->assigned_at = (Carbon::now())->toDateTimeString();
        $order->order_type = $request->order_type;
        if($this->settings->order->manual_request==1){
            $order->request_type = 'MANUAL';
        }
        $order->order_otp = mt_rand(1000 , 9999);
        $order->leave_at_door = $request->payment_mode != 'CASH' && $request->order_type == 'DELIVERY' ? $request->leave_at_door : 0;
        $order->timezone = (Auth::guard('user')->user()->state_id) ? State::find(Auth::guard('user')->user()->state_id)->timezone : '';
        $order->city_id = Auth::guard('user')->user()->city_id;
        $order->country_id = Auth::guard('user')->user()->country_id;
        $order->promocode_id = !empty($cart['promocode_id']) ? $cart['promocode_id']:0;
        if($request->has('delivery_date') && $request->delivery_date !=''){
            $order->delivery_date = Carbon::parse($request->delivery_date)->format('Y-m-d H:i:s');
            $order->schedule_status = 1;
        }
        $order->store_id = $cart['store_id'];
        $order->store_type_id = $store_details->store_type_id;
        $order->admin_service = 'ORDER';
        $order->order_ready_status = 0;
        $order->company_id = $this->company_id;
        $order->currency = Auth::guard('user')->user()->currency_symbol;
        $order->status = 'ORDERED';
        $order->pickup_address = json_encode($store_details);
        $order->save();
        if($order->id){
            $store_commision_amount = ($cart['net']*($cart['store_commision_per']/100));
            $orderinvoice = new StoreOrderInvoice ();
            $orderinvoice->store_order_id = $order->id;
            $orderinvoice->store_id = $order->store_id;
            $orderinvoice->payment_mode = $request->payment_mode;
            $orderinvoice->payment_id = $payment_id;
            $orderinvoice->company_id = $this->company_id;
            $orderinvoice->item_price = $cart['total_item_price'];
            $orderinvoice->gross = $cart['total_price'];
            $orderinvoice->net = $cart['net'];
            $orderinvoice->discount = $cart['shop_discount'];
            $orderinvoice->promocode_id = $cart['promocode_id'];
            $orderinvoice->promocode_amount = $cart['promocode_amount'];
            $orderinvoice->wallet_amount = $cart['wallet_balance'];
            $orderinvoice->tax_per = $cart['shop_gst'];
            $orderinvoice->tax_amount = $cart['shop_gst_amount'];
            $orderinvoice->commision_per = $cart['store_commision_per'];
            $orderinvoice->commision_amount = $store_commision_amount;
            /*$orderinvoice->delivery_per = $cart['total_price'];*/
            if($request->order_type == 'DELIVERY') $orderinvoice->delivery_amount = $cart['delivery_charges'];
            $orderinvoice->store_package_amount = $cart['shop_package_charge'];
            $orderinvoice->total_amount = $cart['total_price'];
            $orderinvoice->cash = $cart['payable'];
            $orderinvoice->payable = $cart['payable'];

            $orderinvoice->status = 0;
            $orderinvoice->cart_details = json_encode($cart['carts']);
            if($orderinvoice->wallet_amount == $orderinvoice->total_amount && $orderinvoice->payable == 0)
            {
                $orderinvoice->payment_mode = 'WALLET';
            }
            else{
                $orderinvoice->payment_mode = $request->payment_mode;
            }
            $orderinvoice->save();
            $orderstatus = new StoreOrderStatus();
            $orderstatus->company_id = $this->company_id;
            $orderstatus->store_order_id = $order->id;
            $orderstatus->status = 'ORDERED';
            $orderstatus->save();

        
        if($store_details->storetype->category == "OTHERS")
        {
            foreach ($cart['carts'] as $value) {
                $storeitem = StoreItem::findOrFail($value->product->id);
                if(!empty($storeitem->quantity))
                {
                    $total_quantity =$storeitem->quantity - $value->quantity;
                    if($storeitem->low_stock >= $total_quantity)
                    {
                        $Notifications = new Notifications;
                        $Notifications->notify_type = "shop";
                        $Notifications->user_id = $storeitem->store_id;
                        $Notifications->title = " Low stock";   
                        $Notifications->service = "ORDER";  
                        $Notifications->company_id = Auth::guard('user')->user()->company_id;
                        $Notifications->descriptions = $storeitem->item_name." is lesser than stock limit. Please add in inventory";
                        $Notifications->expiry_date = date('Y-m-d H:i:s', strtotime($order->created_at));
                        $Notifications->save();
                    }
                    $storeitem->quantity = $total_quantity;
                }else
                {
                    $Notifications = new Notifications;
                    $Notifications->notify_type = "shop";
                    $Notifications->user_id = $storeitem->store_id;
                    $Notifications->title = " Low stock";   
                    $Notifications->service = "ORDER";  
                    $Notifications->company_id = Auth::guard('user')->user()->company_id;
                    $Notifications->descriptions = $storeitem->item_name." is not available";
                    $Notifications->expiry_date = date('Y-m-d H:i:s', strtotime($order->created_at));
                    $Notifications->save();
                    $storeitem->status =0;
                }

                $storeitem->save();
            }
        }

            //payment log update order id
            if($payment_id){
                $log = PaymentLog::where('transaction_id', $payment_id)->first();
                $log->transaction_id = $order->id;
                $log->transaction_code = $order->store_order_invoice_id;
                $log->response = json_encode($order);
                $log->save();
            }
            //$User = User::find($this->user->id);
            $Wallet = Auth::guard('user')->user()->wallet_balance;
            //$Total = 
            //
            if($cart['wallet_balance'] > 0){
                // charged wallet money push 
                // (new SendPushNotification)->ChargedWalletMoney($UserRequest->user_id,$Wallet, 'wallet');
                (new SendPushNotification)->ChargedWalletMoney($this->user->id,Helper::currencyFormat($cart['wallet_balance'],Auth::guard('user')->user()->currency_symbol), 'wallet', 'Wallet Info');

                $transaction['amount']=$cart['wallet_balance'];
                $transaction['id']=$this->user->id;
                $transaction['transaction_id']=$order->id;
                $transaction['transaction_alias']=$order->store_order_invoice_id;
                $transaction['company_id']=$this->company_id;
                $transaction['transaction_msg']='order deduction';
                $transaction['admin_service']=$order->admin_service;
                $transaction['country_id']=$order->country_id;

                (new Transactions)->userCreditDebit($transaction,0);
            }
            //user request
            $user_request = new UserRequest();
            $user_request->company_id = $this->company_id;
            $user_request->user_id = $this->user->id;
            $user_request->request_id = $order->id;
            $user_request->request_data = json_encode(StoreOrder::with('invoice', 'store.storetype')->where('id',$order->id)->first());
            $user_request->admin_service = 'ORDER';
            $user_request->status = 'ORDERED';
            $user_request->save();

            $CartItem_ids  = StoreCart::where('company_id',$this->company_id)->where('user_id',$this->user->id)->pluck('id','id')->toArray();
            $CartItems  = StoreCart::where('company_id',$this->company_id)->where('user_id',$this->user->id)->delete();

            if($order->promocode_id != null) {
                $PromocodeUsage = new PromocodeUsage;
                $PromocodeUsage->user_id = $this->user->id;
                $PromocodeUsage->company_id = $this->company_id;
                $PromocodeUsage->promocode_id = $order->promocode_id;
                $PromocodeUsage->status = 'USED';
                $PromocodeUsage->save();
            }

            StoreCartItemAddon::whereIN('store_cart_id',$CartItem_ids)->delete();

            if($request->has('delivery_date') && $request->delivery_date !=''){
                // scheduling
                $schedule_status = 1;
            }else{
                //Send message to socket
                $requestData = ['type' => 'ORDER', 'room' => 'room_'.$this->company_id, 'id' => $order->id, 'city' => ($this->settings->demo_mode == 0) ? $order->store->city_id : 0, 'user' => $order->user_id ];
                app('redis')->publish('checkOrderRequest', json_encode( $requestData ));
            } 


            //Send message to socket
            $requestData = ['type' => 'ORDER', 'room' => 'room_'.$this->company_id, 'id' => $order->id,'shop'=> $cart['store_id'], 'user' => $order->user_id ];
            app('redis')->publish('newRequest', json_encode( $requestData ));
            
            (new SendPushNotification)->ShopRequest($order->store_id, $order->admin_service); 

            $store = Store::where('id', $order->store_id)->first();
            $settings = json_decode(json_encode(Setting::where('company_id',$this->company_id)->first()->settings_data));
            $site = $settings->site;
        
            Notification::send($store, new WebPushNotification($site->site_title, $site->site_icon, Auth::guard('user')->user()->first_name. " " .Auth::guard('user')->user()->last_name. " Created New Order" ));

            return  $this->orderdetails($order->id);
        }
        
        
    }

    public function cancelOrder(Request $request){

        $this->validate($request, [
            'id' => 'required|numeric|exists:order.store_orders,id,user_id,'.$this->user->id,
            'cancel_reason'=> 'required|max:255',
        ]);

        $request->request->add(['cancelled_by' => 'USER']);

        try {
            $order = (new Order())->cancelOrder($request);
            return Helper::getResponse(['status' => $order['status'], 'message' => $order['message'] ]);
        } catch (Exception $e) {  
            return Helper::getResponse(['status' => 500, 'error' => $e->getMessage()]);
        }
    }

    public function orderdetails($id){
        $order = StoreOrder::with(['store','store.storetype','deliveryaddress','invoice','user','chat',
        'provider' => function($query){  $query->select('id', 'first_name','last_name','country_code','mobile','rating','latitude','longitude','picture' ); },
			])->whereHas('store.storetype',function($q){
                $q->where('status',1);

            })->find($id);
        
            return Helper::getResponse(['data' => $order]);
    }

    //status check request
    public function status(Request $request) 
    {
        try{
            $check_status = ['CANCELLED', 'SCHEDULED'];
            $admin_service = AdminService::where('admin_service','ORDER')->where('company_id', $this->company_id)->first();

            $orderRequest = StoreOrder::OrderRequestStatusCheck($this->user->id, $check_status, $admin_service->id)
                                        ->get()
                                        ->toArray();            
            $search_status = ['SEARCHING','SCHEDULED'];
            $Timeout = $this->settings->order->provider_select_timeout ? $this->settings->order->provider_select_timeout : 60 ;
            $response_time = $Timeout;

            return Helper::getResponse(['data' => [
                'response_time' => $response_time, 
                'data' => $orderRequest, 
                'order_otp' =>  isset($this->settings->order->order_otp) ? $this->settings->order->order_otp: 0 , 
                'sos' => isset($this->settings->site->sos_number) ? $this->settings->site->sos_number : '911' , 
                'emergency' => isset($this->settings->site->contact_number) ? $this->settings->site->contact_number : [['number' => '911']]  ]]);

        } catch (Exception $e) {
            return Helper::getResponse(['status' => 500, 'message' => trans('api.something_went_wrong'), 'error' => $e->getMessage() ]);
        }
    }

    public function orderdetailsRating(Request $request){
       $this->validate($request, [
            'request_id' => 'required|integer|exists:order.store_orders,id,user_id,'.$this->user->id,
            'shopid' => 'required|integer|exists:order.stores,id',
            'rating' => 'required|integer|in:1,2,3,4,5',
            'shoprating' => 'required|integer|in:1,2,3,4,5',
            'comment' => 'max:255',
        ],['comment.max'=>'character limit should not exceed 255']);


       try {

            $orderRequest = StoreOrder::where('id', $request->request_id)->where('status', 'COMPLETED')->firstOrFail();
            $data = (new UserServices())->rate($request, $orderRequest );
            return Helper::getResponse(['status' => isset($data['status']) ? $data['status'] : 200, 'message' => isset($data['message']) ? $data['message'] : '', 'error' => isset($data['error']) ? $data['error'] : '' ]);

        } catch (\Exception $e) {
            return Helper::getResponse(['status' => 500, 'message' => trans('api.order.request_not_completed').$e->getMessage(), 'error' =>trans('api.order.request_not_completed') ]);
        }
    }

    public function tripsList(Request $request) { 
        try{
			$jsonResponse = [];
			$jsonResponse['type'] = 'order';
            $withCallback=[
                            'user' => function($query){  $query->select('id', 'first_name', 'last_name', 'rating', 'picture','currency_symbol' ); },
                            'provider' => function($query){  $query->select('id', 'first_name', 'last_name', 'rating', 'picture','mobile' ); },
                            'reason' => function($query){  $query->select('id', 'reason'); },
                            'rating' => function($query){  $query->select('request_id','user_rating', 'provider_rating','user_comment','provider_comment','store_comment','store_rating'); },
                            'invoice' 
                          ];

            $userrequest=StoreOrder::select('store_orders.*',DB::raw('(select total_amount from store_order_invoices where store_orders.id=store_order_invoices.store_order_id) as total_amount'),DB::raw('(select payment_mode from store_order_invoices where store_orders.id=store_order_invoices.store_order_id) as payment_mode'), 'user_rated', 'provider_rated');
            $data=(new UserServices())->userHistory($request,$userrequest,$withCallback);
            // dd($data);
            $jsonResponse['total_records'] = count($data);
			$jsonResponse['order'] = $data;
			return Helper::getResponse(['data' => $jsonResponse]);
		}

		catch (Exception $e) {
			return response()->json(['error' => trans('api.something_went_wrong')]);
		}

    }
    
    public function getOrderHistorydetails(Request $request,$id)
	{
		try{
			$jsonResponse = [];
			$jsonResponse['type'] = 'order';
            $request->request->add(['admin_service'=>'ORDER','id'=>$id]);
			$userrequest = StoreOrder::with(array("store.storetype",'orderInvoice'=>function($query){
				$query->select('id','store_order_id','gross','wallet_amount','total_amount','payment_mode','tax_amount','delivery_amount','promocode_amount','payable','cart_details','commision_amount','store_package_amount','cash','discount','item_price');
			},'user'=>function($query){
				$query->select('id','first_name','last_name','rating','picture','mobile','currency_symbol');
			},
            'provider'=>function($query){
                $query->select('id','first_name','last_name','rating','picture','mobile');
            },"dispute"=>function($query){
               $query->where('dispute_type','user');
            },
            'rating' => function($query){  $query->select('request_id','user_rating', 'provider_rating','user_comment','provider_comment','store_comment','store_rating'); },
            ))->whereHas('store.storetype',function($q){
                $q->where('status',1);
            })
            ->select('id','store_id','store_order_invoice_id','user_id','provider_id','admin_service','company_id','pickup_address','delivery_address','order_type','created_at','status','timezone');
            $data=(new UserServices())->userTripsDetails($request,$userrequest);
             $jsonResponse['order'] = $data;
			return Helper::getResponse(['data' => $jsonResponse]);
		}
		catch (Exception $e) {
			return response()->json(['error' => trans('api.something_went_wrong')]);
		}
    }


    public function requestHistory(Request $request)
	{
		try {
            $history_status = array('CANCELLED','COMPLETED');
            $datum = StoreOrder::select('store_orders.*',DB::raw('(select total_amount from store_order_invoices where store_orders.id=store_order_invoices.store_order_id) as total_amount'),DB::raw('(select discount from store_order_invoices where store_orders.id=store_order_invoices.store_order_id) as discount'),DB::raw('(select payment_mode from store_order_invoices where store_orders.id=store_order_invoices.store_order_id) as payment_mode'))
                        ->where('company_id', Auth::user()->company_id)
                     ->whereIn('status',$history_status)
                     ->with('user', 'provider');
            /*if(Auth::user()->hasRole('FLEET')) {
                $datum->where('admin_id', Auth::user()->id);  
            }*/
            if($request->has('search_text') && $request->search_text != null) {
                $datum->Search($request->search_text);
            }    
            
            $data = $datum->orderby('id','desc')->paginate(10);    
            return Helper::getResponse(['data' => $data]);
        } catch (\Throwable $e) {
            return Helper::getResponse(['status' => 404,'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
        }
    }

    public function shoprequestHistory(Request $request)
    {
        try {
            $history_status = array('CANCELLED','COMPLETED');
            $datum = StoreOrder::select('store_orders.*',DB::raw('(select total_amount from store_order_invoices where store_orders.id=store_order_invoices.store_order_id) as total_amount'),DB::raw('(select discount from store_order_invoices where store_orders.id=store_order_invoices.store_order_id) as discount'),DB::raw('(select payment_mode from store_order_invoices where store_orders.id=store_order_invoices.store_order_id) as payment_mode'))
                        ->where('company_id', Auth::guard('shop')->user()->company_id)->where('store_id',Auth::guard('shop')->user()->id)
                        ->with('user', 'provider');
            
            if($request->has('search_text') && $request->search_text != null) {
                $datum->Search($request->search_text);
            } 
            if($request->has('limit')) {
                if($request->type == "ONGOING")
                {
                    $data=$datum->whereNotIn('status',['ORDERED','SCHEDULED','CANCELLED','STORECANCELLED','COMPLETED'])->orderBy('id','desc')->paginate($request->limit);
                }else{
                    $data=$datum->where("status",$request->type)->orderBy('id','desc')->paginate($request->limit);  
                }
                
            }elseif ($request->has('type')) {
               if($request->type == "ONGOING")
                {
                    $data=$datum->whereNotIn('status',['ORDERED','SCHEDULED','CANCELLED','STORECANCELLED','COMPLETED'])->orderBy('id','desc')->paginate(10);
                }else{
                    $data=$datum->where("status",$request->type)->orderBy('id','desc')->paginate(10);  
                }
            }
            else{
                $data = $datum->whereIn('status',$history_status)->orderby('id','desc')->paginate(10);    
            }   
            
            return Helper::getResponse(['data' => $data]);
        } catch (\Throwable $e) {
            return Helper::getResponse(['status' => 404,'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
        }
    }




    public function requestScheduleHistory(Request $request)
	{
		try {
            $scheduled_status = array('SCHEDULED');
            $datum = StoreOrder::select('store_orders.*',DB::raw('(select total_amount from store_order_invoices where store_orders.id=store_order_invoices.store_order_id) as total_amount'),DB::raw('(select payment_mode from store_order_invoices where store_orders.id=store_order_invoices.store_order_id) as payment_mode'))
            ->where('company_id', $this->company_id)
                    ->where('schedule_status',1)
                     ->with('user', 'provider');
            /*if(Auth::user()->hasRole('FLEET')) {
                $datum->where('admin_id', Auth::user()->id);  
            }*/
            if($request->has('search_text') && $request->search_text != null) {
                $datum->Search($request->search_text);
            }    
            if($request->has('order_by')) {
                $datum->orderby($request->order_by, $request->order_direction);
            }
            $data = $datum->paginate(10);    
            return Helper::getResponse(['data' => $data]);
        } catch (\Throwable $e) {
            return Helper::getResponse(['status' => 404,'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
        }
    }
    
    public function requestStatementHistory(Request $request)
	{
		try {
            $history_status = array('CANCELLED','COMPLETED');
            $orderRequests = StoreOrder::select('*','created_at as joined')->where('company_id',  Auth::user()->company_id)
                     ->with('user', 'provider');
            if($request->has('country_id')) {
                $orderRequests->where('country_id',$request->country_id);
            }
            if(Auth::user()->hasRole('FLEET')) {
                $orderRequests->where('admin_id', Auth::user()->id);  
            }
            if($request->has('search_text') && $request->search_text != null) {
                $orderRequests->Search($request->search_text);
            }

            if($request->has('status') && $request->status != null) {
                $history_status = array($request->status);
            }

            if($request->has('user_id') && $request->user_id != null) {
                $orderRequests->where('user_id',$request->user_id);
            }

            if($request->has('provider_id') && $request->provider_id != null) {
                $orderRequests->where('provider_id',$request->provider_id);
            }

            if($request->has('ride_type') && $request->ride_type != null) {
                $orderRequests->where('store_type_id',$request->ride_type);
            }
    
            if($request->has('order_by')) {
                $orderRequests->orderby($request->order_by, $request->order_direction);
            }
            $type = isset($_GET['type'])?$_GET['type']:'';
            if($type == 'today'){
				$orderRequests->where('created_at', '>=', Carbon::today());
			}elseif($type == 'monthly'){
				$orderRequests->where('created_at', '>=', Carbon::now()->month);
			}elseif($type == 'yearly'){
				$orderRequests->where('created_at', '>=', Carbon::now()->year);
			}elseif ($type == 'range') {   
                if($request->has('from') &&$request->has('to')) {             
                    if($request->from == $request->to) {
                        $orderRequests->whereDate('created_at', date('Y-m-d', strtotime($request->from)));
                    } else {
                        $orderRequests->whereBetween('created_at',[Carbon::createFromFormat('Y-m-d', $request->from),Carbon::createFromFormat('Y-m-d', $request->to)]);
                    }
                }
			}else{
                // dd(5);
            }
            $cancelservices = $orderRequests;
            $orderCounts = $orderRequests->count();
            $dataval = $orderRequests->whereIn('status',$history_status)->paginate(10);
            $cancelledQuery = $cancelservices->where('status','CANCELLED')->count();
            $total_earnings = 0;
            foreach($dataval as $order){
                //$order->status = $order->status == 1?'Enabled' : 'Disable';
                $orderid  = $order->id;
                $earnings = StoreOrderInvoice::select('total_amount','payment_mode','commision_amount')->where('store_order_id',$orderid)->where('company_id',  Auth::user()->company_id)->first();
                if($earnings != null){
                    $order->payment_mode = $earnings->payment_mode;
                    $order->earnings = $earnings->total_amount;
                    $order->commision = $earnings->commision_amount;
                    $total_earnings = $total_earnings + $earnings->total_amount;
                }else{
                    $order->earnings = 0;
                }
            }
            $data['orders'] = $dataval;
            $data['total_orders'] = $orderCounts;
            $data['revenue_value'] = $total_earnings;
            $data['cancelled_orders'] = $cancelledQuery;
            return Helper::getResponse(['data' => $data]);

        } catch (\Throwable $e) {
            return Helper::getResponse(['status' => 404,'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
        }
    }

    public function requestHistoryDetails($id)
	{
		try {
			$data = StoreOrder::with('user', 'provider','orderInvoice','reason')->findOrFail($id);
            return Helper::getResponse(['data' => $data]);
        } catch (\Throwable $e) {
            return Helper::getResponse(['status' => 404,'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
        }
    }

    public function search(Request $request,$id){
        $Shops = [];
        $dishes = [];
        if($request->has('q')){
            $prodname = $request->q;
            $search_type = $request->t;
            if($search_type=='store'){ 
                $shopps = Store::with(['categories'])->where('company_id',$this->company_id)->where('store_type_id',$id)
                /*->select('id','store_type_id','company_id','store_name','store_location','latitude','longitude','picture','offer_min_amount','estimated_delivery_time','free_delivery','is_veg','rating','offer_percent')*/
                ->where('store_name','LIKE', '%' . $prodname . '%');
                    if($request->has('latitude') && $request->has('latitude') !='' && $request->has('longitude') && $request->has('longitude')!='')
                    {
                        $longitude = $request->longitude;
                        $latitude = $request->latitude;
                        $distance = $this->settings->order->search_radius;
                        // config('constants.store_search_radius', '10');
                        if($distance>0){
                            $shopps->select('id','store_type_id','company_id','store_name','store_location','latitude','longitude','picture','offer_min_amount','estimated_delivery_time','free_delivery','is_veg','rating','offer_percent',\DB::raw("(6371 * acos( cos( radians('$latitude') ) * cos( radians(latitude) ) * cos( radians(longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(latitude) ) ) ) AS distance"))
                                ->whereRaw("(6371 * acos( cos( radians('$latitude') ) * cos( radians(latitude) ) * cos( radians(longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(latitude) ) ) ) <= $distance");
                        }
                    }
                $shops = $shopps->get();
                $shops->map(function ($shop) {
                    $shop->name = $shop->store_name;
                    $shop->item_discount = $shop->offer_percent;
                    $shop->store_id = $shop->id;
                    $shop->delivery_time = $shop->estimated_delivery_time;
                    $shop['shopstatus'] = $this->shoptime($shop->id);
                    //$shop['category'] = $shop->categories()->select(\DB::raw('group_concat(store_category_name) as names'))->names;
                    $cat = [];
                    foreach($shop->categories as $item){
                        $cat[]=$item->store_category_name;
                    }
                    $shop['category'] = implode(',',$cat);
                    unset($shop->categories);
                    return $shop;
                });
                $data = $shops;
            }else{
                $data = StoreItem::with(['store', 'categories'])->where('company_id',$this->company_id)->where('item_name','LIKE', '%' . $prodname . '%')->select('id','store_id','store_category_id','item_name','picture','item_discount')
                ->whereHas('store',function($q) use ($request,$id){
                    $q->where('store_type_id',$id);
                    if($request->has('latitude') && $request->has('latitude') !='' && $request->has('longitude') && $request->has('longitude')!='')
                    {
                        $longitude = $request->longitude;
                        $latitude = $request->latitude;
                        $distance = $this->settings->order->search_radius;
                        // config('constants.store_search_radius', '10');
                        if($distance>0){
                            $q->select('id','store_type_id','company_id','store_name','store_location','latitude','longitude','picture','offer_min_amount','estimated_delivery_time','free_delivery','is_veg','rating','offer_percent',\DB::raw("(6371 * acos( cos( radians('$latitude') ) * cos( radians(latitude) ) * cos( radians(longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(latitude) ) ) ) AS distance"))
                                ->whereRaw("(6371 * acos( cos( radians('$latitude') ) * cos( radians(latitude) ) * cos( radians(longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(latitude) ) ) ) <= $distance");
                        }
                    }
                })
                ->get();
                $data->map(function ($item) {
                    $item->name = $item->item_name;
                    $item->rating = $item->store->rating;
                    $item->delivery_time = $item->store->estimated_delivery_time;
                    $item['shopstatus'] = $this->shoptime($item->store_id);
                    if($item->categories->count()>0){
                    $item['category'] = $item->categories[0]->store_category_name;
                    }else{
                    $item['category'] = null;  
                    }
                    unset($item->store);
                    unset($item->categories);
                    return $item;
                });
            }
        }

        return Helper::getResponse(['data' => $data]);
    }


    public function orderpayment($totalAmount,$request){
        $paymentMode = $request->payment_mode;
        $settings = json_decode(json_encode(Setting::where('company_id', $this->company_id)->first()->settings_data));
              $siteConfig = $settings->site;
              $orderConfig = $settings->order;
              $paymentConfig = json_decode( json_encode( $settings->payment ) , true);

              $cardObject = array_values(array_filter( $paymentConfig, function ($e) { return $e['name'] == 'card'; }));
              $card = 0;

                $stripe_secret_key = "";
                $stripe_publishable_key = "";
                $stripe_currency = "";

                if(count($cardObject) > 0) { 
                    $card = $cardObject[0]['status'];

                    $stripeSecretObject = array_values(array_filter( $cardObject[0]['credentials'], function ($e) { return $e['name'] == 'stripe_secret_key'; }));
                    $stripePublishableObject = array_values(array_filter( $cardObject[0]['credentials'], function ($e) { return $e['name'] == 'stripe_publishable_key'; }));
                    $stripeCurrencyObject = array_values(array_filter( $cardObject[0]['credentials'], function ($e) { return $e['name'] == 'stripe_currency'; }));

                    if(count($stripeSecretObject) > 0) {
                        $stripe_secret_key = $stripeSecretObject[0]['value'];
                    }

                    if(count($stripePublishableObject) > 0) {
                        $stripe_publishable_key = $stripePublishableObject[0]['value'];
                    }

                    if(count($stripeCurrencyObject) > 0) {
                        $stripe_currency = $stripeCurrencyObject[0]['value'];
                    }
                }
  
              $random = $orderConfig->booking_prefix.mt_rand(100000, 999999);

                switch ($paymentMode) {
                    case 'CARD':  

                    if($request->has('card_id')){

                        Card::where('user_id',$this->user->id)->update(['is_default' => 0]);
                        Card::where('card_id',$request->card_id)->update(['is_default' => 1]);
                    }
                        
                    $card = Card::where('user_id', $this->user->id)->where('is_default', 1)->first();

                    //if($card == null)  $card = Card::where('user_id', $this->user->id)->first();
                    $log = new PaymentLog();
                    $log->admin_service = 'ORDER';
                    $log->company_id = $this->company_id;
                    $log->user_type = 'user';
                    $log->transaction_code = $random;
                    $log->amount = $totalAmount;
                    $log->transaction_id = '';
                    $log->payment_mode = $paymentMode;
                    $log->user_id = $this->user->id;
                    $log->save();
                    $gateway = new PaymentGateway('stripe');

                    $response = $gateway->process([
                          'order' => $random,
                          "amount" => $totalAmount,
                          "currency" => $stripe_currency,
                          "customer" => Auth::guard('user')->user()->stripe_cust_id,
                          "card" => $card->card_id,
                          "description" => "Payment Charge for " . Auth::guard('user')->user()->email,
                          "receipt_email" => Auth::guard('user')->user()->email,
                    ]);

                  break;
                }
                //return $response;
                if($response->status == "SUCCESS") {  
                    $log->transaction_id = $response->payment_id;
                    $log->save();
                    
                    return $response->payment_id; 
                } else {
                  return 'failed';
                }
    }

    public function order_request_dispute(Request $request){ 
        
        $this->validate($request, [
                'dispute_name' => 'required',
                'dispute_type' => 'required',
                'provider_id' => 'required',
                'user_id' => 'required',
                'id'=>'required',
                'store_id'=>'required',
            ]);
        $order_request_disputes = StoreOrderDispute::where('company_id',$this->company_id)
                                ->where('store_order_id',$request->id)
                                ->where('dispute_type','user')
                                ->first();
        $request->request->add(['admin_service'=>'ORDER']);                        
        if($order_request_disputes==null)
        {
            try{
                $disputeRequest = new StoreOrderDispute;
                $data=(new UserServices())->userDisputeCreate($request, $disputeRequest);
                return Helper::getResponse(['status' => 200, 'message' => trans('admin.create')]);
            } 
            catch (\Throwable $e) {
                return Helper::getResponse(['status' => 404, 'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
            }
        }else{
            return Helper::getResponse(['status' => 404, 'message' => trans('Already Dispute Created for the Ride Request')]);
        }
    }

    public function get_order_request_dispute(Request $request,$id) {
        $order_request_dispute = StoreOrderDispute::with('request')->where('company_id',$this->company_id)
                                ->where('store_order_id',$id)
                                ->where('dispute_type','user')
                                ->first();
         if($order_request_dispute){
           $order_request_dispute->created_time=(\Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $order_request_dispute->created_at, 'UTC'))->setTimezone($order_request_dispute->request->timezone)->format(Helper::dateFormat()); 
         }                       
                                
        return Helper::getResponse(['data' => $order_request_dispute]);
    }

}
