<?php

namespace App\Http\Controllers\V1\Common\User;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Common\Card;
use App\Models\Common\Chat;
use App\Models\Common\City;
use App\Models\Common\CmsPage;
use App\Models\Common\CompanyCity;
use App\Models\Common\CompanyCountry;
use App\Models\Common\Country;
use App\Models\Common\Faq;
use App\Models\Common\HealthArticle;
use App\Models\Common\HealthArticleCategory;
use App\Models\Common\HealthArticleSubcategory;
use App\Models\Common\Menu;
use App\Models\Common\Notifications;
use App\Models\Common\Promocode;
use App\Models\Common\Reason;
use App\Models\Common\Setting;
use App\Models\Common\State;
use App\Models\Common\User;
use App\Models\Common\UserAddress;
use App\Models\Common\UserRequest;
use App\Models\Common\UserWallet;
use App\Models\Order\Brand;
use App\Models\Order\Store;
use App\Models\Order\StoreDrug;
use App\Models\Order\StoreItem;
use App\Models\Service\Service;
use App\Services\ReferralResource;
use App\Services\SendPushNotification;
use App\Traits\Encryptable;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class HomeController extends Controller
{
    use Encryptable;

    public function country($id, Request $request)
    {

        $country = Country::find($id);

        return Helper::getResponse(['data' => $country]);
    }

    public function index(Request $request)
    {

        $user = Auth::guard('user')->user();

        $company_id = $user ? $user->company_id : 1;

        $city_id = $user ? $user->city_id : $request->city_id;

        $menus = new \stdClass;

        $menus->services = Menu::with('service')->whereHas('service', function ($query) {
            $query->where('status', 1);
        })->whereHas('cities', function ($query) use ($user, $city_id) {
            $query->where('city_id', $city_id);
        })->where('company_id', $company_id)->where('status', 1)->orderby('sort_order')->get();
        $menus->promocodes = Promocode::with('services')->whereHas('services', function ($query) {
            $query->where('status', 1);
        })->where('company_id', $company_id)
            ->where('expiration', '>=', date("Y-m-d H:i"))
            ->whereDoesntHave('promousage', function ($query) use ($user) {
                $query->where('user_id', $user ? $user->id : 0);
            })
            ->get();

        return Helper::getResponse(['data' => $menus]);
    }

    public function ongoing_services(Request $request)
    {

        try {
            $requests = UserRequest::with('service')->where('user_id', Auth::guard('user')->user()->id)->whereNotIn('status', ['SCHEDULED', 'CANCELLED'])->orderby('id', 'desc')->get();

            return Helper::getResponse(['data' => $requests]);
        } catch (Exception $e) {
            return response()->json(['error' => trans('api.something_went_wrong')], 500);
        }
    }

    public function addmanageaddress(Request $request)
    {

        $this->validate($request, [
            'map_address'  => 'required',
            'address_type' => 'required',
            'latitude'     => 'required',
            'longitude'    => 'required',
            'flat_no'      => 'required',
            'street'       => 'required',
        ]);

        try {

            $title       = ('Home' == $request->address_type || 'Work' == $request->address_type) ? $request->address_type : ((!empty($request->title)) ? $request->title : "Other");
            $useraddress = null;
            if ("Other" != $title) {
                $useraddress = UserAddress::where('company_id', Auth::guard('user')->user()->company_id)->where('user_id', Auth::guard('user')->user()->id)->where('address_type', $request->address_type)->where('title', $title)->first();
            }

            if (null != $useraddress) {
                $useraddress = $useraddress;
            } else {
                $useraddress = new UserAddress;
            }

            /*if($request->address_type=='Home' || $request->address_type=='Work' ){
            $UserAddress=$UserAddress->first();

            if($UserAddress != null){
            //return Helper::getResponse(['status' => 404, 'message' => 'Address Type Already Exist']);
            $useraddress = $UserAddress;
            }else{
            $useraddress = new UserAddress;
            }
            $title=$request->address_type;
            }else{
            $title= (!empty($request->title)) ? $request->title : "Other";
            $UserAddress=$UserAddress->where('title',$request->title)->first();

            $useraddress = new UserAddress;
            }*/

            $useraddress->address_type = $request->address_type;
            $useraddress->company_id   = Auth::guard('user')->user()->company_id;
            $useraddress->user_id      = Auth::guard('user')->user()->id;
            $useraddress->landmark     = $request->landmark;
            $useraddress->flat_no      = $request->flat_no;
            $useraddress->title        = $title;
            $useraddress->street       = $request->street;
            $useraddress->latitude     = $request->latitude;
            $useraddress->longitude    = $request->longitude;
            $useraddress->map_address  = $request->map_address;
            $useraddress->city         = $request->city;
            $useraddress->state        = $request->state;
            $useraddress->county       = $request->county;
            $useraddress->zipcode      = $request->zipcode;
            $useraddress->save();
            return Helper::getResponse(['status' => 200, 'message' => trans('admin.create')]);
        } catch (\Throwable $e) {
            return Helper::getResponse(['status' => 404, 'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
        }
    }

    public function editmanageaddress(Request $request, $id)
    {
        $useraddress_details = UserAddress::find($id);
        return Helper::getResponse(['status' => 200, 'data' => $useraddress_details]);
    }

    public function updatemanageaddress(Request $request)
    {

        $this->validate($request, [

            'address_type' => 'required',
            'latitude'     => 'required',
            'longitude'    => 'required',
            'flat_no'      => 'required',
            'street'       => 'required',

        ]);
        try {

            if ('Home' == $request->address_type || 'Work' == $request->address_type) {
                $UserAddress = UserAddress::where('company_id', Auth::guard('user')->user()->company_id)->where('user_id', Auth::guard('user')->user()->id)->where('address_type', $request->address_type)->where('id', '!=', $request->id)->get();

                if (count($UserAddress) > 0) {
                    //return Helper::getResponse(['status' => 404, 'message' => 'Address Type Already Exist']);
                }
                $title = null;
            } else {

                $title = (!empty($request->title)) ? $request->title : "Other";
            }
            $useraddress               = UserAddress::findOrFail($request->id);
            $useraddress->address_type = $request->address_type;
            $useraddress->landmark     = $request->landmark;
            $useraddress->flat_no      = $request->flat_no;
            $useraddress->street       = $request->street;
            $useraddress->latitude     = $request->latitude;
            $useraddress->longitude    = $request->longitude;
            $useraddress->city         = $request->city;
            $useraddress->state        = $request->state;
            $useraddress->county       = $request->county;
            $useraddress->zipcode      = $request->zipcode;
            $useraddress->map_address  = $request->map_address;
            $useraddress->title        = $title;
            $useraddress->save();
            return Helper::getResponse(['status' => 200, 'message' => trans('admin.update')]);
        } catch (\Throwable $e) {
            return Helper::getResponse(['status' => 404, 'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
        }
    }

    public function listmanageaddress(Request $request)
    {
        try {
            $useraddress_details = UserAddress::where('user_id', Auth::guard('user')->user()->id)->get();
            return Helper::getResponse(['status' => 200, 'data' => $useraddress_details]);
        } catch (\Throwable $e) {
            return Helper::getResponse(['status' => 404, 'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
        }
    }

    public function deletemanageaddress($id)
    {
        try {
            $useraddress_details = UserAddress::where('id', $id)->delete();
            return Helper::getResponse(['message' => trans('admin.user_msgs.user_delete')]);
        } catch (\Throwable $e) {
            return Helper::getResponse(['status' => 404, 'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
        }
    }

    public function updatelanguage(Request $request)
    {

        $this->validate($request, [
            'language' => 'required',
        ]);
        try {
            $user           = User::findOrFail(Auth::guard('user')->user()->id);
            $user->language = $request->language;
            $user->save();
            return Helper::getResponse(['status' => 200, 'message' => trans('admin.update')]);
        } catch (\Throwable $e) {
            return Helper::getResponse(['status' => 404, 'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Provider  $provider
     * @return \Illuminate\Http\Response
     */
    public function show_profile()
    {
        $user_details             = User::with('country', 'state', 'city')->where('id', Auth::guard('user')->user()->id)->where('company_id', Auth::guard('user')->user()->company_id)->first();
        $user_details['referral'] = (object) [];
        $user_details->makeVisible(['qrcode_url']);

        $settings = json_decode(json_encode(Setting::where('company_id', Auth::guard('user')->user()->company_id)->first()->settings_data));
        if (1 == $settings->site->referral) {
            $user_details['referral']->referral_code        = $user_details['referral_unique_id'];
            $user_details['referral']->referral_amount      = (double) $settings->site->referral_amount;
            $user_details['referral']->referral_count       = (int) $settings->site->referral_count;
            $user_details['referral']->user_referral_count  = (int) (new ReferralResource)->get_referral(1, Auth::guard('user')->user()->id)[0]->total_count;
            $user_details['referral']->user_referral_amount = (new ReferralResource)->get_referral(1, Auth::guard('user')->user()->id)[0]->total_amount;
        }
        return Helper::getResponse(['data' => $user_details]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Provider  $provider
     * @return \Illuminate\Http\Response
     */
    public function update_profile(Request $request)
    {

        if ($request->has('mobile')) {
            $request->merge([
                'mobile' => $this->cusencrypt($request->mobile, env('DB_SECRET')),
            ]);
            $mobile     = $request->mobile;
            $company_id = Auth::guard('user')->user()->company_id;
            $id         = Auth::guard('user')->user()->id;

            $this->validate($request, [
                'mobile' => [Rule::unique('users')->where(function ($query) use ($mobile, $company_id, $id) {
                    return $query->where('mobile', $mobile)->where('company_id', $company_id)->whereNotIn('id', [$id]);
                }),
                ],
            ]);

            $request->merge([
                'mobile' => $this->cusdecrypt($request->mobile, env('DB_SECRET')),
            ]);
        }

        try {
            $user = User::where('id', Auth::guard('user')->user()->id)->where('company_id', Auth::guard('user')->user()->company_id)->first();

            $user->first_name = $request->first_name;
            if ($request->has('last_name')) {
                $user->last_name = $request->last_name;
            }
            if ($request->has('email')) {
                $user->email = strtolower($request->email);
            }

            if ($request->has('language')) {
                $user->language = $request->language;
            }
            if ($request->has('mobile')) {
                $user->mobile = $request->mobile;
            }

            if ($request->has('city_id')) {
                $user->city_id = $request->city_id;
            }
            if ($request->has('country_code')) {
                $user->country_code = $request->country_code;
            }
            if ($request->has('iso2')) {
                $user->iso2 = $request->iso2;
            }
            if ($request->has('gender')) {
                $user->gender = $request->gender;
            }

            if ("no_image" == $request->picture) {
                $user->picture = "";
            }

            if ($request->hasFile('picture')) {
                $user->picture = Helper::upload_file($request->file('picture'), 'user', null, Auth::guard('user')->user()->company_id);
            }

            $user->qrcode_url = Helper::qrCode(json_encode(["country_code" => $user->country_code, 'phone_number' => $user->mobile, 'id' => base64_encode($user->unique_id)]), $user->id . '.png', $user->company_id);

            $user->save();
            return Helper::getResponse(['status' => 200, 'message' => trans('admin.update'), 'data' => $user]);
        } catch (\Throwable $e) {
            return Helper::getResponse(['status' => 404, 'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Provider  $provider
     * @return \Illuminate\Http\Response
     */
    public function password_update(Request $request)
    {
        $this->validate($request, [
            'old_password'          => 'required',
            'password'              => 'required|min:6|different:old_password',
            'password_confirmation' => 'required',
        ], ['password.different' => 'The new password and old password should not be same']);

        try {

            $User = User::where('id', Auth::guard('user')->user()->id)->where('company_id', Auth::guard('user')->user()->company_id)->first();
            if (password_verify($request->old_password, $User->password)) {
                $User->password = Hash::make($request->password);
                $User->save();
                return Helper::getResponse(['status' => 200, 'message' => trans('admin.update')]);
            } else {
                return Helper::getResponse(['status' => 422, 'message' => trans('admin.old_password_incorrect')]);
            }
        } catch (\Throwable $e) {
            return Helper::getResponse(['status' => 404, 'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
        }
    }

    public function addcard(Request $request)
    {
        $this->validate($request, [
            'stripe_token' => 'required',
        ]);

        try {

            $customer_id = $this->customer_id();
            $this->set_stripe();
            $customer = \Stripe\Customer::retrieve($customer_id);
            $card     = $customer->sources->create(["source" => $request->stripe_token]);

            $user = Auth::guard('user')->user();

            $exist = Card::where('user_id', $user->id)
                ->where('last_four', $card->last4)
                ->where('brand', $card->brand)
                ->count();

            if (0 == $exist) {
                $create_card              = new Card;
                $create_card->user_id     = $user->id;
                $create_card->card_id     = $card->id;
                $create_card->last_four   = $card->last4;
                $create_card->brand       = $card->brand;
                $create_card->company_id  = $user->company_id;
                $create_card->month       = $card->exp_month;
                $create_card->year        = $card->exp_year;
                $create_card->holder_name = $card->name;
                $create_card->funding     = $card->funding;
                $create_card->save();
            } else {
                return Helper::getResponse(['status' => 403, 'message' => trans('api.card_already')]);
            }

            return Helper::getResponse(['status' => 200, 'data' => $create_card, 'message' => trans('api.card_added')]);
        } catch (Exception $e) {
            return Helper::getResponse(['status' => 500, 'error' => $e->getMessage()]);
        }
    }

    public function carddetail(Request $request)
    {
        $cards = Card::where('user_id', Auth::guard('user')->user()->id)->where('company_id', Auth::guard('user')->user()->company_id)->get();
        return Helper::getResponse(['data' => $cards]);
    }

    public function deleteCard(Request $request, $id)
    {
        $card = Card::where('id', $id)->first();
        if ($card) {
            try {
                Card::where('id', $id)->delete();
                return Helper::getResponse(['status' => 200, 'message' => 'Card Deleted']);
            } catch (Exception $e) {
                return Helper::getResponse(['status' => 422, 'message' => 'Card Not Found', 'error' => $e->getMessage()]);
            }
        } else {
            return Helper::getResponse(['status' => 422, 'message' => 'Card Not Found']);
        }
    }

    /**
     * setting stripe.
     *
     * @return \Illuminate\Http\Response
     */
    public function set_stripe()
    {

        $settings = json_decode(json_encode(Setting::where('company_id', Auth::guard('user')->user()->company_id)->first()->settings_data));

        $paymentConfig = json_decode(json_encode($settings->payment), true);

        $cardObject = array_values(array_filter($paymentConfig, function ($e) {return 'card' == $e['name'];}));
        $card = 0;

        $stripe_secret_key      = "";
        $stripe_publishable_key = "";
        $stripe_currency        = "";

        if (count($cardObject) > 0) {
            $card = $cardObject[0]['status'];

            $stripeSecretObject = array_values(array_filter($cardObject[0]['credentials'], function ($e) {return 'stripe_secret_key' == $e['name'];}));
            $stripePublishableObject = array_values(array_filter($cardObject[0]['credentials'], function ($e) {return 'stripe_publishable_key' == $e['name'];}));
            $stripeCurrencyObject = array_values(array_filter($cardObject[0]['credentials'], function ($e) {return 'stripe_currency' == $e['name'];}));

            if (count($stripeSecretObject) > 0) {
                $stripe_secret_key = $stripeSecretObject[0]['value'];
            }

            if (count($stripePublishableObject) > 0) {
                $stripe_publishable_key = $stripePublishableObject[0]['value'];
            }

            if (count($stripeCurrencyObject) > 0) {
                $stripe_currency = $stripeCurrencyObject[0]['value'];
            }
        }

        return \Stripe\Stripe::setApiKey($stripe_secret_key);
    }

    /**
     * Get a stripe customer id.
     *
     * @return \Illuminate\Http\Response
     */
    public function customer_id()
    {
        if (Auth::guard('user')->user()->stripe_cust_id != null) {
            return Auth::guard('user')->user()->stripe_cust_id;
        } else {

            try {
                $stripe   = $this->set_stripe();
                $customer = \Stripe\Customer::create([
                    'email' => Auth::guard('user')->user()->email,
                ]);

                User::where('id', Auth::guard('user')->user()->id)->update(['stripe_cust_id' => $customer['id']]);
                return $customer['id'];
            } catch (Exception $e) {
                return $e;
            }
        }
    }

    public function walletamount()
    {

        $walletamount = User::select('wallet_balance')->where('id', Auth::guard('user')->user()->id)->where('company_id', Auth::guard('user')->user()->company_id)->first();
        return Helper::getResponse(['data' => ['amount' => $walletamount, 'currency_symbol' => Auth::guard('user')->user()->currency_symbol]]);
    }

    public function userlist()
    {

        $user_list = User::where('id', Auth::guard('user')->user()->id)->where('company_id', Auth::guard('user')->user()->company_id)->with('country')->first();
        $user_list->makeVisible(['qrcode_url']);
        return Helper::getResponse(['data' => $user_list]);
    }

    public function walletlist(Request $request)
    {
        if ($request->has('limit')) {
            $user_wallet = UserWallet::select('id', 'transaction_id', 'transaction_desc', 'transaction_alias', 'type', 'amount', 'created_at')->with(['payment_log' => function ($query) {$query->select('id', 'company_id', 'is_wallet', 'user_type', 'payment_mode', 'user_id', 'amount', 'transaction_code');}])
                ->where('company_id', Auth::guard('user')->user()->company_id)->where('user_id', Auth::guard('user')->user()->id)->orderBy('created_at', 'desc');
            $totalRecords              = $user_wallet->count();
            $user_wallet               = $user_wallet->take($request->limit)->offset($request->offset)->get();
            $response['total_records'] = $totalRecords;
            $response['data']          = $user_wallet;
            return Helper::getResponse(['data' => $response]);
        } else {
            $user_wallet = UserWallet::select('id', 'user_id', 'transaction_id', 'transaction_alias', 'transaction_desc', 'type', 'amount', 'created_at')->with(['payment_log' => function ($query) {$query->select('id', 'company_id', 'is_wallet', 'user_type', 'payment_mode', 'user_id', 'amount', 'transaction_code');}, 'user' => function ($query) {
                $query->select('id', 'currency_symbol');
            }])->where('company_id', Auth::guard('user')->user()->company_id)->where('user_id', Auth::guard('user')->user()->id);
            if ($request->has('search_text') && null != $request->search_text) {
                $user_wallet->Search($request->search_text);
            }

            if ($request->has('order_by')) {
                $user_wallet->orderby($request->order_by, $request->order_direction);
            }
            $user_wallet = $user_wallet->orderBy('created_at', 'desc')->paginate(10);
        }

        return Helper::getResponse(['data' => $user_wallet]);
    }

    public function order_status(Request $request)
    {

        $order_status = UserRequest::where('user_id', Auth::guard('user')->user()->id)
            ->whereNotIn('status', ['CANCELLED', 'SCHEDULED'])->get();
        return Helper::getResponse(['data' => $order_status]);
    }

    public function countries(Request $request)
    {
        $company_id   = base64_decode($request->salt_key);
        $country_list = CompanyCountry::with(['companyCountryCities' => function ($q) use ($company_id) {$q->where('company_id', $company_id);}])->has('companyCountryCities')->where('company_id', $company_id)->where('status', 1)->get();
        $countries = [];
        foreach ($country_list as $country) {
            $object                    = new \stdClass();
            $object->id                = $country->country->id;
            $object->country_name      = $country->country->country_name;
            $object->country_code      = $country->country->country_code;
            $object->country_phonecode = $country->country->country_phonecode;
            $object->country_currency  = $country->country->country_currency;
            $object->country_symbol    = $country->country->country_symbol;
            $object->status            = $country->country->status;
            $object->timezone          = $country->country->timezone;
            foreach ($country->companyCountryCities as $value) {
                $object->city[] = $value->city;
            }
            $countries[] = $object;
        }

        return Helper::getResponse(['data' => $countries]);
    }

    public function cities(Request $request)
    {
        $user       = \Auth::guard('user')->user();
        $company_id = $user ? $user->company_id : 1;

        $settings = json_decode(json_encode(Setting::where('company_id', $company_id)->first()->settings_data));

        $country_id = $user ? $user->country_id : $settings->site->country;

        $company_cities = CompanyCity::where('company_id', $company_id)->where('country_id', $country_id)->pluck('city_id')->all();

        $cities = City::whereIn('id', $company_cities)->get();
        return Helper::getResponse(['data' => $cities]);
    }

    public function promocode(Request $request)
    {
        $user       = \Auth::guard('user')->user();
        $company_id = $user ? $user->company_id : 1;

        $settings = json_decode(json_encode(Setting::where('company_id', $company_id)->first()->settings_data));

        $country_id = $user ? $user->country_id : $settings->site->country;

        $promocode = Promocode::with('service')->whereHas('service', function ($query) {
            $query->where('status', 1);
        })->where('company_id', $company_id)->whereDate('expiration', '>=', Carbon::today())->orderby('id', 'desc')->get();

        return Helper::getResponse(['data' => $promocode]);
    }

    public function reasons(Request $request)
    {
        $reason = Reason::where('company_id', Auth::guard('user')->user()->company_id)->where('service', $request->type)
            ->where('type', 'USER')
            ->where('status', 'active')
            ->get();

        return Helper::getResponse(['data' => $reason]);
    }

    public function notification(Request $request)
    {
        try {
            $timezone     = (Auth::guard('user')->user()->state_id) ? State::find(Auth::guard('user')->user()->state_id)->timezone : '';
            $jsonResponse = [];
            if ($request->has('limit')) {
                $notifications = Notifications::where('company_id', Auth::guard('user')->user()->company_id)
                    ->whereIn('notify_type', ['user', 'all'])->where('status', 'active')
                    ->whereDate('expiry_date', '>=', Carbon::today())
                    ->take($request->limit)->offset($request->offset)->orderby('id', 'desc')->get();
            } else {
                $notifications = Notifications::where('company_id', Auth::guard('user')->user()->company_id)->whereIn('notify_type', ['user', 'all'])->where('status', 'active')->whereDate('expiry_date', '>=', Carbon::today())->orderby('id', 'desc')->paginate(10);
            }

            if (count($notifications) > 0) {
                foreach ($notifications as $k => $val) {
                    $notifications[$k]['created_at'] = (\Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $val['created_at'], 'Asia/Kolkata'))->setTimezone($timezone)->format('Y-m-d H:i:s');
                }
            }

            $jsonResponse['total_records'] = count($notifications);
            $jsonResponse['notification']  = $notifications;
        } catch (Exception $e) {
            return response()->json(['error' => trans('api.something_went_wrong')]);
        }
        return Helper::getResponse(['data' => $jsonResponse]);
    }

    public function search_user(Request $request)
    {
        $results = [];
        $term    = $request->input('stext');
        $queries = User::where('company_id', Auth::user()->company_id)
            ->where(function ($query) use ($term) {
                $query->where('first_name', 'LIKE', $term . '%')
                    ->orWhere('last_name', 'LIKE', $term . '%');
            })->take(5)->get();
        foreach ($queries as $query) {
            $results[] = $query;
        }
        return response()->json(['success' => true, 'data' => $results]);
    }

    public function city(Request $request)
    {
        try {

            $user = Auth::guard('user')->user();

            $company_id = $user ? $user->company_id : 1;

            if ($user) {
                $city_update = User::where('company_id', $company_id)
                    ->where('id', Auth::guard('user')->user()->id)
                    ->update(['city_id' => $request['city_id']]);
            }

            return Helper::getResponse(['status' => 200, 'message' => 'Updated Successfully']);
        } catch (\Throwable $e) {
            return Helper::getResponse(['status' => 404, 'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
        }
    }

    public function defaultcard(Request $request)
    {
        try {
            $card = Card::where('card_id', $request->card_id)->get();
            if (count($card) > 0) {
                Card::where('user_id', Auth::guard('user')->user()->id)->update(['is_default' => 0]);
                Card::where('card_id', $request->card_id)->update(['is_default' => 1]);

                return Helper::getResponse(['status' => 200, 'message' => trans('admin.update')]);
            } else {
                return Helper::getResponse(['status' => 200, 'message' => "Card Not Exist"]);
            }
        } catch (\Throwable $e) {
            return Helper::getResponse(['status' => 404, 'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
        }
    }

    public function listpromocode($service = null)
    {

        $type = strtoupper($service);

        $user = Auth::guard('user')->user();

        $company_id = $user ? $user->company_id : 1;

        $promocodes = Promocode::where('company_id', $company_id)->where('service', $type)->where('expiration', '>=', Carbon::now()->format('Y-m-d H:i'))->get();

        return Helper::getResponse(['data' => $promocodes]);
    }

    public function get_chat(Request $request)
    {

        $this->validate($request, [
            'admin_service' => 'required|in:TRANSPORT,ORDER,SERVICE,DELIVERY',
            'id'            => 'required',
        ]);

        $chat = Chat::where('admin_service', $request->admin_service)->where('request_id', $request->id)->where('company_id', Auth::guard('user')->user()->company_id)->get();

        return Helper::getResponse(['data' => $chat]);
    }

    public function updateDeviceToken(Request $request)
    {
        $this->validate($request, [
            'device_token' => 'required',
        ]);
        try {
            $company_id = Auth::guard('user')->user()->company_id;
            $user_id    = Auth::guard('user')->user()->id;
            $update     = User::where('id', $user_id)->update(['device_token' => $request->device_token]);
            if ($update) {
                return Helper::getResponse(['status' => 200, 'message' => trans('admin.update')]);
            } else {
                return Helper::getResponse(['status' => 404, 'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
            }
        } catch (ModelNotFoundException $e) {
            return Helper::getResponse(['status' => 500, 'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
        }
    }

    public function wallet_transfer(Request $request)
    {
        $this->validate($request, [
            'amount' => 'required|max:10',
            'id'     => 'required_without:mobile',
            'mobile' => 'required_without:id',
        ]);

        try {

            if (Auth::guard('user')->user()->unique_id == $request->id || Auth::guard('user')->user()->mobile == $request->mobile) {
                return Helper::getResponse(['status' => 422, 'message' => trans('user.wallet_same')]);
            }

            if ($request->amount <= 0) {
                return Helper::getResponse(['status' => 422, 'message' => trans('user.lesser_amount')]);
            }

            $sender = User::where('id', Auth::guard('user')->user()->id)->first();
            $request->merge([
                'mobile' => $this->cusencrypt($request->mobile, env('DB_SECRET')),
            ]);

            $unique_id = $request->id;
            $mobile    = $request->mobile;
            $receiver  = User::whereNotNull('unique_id')->where(function ($query) use ($unique_id, $mobile) {
                $query->where('unique_id', $unique_id)
                    ->orWhere('mobile', $mobile);
            })->first();

            if (!empty($receiver)) {
                if ($sender->wallet_balance < $request->amount) {
                    return Helper::getResponse(['status' => 422, 'message' => trans('user.wallet_lesser_amount')]);
                }

                $user_data = UserWallet::orderBy('id', 'DESC')->first();
                if (!empty($user_data)) {
                    $transaction_id = $user_data->id + 1;
                } else {
                    $transaction_id = 1;
                }

                $senderWallet                    = new UserWallet;
                $senderWallet->user_id           = $sender->id;
                $senderWallet->company_id        = $sender->company_id;
                $senderWallet->transaction_id    = $transaction_id;
                $senderWallet->transaction_alias = 'USR' . str_pad($transaction_id, 6, 0, STR_PAD_LEFT);
                $senderWallet->transaction_desc  = trans('api.transaction.wallet_transfer');
                $senderWallet->type              = 'D';
                $senderWallet->amount            = $request->amount;

                if (empty($sender->wallet_balance)) {
                    $senderWallet->open_balance = 0;
                } else {
                    $senderWallet->open_balance = $sender->wallet_balance;
                }

                if (empty($sender->wallet_balance)) {
                    $senderWallet->close_balance = $request->amount;
                } else {
                    $senderWallet->close_balance = $sender->wallet_balance + $request->amount;
                }

                $senderWallet->save();

                $sender->wallet_balance = $sender->wallet_balance - $request->amount;
                $sender->save();

                $transaction_id = $senderWallet->id + 1;

                $receiverWallet                    = new UserWallet;
                $receiverWallet->user_id           = $receiver->id;
                $receiverWallet->company_id        = $receiver->company_id;
                $receiverWallet->transaction_id    = $transaction_id;
                $receiverWallet->transaction_alias = 'USR' . str_pad($transaction_id, 6, 0, STR_PAD_LEFT);
                $receiverWallet->transaction_desc  = trans('api.transaction.wallet_transfer');
                $receiverWallet->type              = 'C';
                $receiverWallet->amount            = $request->amount;

                if (empty($receiver->wallet_balance)) {
                    $receiverWallet->open_balance = 0;
                } else {
                    $receiverWallet->open_balance = $receiver->wallet_balance;
                }

                if (empty($receiver->wallet_balance)) {
                    $receiverWallet->close_balance = $request->amount;
                } else {
                    $receiverWallet->close_balance = $receiver->wallet_balance + $request->amount;
                }

                $receiverWallet->save();

                $receiver->wallet_balance = $receiver->wallet_balance + $request->amount;
                $receiver->save();

                (new SendPushNotification)->sendPushToUser($receiver->id, 'wallet', trans('user.wallet_transferred'));

                return Helper::getResponse(['status' => 200, 'message' => trans('user.wallet_transferred')]);
            } else {
                return Helper::getResponse(['status' => 422, 'message' => trans('user.wallet_type')]);
            }
        } catch (ModelNotFoundException $e) {
            return Helper::getResponse(['status' => 500, 'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
        }
    }

    public function article_category(Request $request)
    {
        $user = Auth::guard('user')->user();

        $company_id = $user ? $user->company_id : 1;

        $category_list = HealthArticleCategory::where('company_id', $company_id)
            ->get();
        return Helper::getResponse(['data' => $category_list]);
    }

    //Article Sub Category
    public function article_sub_category(Request $request, $id)
    {
        $user = Auth::guard('user')->user();

        $company_id                = $user ? $user->company_id : 1;
        $city_id                   = $user ? $user->city_id : $request->city_id;
        $article_sub_category_list = HealthArticleSubcategory::where('company_id', $company_id)->where('article_subcategory_status', 1)->where('article_category_id', $id)->get();
        return Helper::getResponse(['data' => $article_sub_category_list]);
    }

    //Article Sub Category
    public function article(Request $request, $category_id, $subcategory_id)
    {

        $user = Auth::guard('user')->user();

        $company_id = $user ? $user->company_id : 1;

        $city_id = $user ? $user->city_id : $request->city_id;

        $article = HealthArticle::where('company_id', $company_id)
            ->where('article_subcategory_id', $subcategory_id)
            ->where('article_category_id', $category_id)
            ->where('article_status', 1)
            ->get();

        return Helper::getResponse(['data' => $article]);
    }

    public function home(Request $request)
    {

        $user = Auth::guard('user')->user();

        $company_id = $user ? $user->company_id : 1;

        $city_id = $user ? $user->city_id : $request->city_id;

        $settings = json_decode(json_encode(Setting::where('company_id', $company_id)->first()->settings_data));

        $article_category = HealthArticleCategory::where('company_id', $company_id)
            ->get();
        $all_products = StoreItem::with('brand', 'attribute', 'attribute_value')->where('status', '!=', 2)->where('company_id', $company_id)->get();

        $product_timeline = $settings->order->product_timeline;
        $all_products->map(function ($products) use ($product_timeline) {
            $new_product = $products->where('id', $products->id)->where('created_at', '>', Carbon::now()->subDays($product_timeline))->first();

            if ($new_product) {
                $products->shop_status = "NEW";
            } else {
                $products->shop_status = "DISCOUNT";
            }

            $products->offer = 0;
            if ($products->item_discount) {
                $products->offer = 1;
            }

            if (0 == $products->status) {
                $products->item_status = "Item Not Available";
            } else if (1 == $products->status) {
                $products->item_status = "Available";
            }

            if ("PERCENTAGE" == $products->item_discount_type) {
                $products->product_offer = ($products->item_price - ($products->item_discount / 100) * $products->item_price);
            } else if ("AMOUNT" == $products->item_discount_type) {
                $products->product_offer = $products->item_price - $products->item_discount;
            }
            $products->product_offer = ($products->product_offer > 0) ? $products->product_offer : 0;

            $products->itemsaddon->filter(function ($addon) {
                $addon->addon_name = $addon->addon->addon_name;;
                unset($addon->addon);
                return $addon;
            });

            return $products;
        });

        $offers_banner       = ['https://api.pega10x.com/storage/1/home_services/banner1.jpg', 'https://api.pega10x.com/storage/1/home_services/banner2.jpg'];
        $new_arrivals_banner = ['https://api.pega10x.com/storage/1/home_services/banner3.jpg', 'https://api.pega10x.com/storage/1/home_services/banner4.jpg'];
        $brands              = Brand::get();

        $ambulance = Service::where('company_id', $company_id)->where('service_category_id', 2)->where('service_subcategory_id', 8)->where('service_status', 1)->first(['service_name as name', 'picture', 'description', 'emergency_contact']);

        $newArrivals = StoreItem::active()
            ->with('brand', 'attribute', 'attribute_value', 'store', 'unit')
            ->orderBy('created_at', 'desc')
            ->limit(4)
            ->get()
            ->toArray();

        $data                        = [];
        $data['services']            = Helper::getServices();
        $data['services'][]          = $ambulance;
        $data['offers_banner']       = $offers_banner;
        $data['products']            = $all_products;
        $data['new_arrivals']        = $newArrivals;
        $data['new_arrivals_banner'] = $new_arrivals_banner;
        $data['article_category']    = $article_category;
        $data['brands']              = $brands;
        $data['ambulance']           = $ambulance;

        return Helper::getResponse(['data' => $data]);
    }

    public function autoComplete(Request $request)
    {
        $this->validate($request, [
            'query' => 'required|min:3',
        ]);

        $terms = $request->input('query');

        $user = Auth::guard('user')->user();

        $data = [];

        $data['drugs']  = StoreDrug::where('name', 'like', '%' . $terms . '%')->where('company_id', Auth::guard('user')->user()->company_id)->where('status', 1)->get();
        $data['brands'] = Brand::where('name', 'like', '%' . $terms . '%')->where('company_id', Auth::guard('user')->user()->company_id)->where('status', 1)->get();

        $data['medicines'] = StoreItem::active()
            ->where('company_id', Auth::guard('user')->user()->company_id)
            ->where('item_name', 'like', '%' . $terms . '%')
            ->get();

        $data['stores'] = Store::where('status', 1)
            ->where('company_id', Auth::guard('user')->user()->company_id)
            ->where('store_name', 'like', '%' . $terms . '%')
            ->get();

        return Helper::getResponse(['data' => $data]);
    }

    public function listFaq(Request $request)
    {
        $data = Faq::get();

        return Helper::getResponse(['data' => $data]);
    }

    public function cmsPages(Request $request)
    {
        //$data = CmsPage::get();

        $page_name = $request->input('page_name');

        if (is_null($page_name) || "" == $page_name) {
            $data = CmsPage::get();
        } else {
            $data = CmsPage::where('page_name', $page_name)->get();
        }

        return Helper::getResponse(['data' => $data]);
    }
}
