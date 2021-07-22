<?php

namespace App\Http\Controllers\V1\Order\Admin\Resource;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Common\AuthLog;
use App\Models\Common\City;
use App\Models\Common\CompanyCity;
use App\Models\Common\CompanyCountry;
use App\Models\Common\State;
use App\Models\Common\Zone;
use App\Models\Order\Cuisine;
use App\Models\Order\Store;
use App\Models\Order\StoreCuisines;
use App\Models\Order\StoreOrder;
use App\Models\Order\StoreOrderInvoice;
use App\Models\Order\StoreTiming;
use App\Models\Order\StoreType;
use App\Traits\Actions;
use App\Traits\Encryptable;
use Auth;
use Carbon\Carbon;
use DB;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ShopsController extends Controller
{
    use Actions, Encryptable;

    private $model;
    private $request;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Store $model)
    {
        $this->model = $model;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $datum = Store::with('storetype')->where('company_id', Auth::user()->company_id);

        if ($request->has('search_text') && null != $request->search_text) {
            $datum->Search($request->search_text);
        }

        if ($request->has('order_by')) {
            $datum->orderby($request->order_by, $request->order_direction);
        }

        if ($request->has('page') && 'all' == $request->page) {
            $datum = $datum->get();
        } else {
            $datum = $datum->paginate(10);
        }

        return Helper::getResponse(['data' => $datum]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if ($request->has('email') && null != $request->email) {
            $request->request->add(['email' => strtolower($request->email)]);
        }

        $this->validate($request, [
            'store_name'      => 'required',
            //'cuisine_id'=>'required|exists:store,store_type_id,1',
            'store_type_id'   => 'required',
            // 'is_veg'=>'required',
            'email'           => 'required|email|max:255',
            // 'estimated_delivery_time'=>'required',
            'contact_number'  => 'required',
            'password'        => 'required|min:6',
            'store_location'  => 'required',
            'latitude'        => 'required',
            'longitude'       => 'required',
            'store_zipcode'   => 'required',
            'zone_id'         => 'required',

            'picture'         => 'mimes:jpeg,jpg,bmp,png|max:5242880',
            'password'        => 'required',
            'confirmpassword' => 'required|same:password',

        ]);
        $datum = Store::with('storetype')->where('company_id', Auth::user()->company_id)->get();
        foreach ($datum as $avail) {
            $available_name = strtoupper(str_replace(' ', '', $avail->store_name));
            if ((strtoupper(str_replace(' ', '', $request->store_name)) === $available_name) && ($avail->store_type_id == $request->store_type_id)) {
                return Helper::getResponse(['status' => 422, 'message' => ("$request->store_name Already Exists")]);
            } else if ($avail->email == $request->email) {
                return Helper::getResponse(['status' => 422, 'message' => ("$request->email Already Exists")]);
            }
        }

        $store_type = StoreType::where('id', $request->store_type_id)->first();

        if ("FOOD" == $store_type->category) {
            $this->validate($request, [
                'cuisine_id'              => 'required',
                'is_veg'                  => 'required',
                'estimated_delivery_time' => 'required',
            ]);
        }

        $request->merge([
            'email'          => $this->cusencrypt($request->email, env('DB_SECRET')),
            'contact_number' => $this->cusencrypt($request->contact_number, env('DB_SECRET')),
        ]);

        $email          = $request->email;
        $contact_number = $request->contact_number;
        $company_id     = Auth::user()->company_id;

        $this->validate($request, [
            'email'          => [Rule::unique('order.stores')->where(function ($query) use ($email, $company_id) {
                return $query->where('email', $email)->where('company_id', $company_id);
            }),
            ],
            'contact_number' => [Rule::unique('order.stores')->where(function ($query) use ($contact_number, $company_id) {
                return $query->where('contact_number', $contact_number)->where('company_id', $company_id);
            }),
            ],
        ]);

        try {

            $request->merge([
                'email'          => $this->cusdecrypt($request->email, env('DB_SECRET')),
                'contact_number' => $this->cusdecrypt($request->contact_number, env('DB_SECRET')),
            ]);
            $store                = new Store;
            $store->store_name    = $request->store_name;
            $store->store_type_id = $request->store_type_id;
            $store->status        = $request->status;
            if ($request->has('is_veg')) {
                $store->is_veg = $request->is_veg;
            } else {
                $store->is_veg = 0;
            }
            $store->store_response_time = 100;
            $store->email               = $request->email;

            if ($request->has('estimated_delivery_time')) {
                $store->estimated_delivery_time = $request->estimated_delivery_time;
            } else {
                $store->estimated_delivery_time = 0;
            }
            $store->contact_number = $request->contact_number;
            if ($request->has('password') && "" != $request->password) {
                $store->password = Hash::make($request->password);
            }

            $store->store_location        = $request->store_location;
            $store->latitude              = $request->latitude;
            $store->longitude             = $request->longitude;
            $store->store_zipcode         = $request->store_zipcode;
            $store->contact_person        = $request->contact_person;
            $store->picture               = $request->picture;
            $store->store_packing_charges = $request->store_packing_charges;
            $country                      = CompanyCountry::where('company_id', $company_id)->where('country_id', $request->country_id)->first();
            $store->currency_symbol       = $country->currency;
            $store->store_gst             = $request->store_gst;
            $store->offer_min_amount      = $request->offer_min_amount;
            $store->offer_percent         = $request->offer_percent;
            $store->description           = $request->description;
            $store->offer_percent         = $request->offer_percent;
            $store->country_id            = $request->country_id;
            $store->city_id               = $request->city_id;
            $store->store_gst             = $request->store_gst;
            $store->commission            = $request->commission;
            $store->country_code          = $request->country_code;
            $store->iso2                  = $request->iso2;
            $store->zone_id               = $request->zone_id;
            if ($request->has('free_delivery')) {
                $store->free_delivery = $request->free_delivery;
            } else {
                $store->free_delivery = 0;
            }
            $store->free_delivery_limit = $request->free_delivery_limit ? $request->free_delivery_limit : 0;

            if ($request->has('bestseller')) {
                $store->bestseller = $request->bestseller;
            }

            if ($request->has('bestseller_month')) {
                $store->bestseller_month = $request->bestseller_month;
            }

            if ($request->hasFile('picture')) {
                $store->picture = Helper::upload_file($request->file('picture'), 'shops/profile');
            }
            $store->company_id = Auth::user()->company_id;
            $store->save();
            if ("FOOD" == $store_type->category) {
                foreach ($request->cuisine_id as $k => $v) {
                    $cuisine                = new StoreCuisines;
                    $cuisine->store_type_id = $request->store_type_id;
                    $cuisine->store_id      = $store->id;
                    $cuisine->cuisines_id   = $v;
                    $cuisine->company_id    = Auth::user()->company_id;
                    $cuisine->save();
                }
            }

            if ($request->has('day')) {
                $city     = City::find($store->city_id);
                $state    = State::find($city->state_id);
                $timezone = $state->timezone;

                $start_time = $request->hours_opening;
                $end_time   = $request->hours_closing;

                foreach ($request->day as $key => $day) {
                    $timing[] = [
                        'store_start_time' => (Carbon::createFromFormat('H:i', (Carbon::parse($start_time[$day])->format('H:i')), $timezone))->setTimezone('Asia/Kolkata'),
                        'store_end_time'   => (Carbon::createFromFormat('H:i', (Carbon::parse($end_time[$day])->format('H:i')), $timezone))->setTimezone('Asia/Kolkata'),
                        'store_id'         => $store->id,
                        'store_day'        => $day,
                        'company_id'       => Auth::user()->company_id,
                    ];
                }

                StoreTiming::insert($timing);
            }

            return Helper::getResponse(['status' => 200, 'message' => trans('admin.create')]);
        } catch (\Throwable $e) {
            return Helper::getResponse(['status' => 404, 'message' => trans('admin.something_wrong'), 'error' => $e->getMessage(), 'data' => $store]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Dispatcher  $account
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {

        try {

            $store = Store::findOrFail($id);

            $city = City::find($store->city_id);

            $state = State::where('id', $city->state_id)->first();

            $timezone = $state->timezone;

            $store['store_type']     = StoreType::where('id', $store['store_type_id'])->first();
            $store['cui_selectdata'] = StoreCuisines::where('store_id', $id)->pluck('cuisines_id')->all();
            $store['cuisine_data']   = Cuisine::where('store_type_id', $store['store_type_id'])->where('status', 1)->get();
            $store['time_data']      = StoreTiming::where('store_id', $id)->get();
            foreach ($store['time_data'] as $k => $v) {
                $store['time_data'][$k]['store_start_time'] = (Carbon::createFromFormat('H:i', (Carbon::parse($v['store_start_time'])->format('H:i')), 'Asia/Kolkata'))->setTimezone($timezone)->format('H:i');
                $store['time_data'][$k]['store_end_time']   = (Carbon::createFromFormat('H:i', (Carbon::parse($v['store_end_time'])->format('H:i')), 'Asia/Kolkata'))->setTimezone($timezone)->format('H:i');
            }
            $store['city_data'] = CompanyCity::where("country_id", $store['country_id'])->with('city')->get();
            if (!empty(Auth::user())) {
                $this->company_id = Auth::user()->company_id;
            } else {
                $this->company_id = Auth::guard('shop')->user()->company_id;
            }
            $store['zone_data'] = Zone::where("city_id", $store['city_id'])->where('company_id', $this->company_id)->where('user_type', "PHARMACY")->get();

            return Helper::getResponse(['data' => $store]);
        } catch (\Throwable $e) {
            return Helper::getResponse(['status' => 404, 'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\account  $account
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {

        if ($request->has('email') && null != $request->email) {
            $request->request->add(['email' => strtolower($request->email)]);
        }

        $this->validate($request, [
            'store_name'     => 'required',
            //'cuisine_id'=>'required',
            'store_type_id'  => 'required',
            // 'is_veg'=>'required',
            'email'          => 'required|email|max:255',
            // 'estimated_delivery_time'=>'required',
            'contact_number' => 'required',

            'store_location' => 'required',
            'latitude'       => 'required',
            'longitude'      => 'required',
            'store_zipcode'  => 'required',
            'zone_id'        => 'required',

        ]);

        if (!empty(Auth::user())) {
            $this->company_id = $company_id = Auth::user()->company_id;
        } else {
            $this->company_id = $company_id = Auth::guard('shop')->user()->company_id;
        }

        $datum = Store::with('storetype')->where('company_id', $this->company_id)->get();

        foreach ($datum as $avail) {
            $available_name = strtoupper(str_replace(' ', '', $avail->store_name));
            if ((strtoupper(str_replace(' ', '', $request->store_name)) === $available_name) && ($avail->store_type_id == $request->store_type_id) && ($avail->id != $request->id)) {
                return Helper::getResponse(['status' => 422, 'message' => ("$request->store_name Already Exists")]);
            } else if (($avail->email == $request->email) && ($avail->id != $request->id)) {
                return Helper::getResponse(['status' => 422, 'message' => ("Email Already Exists")]);
            }
        }

        $store_type = StoreType::where('id', $request->store_type_id)->first();

        if ("FOOD" == $store_type->category) {
            $this->validate($request, [
                'cuisine_id'              => 'required',
                'is_veg'                  => 'required',
                'estimated_delivery_time' => 'required',
            ]);
        }

        $request->merge([
            'email'          => $this->cusencrypt($request->email, env('DB_SECRET')),
            'contact_number' => $this->cusencrypt($request->contact_number, env('DB_SECRET')),
        ]);

        $email          = $request->email;
        $contact_number = $request->contact_number;
        if (!empty(Auth::user())) {
            $this->company_id = $company_id = Auth::user()->company_id;
        } else {
            $this->company_id = $company_id = Auth::guard('shop')->user()->company_id;
        }

        $this->validate($request, [
            'email'          => [Rule::unique('order.stores')->where(function ($query) use ($email, $company_id, $id) {
                return $query->where('email', $email)->where('company_id', $company_id)->whereNotIn('id', [$id]);
            }),
            ],
            'contact_number' => [Rule::unique('order.stores')->where(function ($query) use ($contact_number, $company_id, $id) {
                return $query->where('contact_number', $contact_number)->where('company_id', $company_id)->whereNotIn('id', [$id]);
            }),
            ],
        ]);

        try {

            $request->merge([
                'email'          => $this->cusdecrypt($request->email, env('DB_SECRET')),
                'contact_number' => $this->cusdecrypt($request->contact_number, env('DB_SECRET')),
            ]);

            $store                = Store::findOrFail($id);
            $store->store_name    = $request->store_name;
            $store->store_type_id = $request->store_type_id;
            $store->status        = $request->status;
            if ($request->has('is_veg')) {
                $store->is_veg = $request->is_veg;
            } else {
                $store->is_veg = 0;
            }
            $store->store_response_time = 100;
            $store->email               = $request->email;
            if ($request->has('estimated_delivery_time')) {
                $store->estimated_delivery_time = $request->estimated_delivery_time;
            } else {
                $store->estimated_delivery_time = 0;
            }
            $store->contact_number = $request->contact_number;
            if ($request->has('password') && !empty($request->password)) {
                $store->password = Hash::make($request->password);
            }
            $store->store_location = $request->store_location;
            if ($request->flat_no) {
                $store->flat_no = $request->flat_no;
            }

            if ($request->street) {
                $store->street = $request->street;
            }

            $store->latitude              = $request->latitude;
            $store->longitude             = $request->longitude;
            $store->store_zipcode         = $request->store_zipcode;
            $store->contact_person        = $request->contact_person;
            $store->store_packing_charges = $request->store_packing_charges;
            $store->zone_id               = $request->zone_id;
            $store->store_gst             = $request->store_gst;
            $store->offer_min_amount      = $request->offer_min_amount;
            $store->offer_percent         = $request->offer_percent;
            $store->description           = $request->description;
            $store->country_id            = $request->country_id;
            $store->city_id               = $request->city_id;
            $store->commission            = $request->commission;
            $store->country_code          = $request->country_code;
            $store->iso2                  = $request->iso2;
            if ($request->has('free_delivery')) {
                $store->free_delivery = $request->free_delivery;
            } else {
                $store->free_delivery = 0;
            }
            $store->free_delivery_limit = $request->free_delivery_limit;
            if ($request->has('bestseller')) {
                $store->bestseller = $request->bestseller;
            }

            if ($request->has('bestseller_month')) {
                $store->bestseller_month = $request->bestseller_month;
            }

            if ($request->hasFile('picture')) {
                $store->picture = Helper::upload_file($request->file('picture'), 'shops/profile');
            }
            $storedata = $store->update();
            if ("FOOD" == $store_type->category) {
                StoreCuisines::where('store_id', $id)->delete();
                foreach ($request->cuisine_id as $k => $v) {
                    $cuisine                = new StoreCuisines;
                    $cuisine->store_type_id = $request->store_type_id;
                    $cuisine->store_id      = $store->id;
                    $cuisine->cuisines_id   = $v;
                    $cuisine->company_id    = $this->company_id;
                    $cuisine->save();
                }
            }
            if ($request->has('day')) {
                $city = City::find($store->city_id);

                $state = State::where('id', $city->state_id)->first();

                $timezone = $state->timezone;

                StoreTiming::where('store_id', $id)->delete();
                $start_time = $request->hours_opening;
                $end_time   = $request->hours_closing;
                foreach ($request->day as $key => $day) {
                    $timing[] = [
                        'store_start_time' => (Carbon::createFromFormat('H:i', (Carbon::parse($start_time[$day])->format('H:i')), $timezone))->setTimezone('Asia/Kolkata'),
                        'store_end_time'   => (Carbon::createFromFormat('H:i', (Carbon::parse($end_time[$day])->format('H:i')), $timezone))->setTimezone('Asia/Kolkata'),
                        'store_id'         => $store->id,
                        'store_day'        => $day,
                        'company_id'       => $this->company_id,
                    ];
                }
                StoreTiming::insert($timing);
            }
            $store->update();
            return Helper::getResponse(['status' => 200, 'message' => trans('admin.update')]);
        } catch (\Throwable $e) {
            return Helper::getResponse(['status' => 404, 'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Account  $dispatcher
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        StoreCuisines::where('store_id', $id)->delete();
        StoreTiming::where('store_id', $id)->delete();
        return $this->removeModel($id);
    }

    public function shoptimings(Request $request)
    {

        try {
            $store = Store::findOrFail(Auth::guard('shop')->user()->id);

            $city = City::find($store->city_id);

            $state = State::where('id', $city->state_id)->first();

            $timezone = $state->timezone;
            $datum    = StoreTiming::where('store_id', Auth::guard('shop')->user()->id)->get();

            foreach ($datum as $k => $v) {
                $datum[$k]['store_start_time'] = (Carbon::createFromFormat('H:i', (Carbon::parse($v['store_start_time'])->format('H:i')), 'Asia/Kolkata'))->setTimezone($timezone)->format('H:i');
                $datum[$k]['store_end_time']   = (Carbon::createFromFormat('H:i', (Carbon::parse($v['store_end_time'])->format('H:i')), 'Asia/Kolkata'))->setTimezone($timezone)->format('H:i');
            }
            return Helper::getResponse(['data' => $datum]);
        } catch (\Throwable $e) {
            return Helper::getResponse(['status' => 404, 'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
        }
    }

    public function updateshoptimings(Request $request)
    {

        try {

            if (!empty(Auth::user())) {
                $this->company_id = Auth::user()->company_id;
            } else {
                $this->company_id = Auth::guard('shop')->user()->company_id;
            }

            $store = Store::findOrFail(Auth::guard('shop')->user()->id);

            $city = City::find($store->city_id);

            $state = State::where('id', $city->state_id)->first();

            $timezone = $state->timezone;

            StoreTiming::where('store_id', Auth::guard('shop')->user()->id)->delete();
            $start_time = $request->hours_opening;
            $end_time   = $request->hours_closing;
            foreach ($request->day as $key => $day) {
                $timing[] = [
                    'store_start_time' => (Carbon::createFromFormat('H:i', (Carbon::parse($start_time[$day])->format('H:i')), $timezone))->setTimezone('Asia/Kolkata'),
                    'store_end_time'   => (Carbon::createFromFormat('H:i', (Carbon::parse($end_time[$day])->format('H:i')), $timezone))->setTimezone('Asia/Kolkata'),
                    'store_id'         => $store->id,
                    'store_day'        => $day,
                    'company_id'       => $this->company_id,
                ];
            }
            StoreTiming::insert($timing);

            return Helper::getResponse(['status' => 200, 'message' => trans('admin.update')]);
        } catch (\Throwable $e) {
            return Helper::getResponse(['status' => 404, 'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
        }
    }

    public function updateStatus(Request $request, $id)
    {

        try {

            $datum = Store::findOrFail($id);

            if ($request->has('status')) {
                if (1 == $request->status) {
                    $datum->status = 0;
                } else {
                    $datum->status = 1;
                }
            }
            $datum->save();

            return Helper::getResponse(['status' => 200, 'message' => trans('admin.activation_status')]);
        } catch (\Throwable $e) {
            return Helper::getResponse(['status' => 404, 'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
        }
    }

    public function walletDetails($type, $id)
    {

        $date = \Carbon\Carbon::today()->subDays(15);

        $wallet_details = UserWallet::where('user_id', $id)->orderBy('created_at', 'DESC')->whereDate('created_at', '>', $date)->paginate(10);

        return Helper::getResponse(['data' => $wallet_details]);
    }

    public function logDetails($id)
    {

        $date = \Carbon\Carbon::today()->subDays(7);

        $datum = AuthLog::where('user_type', "Shop")->where('user_id', $id)->orderBy('created_at', 'DESC')->whereDate('created_at', '>', $date)->paginate(5);

        return Helper::getResponse(['data' => $datum]);
    }

    public function getStorePriceCities()
    {
        $cityList = CompanyCountry::with('country', 'companyCountryCities')->where('company_id', Auth::user()->company_id)->where('status', 1)->get();
        return Helper::getResponse(['data' => $cityList]);
    }

    public function dashboarddata($id)
    {
        try {
            $data['storedata']    = Store::where('country_id', $id)->where('company_id', \Auth::user()->company_id)->count();
            $data['overall_data'] = StoreOrder::where('country_id', $id)->where('company_id', \Auth::user()->company_id)->where('status', 'COMPLETED')->count();
            return Helper::getResponse(['status' => 200, 'data' => $data]);
        } catch (Exception $e) {
            return Helper::getResponse(['status' => 500, 'message' => trans('api.something_went_wrong'), 'error' => $e->getMessage()]);
        }
    }

    public function storedashboard($id)
    {
        try {

            $completed = StoreOrder::where('country_id', $id)->where('status', 'COMPLETED')->where('company_id', Auth::user()->company_id)->get(['id', 'created_at', 'timezone'])->groupBy(function ($date) {
                return Carbon::parse($date->created_at)->format('m');
            });
            $cancelled = StoreOrder::where('country_id', $id)->where('status', 'CANCELLED')->where('company_id', Auth::user()->company_id)->get(['id', 'created_at', 'timezone'])->groupBy(function ($date) {
                return Carbon::parse($date->created_at)->format('m');
            });

            $month = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'];

            foreach ($month as $k => $v) {
                if (empty($completed[$v])) {
                    $complete[] = 0;
                } else {
                    $complete[] = count($completed[$v]);
                }

                if (empty($cancelled[$v])) {
                    $cancel[] = 0;
                } else {
                    $cancel[] = count($cancelled[$v]);
                }
            }

            $data['cancelled_data'] = $cancel;
            $data['completed_data'] = $complete;
            $data['max']            = max($complete);

            if (max($complete) < max($cancel)) {
                $data['max'] = max($cancel);
            }

            return Helper::getResponse(['status' => 200, 'data' => $data]);
        } catch (Exception $e) {
            return Helper::getResponse(['status' => 500, 'message' => trans('api.something_went_wrong'), 'error' => $e->getMessage()]);
        }
    }

    public function storeStatementHistory(Request $request)
    {
        try {
            $history_status = ['CANCELLED', 'COMPLETED'];
            if ($request->has('search_text') && null != $request->search_text) {
                $storeLists = Store::testsearch($request->search_text)->select('*', 'created_at as joined')->where('company_id', Auth::user()->company_id);
            } else {
                $storeLists = Store::select('*', 'created_at as joined')->where('company_id', Auth::user()->company_id);
            }
            if ($request->has('country_id')) {
                $storeLists->where('country_id', $request->country_id);
            }
            if (Auth::user()->hasRole('FLEET')) {
                $storeLists->where('admin_id', Auth::user()->id);
            }

            if ($request->has('order_by')) {
                $storeLists->orderby($request->order_by, $request->order_direction);
            }
            $type = isset($_GET['type']) ? $_GET['type'] : '';
            if ('today' == $type) {
                $storeLists->where('created_at', '>=', Carbon::today());
            } elseif ('monthly' == $type) {
                $storeLists->where('created_at', '>=', Carbon::now()->month);
            } elseif ('yearly' == $type) {
                $storeLists->where('created_at', '>=', Carbon::now()->year);
            } elseif ('range' == $type) {
                if ($request->has('from') && $request->has('to')) {
                    if ($request->from == $request->to) {
                        $storeLists->whereDate('created_at', date('Y-m-d', strtotime($request->from)));
                    } else {
                        $storeLists->whereBetween('created_at', [Carbon::createFromFormat('Y-m-d', $request->from), Carbon::createFromFormat('Y-m-d', $request->to)]);
                    }
                }
            } else {
                // dd(5);
            }
            $cancelservices = $storeLists;
            $orderCounts    = $storeLists->count();
            $dataval        = $storeLists->where('status', 1)->paginate(10);
            $cancelledQuery = $cancelservices->where('status', 1)->count();
            $total_earnings = 0;
            foreach ($dataval as $shop) {
                $shop->status = 1 == $shop->status ? 'Enabled' : 'Disable';
                $shopid       = $shop->id;
                $earnings     = StoreOrderInvoice::select('cart_details', DB::raw('sum(total_amount - delivery_amount - commision_amount) as total_amount'))
                    ->where('store_id', $shopid)->where('company_id', Auth::user()->company_id)->first();
                if (null != $earnings) {
                    $shop->earnings = $earnings->total_amount;
                    $total_earnings = $total_earnings + $earnings->total_amount;
                } else {
                    $shop->earnings = 0;
                }
            }

            $data['stores']           = $dataval;
            $data['total_orders']     = $orderCounts;
            $data['revenue_value']    = $total_earnings;
            $data['cancelled_orders'] = $cancelledQuery;
            return Helper::getResponse(['data' => $data]);
        } catch (\Throwable $e) {
            return Helper::getResponse(['status' => 404, 'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
        }
    }

    public function searchprovider(Request $request, $id)
    {
        $provider = Store::where('zone_id', $id)
            ->where('store_name', 'like', "%" . $request->term . "%")
            ->select(\DB::raw("CONCAT(first_name,' ',last_name,'(',id,')') AS label"), \DB::raw("CONCAT(store_name,'(',id,')') AS value"), 'id', 'wallet_balance', 'zone_id', 'store_name')->get()->toArray();
        return $provider;
    }

    public function push_subscription(Request $request)
    {

        $this->validate($request, [
            'endpoint'    => 'required',
            'keys.auth'   => 'required',
            'keys.p256dh' => 'required',
        ]);

        $endpoint = $request->endpoint;
        $token    = $request->keys['auth'];
        $key      = $request->keys['p256dh'];
        $user     = Auth::guard('shop')->user();
        $user->updatePushSubscription($endpoint, $key, $token, null, 'shop');

        return response()->json(['success' => true], 200);
    }
}
