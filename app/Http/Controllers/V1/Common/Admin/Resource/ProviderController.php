<?php

namespace App\Http\Controllers\V1\Common\Admin\Resource;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Common\CompanyCity;
use App\Models\Common\CompanyCountry;
use App\Models\Common\Document;
use App\Models\Common\Notifications;
use App\Models\Common\Provider;
use App\Models\Common\ProviderAddress;
use App\Models\Common\ProviderDocument;
use App\Models\Common\ProviderService;
use App\Models\Common\ProviderVehicle;
use App\Models\Common\Setting;
use App\Services\ReferralResource;
use App\Services\SendPushNotification;
use App\Services\Transactions;
use App\Traits\Actions;
use App\Traits\Encryptable;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProviderController extends Controller
{
    use Actions;
    use Encryptable;

    private $model;
    private $request;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Provider $model)
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
        $datum = Provider::with('admin')->where('company_id', Auth::user()->company_id);

        $datum->where('type', $request->type);

        if (Auth::user()->type == 'FLEET') {
            $datum->where('admin_id', Auth::user()->id);
        }

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
            'first_name'   => 'required|max:255',
            'last_name'    => 'required|max:255',
            'country_code' => 'required|max:25',
            'email'        => 'required|email|max:255',
            'mobile'       => 'required|digits_between:6,13',
            'iso2'         => 'required',
            'picture'      => 'mimes:jpeg,jpg,bmp,png|max:5242880',
            'password'     => 'required|min:6|confirmed',
            'country_id'   => 'required',
            'city_id'      => 'required',
            'type'         => 'required',
        ]);

        $request->merge([
            'email'  => $this->cusencrypt($request->email, env('DB_SECRET')),
            'mobile' => $this->cusencrypt($request->email, env('DB_SECRET')),
        ]);

        $company_id = Auth::user()->company_id;
        $email      = $request->email;
        $mobile     = $request->mobile;

        $setting    = Setting::where('company_id', $company_id)->first();
        $settings   = json_decode(json_encode($setting->settings_data));
        $siteConfig = $settings->site;
        $this->validate($request, [
            'email'  => [Rule::unique('providers')->where(function ($query) use ($email, $company_id) {
                return $query->where('email', $email)->where('company_id', $company_id);
            }),
            ],
            'mobile' => [Rule::unique('providers')->where(function ($query) use ($mobile, $company_id) {
                return $query->where('mobile', $mobile)->where('company_id', $company_id);
            }),
            ],
        ]);

        try {

            $request->merge([
                'email'  => $this->cusdecrypt($request->email, env('DB_SECRET')),
                'mobile' => $this->cusdecrypt($request->mobile, env('DB_SECRET')),
            ]);

            // $provider = $request->all();
            // $request->request->add(['company_id' => \Auth::user()->company_id]);
            // $provider['password'] =Hash::make($request->password);
            $provider               = new Provider;
            $provider->type         = $request->type;
            $provider->admin_id     = Auth::user()->id;
            $provider->company_id   = Auth::user()->company_id;
            $provider->first_name   = $request->first_name;
            $provider->last_name    = $request->last_name;
            $provider->email        = $request->email;
            $provider->country_code = $request->country_code;
            $provider->mobile       = $request->mobile;
            $provider->password     = Hash::make($request->password);
            $provider->country_id   = $request->country_id;
            $provider->city_id      = $request->city_id;
            $provider->iso2         = $request->iso2;
            if ($request->hasFile('picture')) {
                $provider['picture']  = Helper::upload_file($request->file('picture'), 'provider/profile');
                $provider->is_profile = 1;
            }

            $country                      = CompanyCountry::where('company_id', Auth::user()->company_id)->where('country_id', $request->country_id)->first();
            $provider->currency_symbol    = $country->currency;
            $provider->referral_unique_id = (new ReferralResource)->generateCode($company_id);

            $provider->save();
            $provider->unique_id  = $siteConfig->provider_unique_id . $provider->id;
            $provider->qrcode_url = Helper::qrCode(json_encode(["country_code" => $request->country_code, 'phone_number' => $request->mobile]), $provider->id . '.png', Auth::user()->company_id);
            $provider->save();

            $request->merge(["body" => "registered"]);
            $this->sendUserData($request->all());

            //$provider = Provider::create($provider);
            return Helper::getResponse(['status' => 200, 'message' => trans('admin.create')]);
        } catch (\Throwable $e) {
            return Helper::getResponse(['status' => 404, 'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Provider  $provider
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $provider              = Provider::findOrFail($id);
            $provider['city_data'] = CompanyCity::where("country_id", $provider['country_id'])->with('city')->get();
            return Helper::getResponse(['data' => $provider]);
        } catch (\Throwable $e) {
            return Helper::getResponse(['status' => 404, 'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Provider  $provider
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {

        if ($request->has('email') && null != $request->email) {
            $request->request->add(['email' => strtolower($request->email)]);
        }

        $this->validate($request, [
            'first_name'   => 'required|max:255',
            'last_name'    => 'required|max:255',
            'country_code' => 'required|max:25',
            'iso2'         => 'required',
            'email'        => null != $request->email ? 'sometimes|required|email|max:255' : '',
            'mobile'       => null != $request->email ? 'sometimes|digits_between:6,13' : '',
            'country_id'   => 'required',
            'city_id'      => 'required',
            'type'         => 'required',
        ]);

        $company_id = Auth::user()->company_id;
        if ($request->has('email') && $request->has('mobile')) {
            $request->merge([
                'email'  => $this->cusencrypt($request->email, env('DB_SECRET')),
                'mobile' => $this->cusencrypt($request->mobile, env('DB_SECRET')),
            ]);

            $email  = $request->email;
            $mobile = $request->mobile;

            $this->validate($request, [
                'email'  => [Rule::unique('providers')->where(function ($query) use ($email, $company_id, $id) {
                    return $query->where('email', $email)->where('company_id', $company_id)->whereNotIn('id', [$id]);
                }),
                ],
                'mobile' => [Rule::unique('providers')->where(function ($query) use ($mobile, $company_id, $id) {
                    return $query->where('mobile', $mobile)->where('company_id', $company_id)->whereNotIn('id', [$id]);
                }),
                ],
            ]);
        }

        try {

            if ($request->has('email') && $request->has('mobile')) {
                $request->merge([
                    'email'  => $this->cusdecrypt($request->email, env('DB_SECRET')),
                    'mobile' => $this->cusdecrypt($request->mobile, env('DB_SECRET')),
                ]);
            }

            $provider             = Provider::findOrFail($id);
            $provider->type       = $request->type;
            $provider->first_name = $request->first_name;
            $provider->last_name  = $request->last_name;
            if ($request->has('email') && $request->has('mobile')) {
                $provider->country_code = $request->country_code;
                $provider->mobile       = $request->mobile;
                $provider->email        = $request->email;
            }
            $provider->password   = Hash::make($request->password);
            $provider->country_id = $request->country_id;
            $provider->city_id    = $request->city_id;
            $provider->iso2       = $request->iso2;
            if ($request->hasFile('picture')) {
                $provider['picture']  = Helper::upload_file($request->file('picture'), 'provider/profile');
                $provider->is_profile = 1;
            }

            $country                   = CompanyCountry::where('company_id', Auth::user()->company_id)->where('country_id', $request->country_id)->first();
            $provider->currency_symbol = $country->currency;

            $provider->save();

            // $request->merge(["body"=>"updated"]);
            // if($request->has('email') && $request->has('mobile')) {
            //     $this->sendUserData($request->all());
            // }

            return Helper::getResponse(['status' => 200, 'message' => trans('admin.update')]);
        } catch (\Throwable $e) {
            return Helper::getResponse(['status' => 404, 'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
        }
    }

    public function updateStatus(Request $request, $id)
    {

        try {

            $datum = Provider::findOrFail($id);
            //if($datum->is_service==1 && $datum->is_document==1 && $datum->is_bankdetail==1 ){
            if ($request->has('status')) {
                if ("Enable" == $request->status) {
                    //$datum->status = "APPROVED";
                    $datum->activation_status = "1";
                    $datum->is_online         = "1";
                } else {
                    //$datum->status = "BANNED";
                    $datum->activation_status = "0";
                    $datum->is_online         = "0";
                }
            }
            $datum->save();

            if ("Disable" == $request->status) {
                $status = "Enable";
            } else {
                $status = "Disable";
                if (null != $datum->jwt_token) {
                    Auth::guard('provider')->setToken($datum->jwt_token);
                    try {
                        Auth::guard('provider')->invalidate();
                    } catch (\Throwable $e) {}
                    $datum->jwt_token = null;
                    $datum->save();
                }
            }

            $datum['body'] = $status;

            //$this->sendUserData($datum);

            (new SendPushNotification)->updateProviderStatus($datum->id, 'provider', 'Provider ' . $status . 'd successfully', 'Account Info', json_encode(['service' => $datum->is_service, 'document' => $datum->is_document, 'bank' => $datum->is_bankdetail]));

            $requestData = ['type' => 'PROVIDER', 'room' => 'room_provider_' . $datum->company_id . '_' . $id,
                'id'                   => $id];
            app('redis')->publish('providerUpdate', json_encode($requestData));

            return Helper::getResponse(['status' => 200, 'message' => trans('admin.activation_status')]);
            //} else{
            //return Helper::getResponse(['status' => 200, 'message' =>'Status Not Updated Contact Admin']);
            // }
        } catch (\Throwable $e) {
            return Helper::getResponse(['status' => 404, 'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
        }
    }

    public function approveStatus(Request $request, $id)
    {

        try {
            $datum = Provider::findOrFail($id);

            /*$provider_services = ProviderService::with('admin_service')->where('provider_id',$id)->get();
            $admin_services = [];
            if(count($provider_services) > 0){
            foreach ($provider_services as $key => $value) {
            $admin_services[] = $value->admin_service;
            }
            } else {
            return Helper::getResponse(['status' => 200, "data"=>['status'=>1],'message' => 'Please Add Service']);
            }

            $provider_doc=ProviderDocument::where('provider_id',$id)->whereHas('document', function($q) use($admin_services) { $q->whereIn('service', $admin_services ); })->count();
            $provider_status=ProviderDocument::where('provider_id',$id)->whereHas('document', function($q) use($admin_services) { $q->whereIn('service', $admin_services ); })->where('status','ACTIVE')->count();

            if(($provider_doc==$provider_status)  ){
            $datum->is_document="1";

            $datum->save();
            }

            if($datum->is_service!=1){
            return Helper::getResponse(['status' => 200, "data"=>['status'=>1],'message' => 'Please Add Service']);
            }else if($datum->is_document!=1){
            return Helper::getResponse(['status' => 200, "data"=>['status'=>1],'message' => 'Please Add Document']);
            }else if($datum->is_profile!=1){
            return Helper::getResponse(['status' => 200, "data"=>['status'=>1],'message' => 'Please Add Profile Picture']);
            }else if($datum->is_bankdetail!=1){
            return Helper::getResponse(['status' => 200, "data"=>['status'=>1],'message' => 'Please Add Bankdetails']);
            }else if(empty($datum->zone_id)){
            return Helper::getResponse(['status' => 200, "data"=>['status'=>1],'message' => 'Please Select Zone For the Provider ']);
            }

            $transport_services = ProviderService::where('admin_service','TRANSPORT')->where('provider_id',$id)->where('status','ASSESSING')->get();
            $services = ProviderService::where('admin_service','SERVICE')->where('provider_id',$id)->where('status','ASSESSING')->get();
            $order_services = ProviderService::where('admin_service','ORDER')->where('provider_id',$id)->where('status','ASSESSING')->get();

            if(!$transport_services->isEmpty())
            {
            return Helper::getResponse(['status' => 404, "data"=>['status'=>1],'message' => 'Please Verify Transport Service']);
            }
            else if (!$order_services->isEmpty()) {
            return Helper::getResponse(['status' => 404, "data"=>['status'=>1],'message' => 'Please Verify Order Service']);
            }
            elseif (!$services->isEmpty()) {
            return Helper::getResponse(['status' => 404, "data"=>['status'=>1],'message' => 'Please Verify Service']);
            }

            if($datum->is_service==1 && $datum->is_document==1 && $datum->is_bankdetail==1 && ($provider_doc==$provider_status)  ){
            $datum->status="APPROVED";
            $datum->activation_status="1";
            $datum->save();
            $datum['body'] = "APPROVED";
            //$this->sendUserData($datum);

            $datum->is_document="1";
            $datum->status="APPROVED";
            $datum->activation_status="1";

            (new SendPushNotification)->updateProviderStatus($datum->id, 'provider', trans('admin.activation_status'), 'Account Info', json_encode(['service' => $datum->is_service, 'document' => $datum->is_document, 'bank' => $datum->is_bankdetail]));
            $requestData = ['type' => 'PROVIDER', 'room' => 'room_provider_'.$datum->company_id.'_'.$id,
            'id' => $id ];
            app('redis')->publish('providerUpdate', json_encode( $requestData ));

            return Helper::getResponse(['status' => 200,"data"=>['status'=>0],'message' => trans('admin.activation_status')]);
            } else{
            return Helper::getResponse(['status' => 200,"data"=>['status'=>1], 'message' =>'Status Not Updated Contact Admin']);
            }*/

            $datum->is_document       = "1";
            $datum->status            = "APPROVED";
            $datum->activation_status = "1";
            $datum->save();

            (new SendPushNotification)->updateProviderStatus($datum->id, 'provider', trans('admin.activation_status'), 'Account Info', json_encode(['service' => $datum->is_service, 'document' => $datum->is_document, 'bank' => $datum->is_bankdetail]));

            $requestData = ['type' => 'PROVIDER', 'room' => 'room_provider_' . $datum->company_id . '_' . $id,
                'id'                   => $id];
            app('redis')->publish('providerUpdate', json_encode($requestData));

            return Helper::getResponse(['status' => 200, "data" => ['status' => 0], 'message' => trans('admin.activation_status')]);
        } catch (\Throwable $e) {
            return Helper::getResponse(['status' => 404, 'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
        }
    }

    public function zoneStatus(Request $request, $id)
    {

        try {
            $datum = Provider::where('id', $id)->update(['zone_id' => $request->zone_id]);

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
    public function destroy($id)
    {
        $datum    = Provider::findOrFail($id);
        $settings = Setting::where('company_id', Auth::user()->company_id)->first()->settings_data->site;

        if (!empty($settings->send_email) && 1 == $settings->send_email) {
            $datum['body']        = "deleted";
            $datum['mail_driver'] = $settings->mail_driver;

            $this->sendUserData($datum);
        }

        return $this->removeModel($id);
    }

    //For vehicle service type
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Provider  $provider
     * @return \Illuminate\Http\Response
     */
    public function vehicle_type(Request $request)
    {
        $this->validate($request, [
            'vehicle_service_id' => 'required',
            'vehicle_model'      => 'required',
            'vehicle_no'         => 'required',
        ]);
        try {
            $provider_vehicle                     = new ProviderVehicle;
            $provider_vehicle->company_id         = Auth::user()->company_id;
            $provider_vehicle->provider_id        = $request->id;
            $provider_vehicle->vehicle_service_id = $request->vehicle_service_id;
            $provider_vehicle->vehicle_model      = $request->vehicle_model;
            $provider_vehicle->vehicle_no         = $request->vehicle_no;
            $provider_vehicle->save();
            return Helper::getResponse(['status' => 200, 'message' => trans('admin.create')]);
        } catch (\Throwable $e) {
            return Helper::getResponse(['status' => 404, 'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
        }
    }

    public function ProviderService($id)
    {
        $ProviderService = ProviderService::with('providervehicle', 'admin_service')->where('company_id', Auth::user()->company_id)->where('provider_id', $id)->get();
        return Helper::getResponse(['data' => $ProviderService]);
    }

    public function service_on($id)
    {
        $service_on = ProviderVehicle::where('company_id', Auth::user()->company_id)->where('vehicle_service_id', $id)->update(['status' => 1]);
        return Helper::getResponse(['status' => 200, 'message' => trans('admin.active_status')]);
    }

    public function service_off($id)
    {
        $service_on = ProviderVehicle::where('company_id', Auth::user()->company_id)->where('vehicle_service_id', $id)->update(['status' => 0]);
        return Helper::getResponse(['status' => 200, 'message' => trans('admin.deactive_status')]);
    }

    public function deleteservice($id)
    {
        $service_on = ProviderService::destroy($id);
        return Helper::getResponse(['status' => 200, 'message' => trans('admin.delete')]);
    }

    public function provider_services($admin_service)
    {
        $services = [];

        if ("TRANSPORT" == $admin_service) {
            try {
                $services = \App\Models\Transport\RideType::with('servicelist')->where('company_id', Auth::user()->company_id)->where('status', 1)->get();
                //$services = \App\Models\Transport\RideDeliveryVehicle::select('id', 'vehicle_name AS name')->where('company_id', Auth::user()->company_id)->where('status', 1)->get();
            } catch (\Throwable $e) {}
        } else if ("SERVICE" == $admin_service) {
            try {
                $services = \App\Models\Service\ServiceCategory::with('subcategories.service')->where('company_id', Auth::user()->company_id)->get();
                //$services = \App\Models\Transport\RideDeliveryVehicle::select('id', 'vehicle_name AS name')->where('company_id', Auth::user()->company_id)->where('status', 1)->get();
            } catch (\Throwable $e) {}
        }

        return Helper::getResponse(['data' => $services]);
    }

    //For document list
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Provider  $provider
     * @return \Illuminate\Http\Response
     */

    public function providerdocument(Request $request, $id)
    {
        $Provider = ProviderDocument::with('document')->where('company_id', Auth::user()->company_id)->where('provider_id', $id)->paginate(10);
        return Helper::getResponse(['data' => $Provider]);
    }

    //For view document list
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Provider  $provider for view document
     * @return \Illuminate\Http\Response
     */
    public function view_document(Request $request, $id)
    {
        $provider_document = ProviderDocument::with('document', 'provider')
            ->where('company_id', Auth::user()->company_id)
            ->where('id', $id)
            ->get();
        return Helper::getResponse(['data' => $provider_document]);
    }

    //Approve the provider image
    public function approve_image($id)
    {
        $service_on         = ProviderDocument::with('document')->where('id', $id)->first();
        $service_on->status = 'ACTIVE';
        $service_on->save();

        if (!empty($service_on->document) && "SERVICE" == $service_on->document->type) {
            if (!empty($service_on->document->service_category_id)) {
                ProviderService::where('provider_id', $service_on->provider_id)->where('admin_service', "SERVICE")->where('category_id', $service_on->document->service_category_id)->update(['status' => 'ACTIVE']);
            }
        }
        return Helper::getResponse(['status' => 200, 'message' => trans('admin.deactive_status')]);
    }

    //Approve the provider vehicle
    public function approve_vehicle($id)
    {
        $service_on = ProviderVehicle::where('id', $id)->update(['status' => 'ACTIVE']);
        return Helper::getResponse(['status' => 200, 'message' => trans('admin.deactive_status')]);
    }

    //Delete the provider image
    public function delete_view_image(Request $request, $id)
    {
        Provider::where('id', $request->provider_id)->update(['is_document' => 0]);
        $service_on = ProviderDocument::where('company_id', Auth::user()->company_id)->where('id', $id)->delete();
        return Helper::getResponse(['status' => 200, 'message' => trans('admin.delete')]);
    }

    //Delete the provider Document
    public function document_reject(Request $request)
    {
        Provider::where('id', $request->provider_id)->update(['is_document' => 0]);
        $service_on = ProviderDocument::where('company_id', Auth::user()->company_id)->where('id', $request->document_id)->delete();

        $Notifications               = new Notifications;
        $Notifications->notify_type  = "Provider";
        $Notifications->user_id      = $request->provider_id;
        $Notifications->title        = $request->document_type . " Document Reject";
        $Notifications->service      = $request->document_service;
        $Notifications->company_id   = Auth::guard()->user()->company_id;
        $Notifications->descriptions = $request->document_reason;
        $Notifications->expiry_date  = date('Y-m-d H:i:s', strtotime(Carbon::now()->addMonths(1)));
        $Notifications->save();

        return Helper::getResponse(['status' => 200, 'message' => trans('admin.delete')]);
    }

    //Delete the provider vehicle
    public function vehicle_reject(Request $request)
    {

        $provider_vehicle         = ProviderVehicle::with('vehicle_type')->where('id', $request->vechile_id)->first();
        $provider_vehicle->status = "INACTIVE";
        $provider_vehicle->save();
        ProviderService::where('provider_vehicle_id', $provider_vehicle->id)->where('provider_id', $request->provider_id)->where('admin_service', $provider_vehicle->admin_service)->update(['status' => 'INACTIVE']);

        $Notifications               = new Notifications;
        $Notifications->notify_type  = "Provider";
        $Notifications->user_id      = $request->provider_id;
        $Notifications->title        = $request->vechile_type . " Vehicle Reject";
        $Notifications->service      = $provider_vehicle->admin_service;
        $Notifications->company_id   = Auth::guard()->user()->company_id;
        $Notifications->descriptions = $request->reason;
        $Notifications->expiry_date  = date('Y-m-d H:i:s', strtotime(Carbon::now()->addMonths(1)));
        $Notifications->save();

        return Helper::getResponse(['status' => 200, 'message' => trans('admin.delete')]);
    }

    public function delete_address_pfofile(Request $request, $id)
    {
        $provider = Provider::findOrFail($id);
        if ('Profile' == $request->type) {
            $provider->is_profile    = 0;
            $provider->picture_draft = null;
        } elseif ('Address' == $request->type) {
            ProviderAddress::where('company_id', Auth::user()->company_id)->where('provider_id', $provider->id)->delete();
            $provider->is_address = 0;
        }
        $provider->save();
        return Helper::getResponse(['status' => 200, 'message' => trans('admin.delete')]);
    }

    public function approve_all(Request $request, $id)
    {

        try {
            $document = Document::where('type', $request->type)->select('id')->get();
            ProviderDocument::where('provider_id', $id)->whereIn('document_id', $document)->update(['status' => 'ACTIVE']);

            if ('ALL' != $request->type) {
                ProviderVehicle::where('provider_id', $id)->where('admin_service', $request->type)->update(['status' => 'ACTIVE']);
                ProviderService::where('provider_id', $id)->where('admin_service', $request->type)->update(['status' => 'ACTIVE']);
            }
            return Helper::getResponse(['status' => 200, 'message' => trans('admin.deactive_status')]);
        } catch (\Throwable $e) {
            return Helper::getResponse(['status' => 404, 'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
        }
    }

    public function addressProfileStatus(Request $request, $id)
    {
        try {
            $provider = Provider::findOrFail($id);
            if ('Profile' == $request->type) {
                $provider->is_profile = 1;
                if (null != $provider->picture_draft) {
                    $provider->picture = $provider->picture_draft;
                }

                $provider->picture_draft = null;
                $provider->save();
            } elseif ('Address' == $request->type) {
                ProviderAddress::where('provider_id', $provider->id)->update(['status' => 'ACTIVE']);
                $provider->is_address = 1;
                $provider->save();
            }

            return Helper::getResponse(['status' => 200, 'message' => trans('admin.deactive_status')]);
        } catch (\Throwable $e) {
            return Helper::getResponse(['status' => 404, 'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
        }
    }

    public function provider_total_deatils(Request $request, $id)
    {
        try {
            $provider = Provider::with('totaldocument', 'totaldocument.document', 'totaldocument.document.serviceCategory', 'providerservice', 'providerservice.vehicle', 'providerservice.admin_service', 'providerAddress')->where('id', $id)->get();
            if (count($provider[0]->providerservice) > 0) {
                foreach ($provider[0]->providerservice as $key => $value) {
                    if ("TRANSPORT" == $value->admin_service) {
                        try {
                            $ridetype = \App\Models\Transport\RideDeliveryVehicle::with('ride_type')->where('company_id', Auth::user()->company_id)->where('id', $value->vehicle->vehicle_service_id)->where('status', 1)->first();
                            if ($ridetype) {
                                $provider[0]->providerservice[$key]->vehicle->type         = $ridetype->ride_type->ride_name;
                                $provider[0]->providerservice[$key]->vehicle->vehicle_name = $ridetype->vehicle_name;
                            }
                        } catch (\Throwable $e) {
                            return Helper::getResponse(['status' => 404, 'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
                        }
                    } else if ("ORDER" == $value->admin_service) {
                        try {
                            $storetype                                         = \App\Models\Order\StoreType::select('name')->where('company_id', Auth::user()->company_id)->where('id', $value->category_id)->where('status', 1)->first();
                            $provider[0]->providerservice[$key]->vehicle->type = $storetype->name;
                        } catch (\Throwable $e) {
                            return Helper::getResponse(['status' => 404, 'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
                        }
                    }
                }
            }
            return Helper::getResponse(['status' => 200, 'data' => $provider]);
        } catch (\Throwable $e) {
            return Helper::getResponse(['status' => 404, 'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
        }
    }

    public function addamount(Request $request, $id)
    {
        try {
            $transaction['message']    = 'Admin Added Amount';
            $transaction['amount']     = $request->amount;
            $transaction['company_id'] = Auth::user()->company_id;
            $transaction['id']         = $id;
            (new Transactions)->AdminAddAmountCreditDebit($transaction, 0);
            (new SendPushNotification)->adminAddamount($id, 'provider', trans('admin.admin_add_amount'), $request->amount);
            return Helper::getResponse(['status' => 200, 'message' => trans('admin.add_amount')]);
        } catch (\Throwable $e) {
            return Helper::getResponse(['status' => 404, 'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
        }
    }

    public function searchprovider(Request $request, $id, $type)
    {

        if ('shop' == $type) {
            $data = \App\Models\Order\Store::where('zone_id', $id)->where('wallet_balance', '>', 1)
                ->where('store_name', 'like', "%" . $request->term . "%")
                ->select('store_name AS label', 'store_name AS value', 'store_name AS name', 'id', 'wallet_balance', 'zone_id');
        } else if ('fleet' == $type) {
            $data = \App\Models\Common\Admin::where('zone_id', $id)->where('wallet_balance', '>', 1)->where('type', 'FLEET')
                ->where('name', 'like', "%" . $request->term . "%")
                ->select('name AS label', 'name AS value', 'name', 'id', 'wallet_balance', 'zone_id');
        } else {
            $data = Provider::where('zone_id', $id)->where('wallet_balance', '>', 1)
                ->where('first_name', 'like', "%" . $request->term . "%")->Orwhere('last_name', 'like', "%" . $request->term . "%")
                ->select(\DB::raw("CONCAT(first_name,' ',last_name) AS label"), \DB::raw("CONCAT(first_name,' ',last_name) AS value"), \DB::raw("CONCAT(first_name,' ',last_name) AS name"), 'id', 'wallet_balance', 'zone_id');
        }

        $provider = $data->get()->toArray();
        return $provider;
    }
}
