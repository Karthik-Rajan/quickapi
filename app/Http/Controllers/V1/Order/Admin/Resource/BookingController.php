<?php

namespace App\Http\Controllers\V1\Order\Admin\Resource;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Common\Setting;
use App\Models\Common\UserRequest;
use App\Models\Order\StoreOrder;
use App\Models\Service\ServiceRequest;
use App\Models\Service\ServiceRequestPayment;
use Auth;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct() {}

    public function index(Request $request, $id = 0)
    {
        $serviceRequest = ServiceRequest::with(['service', 'provider', 'user'])->whereNotNull('service_id');
        if ($request->input('status')) {
            $serviceRequest = $serviceRequest->whereIn('status', explode(',', $request->input('status')));
        }
        if ($id) {
            $data = $serviceRequest->where('id', $id)->first();
        } else {
            $data = $serviceRequest->get();
        }

        return Helper::getResponse(['data' => $data]);
    }

    public function updateProvider(Request $request, $id)
    {
        try {

            if ($request->has('lab_order')) {
                ServiceRequest::where('id', $id)->update(['status' => $request->input('status')]);

                $serviceReq = ServiceRequest::find($id);

                $data                       = [];
                $data['service_request_id'] = $serviceReq->id;
                $data['user_id']            = $serviceReq->user_id;
                $data['provider_id']        = $serviceReq->provider_id;
                $data['company_id']         = $serviceReq->company_id;
                $data['total']              = $serviceReq->price;
                $data['payable']            = $serviceReq->price;
                $data['cash']               = 'CASH' == $serviceReq->payment_mode ? $serviceReq->price : 0;
                $data['payment_mode']       = $serviceReq->payment_mode;
                ServiceRequestPayment::create($data);
            } else {

                $setting = Setting::where('company_id', Auth::user()->company_id)->first();

                $settings = json_decode(json_encode($setting->settings_data));

                $serviceConfig = $settings->service;

                $prefix = $serviceConfig->booking_prefix;

                $storeOrder = StoreOrder::with('invoice')->where('id', $id)->first();
                $storeOrder->update(['provider_id' => $request->input('provider_id'), 'status' => 'PROCESSING']);

                $amount = isset($storeOrder->invoice->total_amount) ? $storeOrder->invoice->total_amount : 0;

                $serviceReq = ServiceRequest::where('user_id', $storeOrder->user_id)->where('provider_id', $request->input('provider_id'))->where('company_id', $storeOrder->company_id)->count();
                if (!$serviceReq) {
                    $data                  = [];
                    $data['booking_id']    = Helper::generate_booking_id($prefix);
                    $data['admin_service'] = 'SERVICE';
                    $data['user_id']       = $storeOrder->user_id;
                    $data['provider_id']   = $request->input('provider_id');
                    $data['company_id']    = $storeOrder->company_id;
                    $data['country_id']    = $storeOrder->country_id;
                    $data['city_id']       = $storeOrder->city_id;
                    $data['status']        = 'SCHEDULED';
                    $data['is_scheduled']  = 'YES';
                    $data['price']         = $amount;
                    $data['assigned_at']   = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
                    $service               = ServiceRequest::create($data);

                    $service = ServiceRequest::find($service->id);

                    $data                  = [];
                    $data['user_id']       = $storeOrder->user_id;
                    $data['provider_id']   = $request->input('provider_id');
                    $data['request_id']    = $service->id;
                    $data['admin_service'] = 'SERVICE';
                    $data['company_id']    = $storeOrder->company_id;
                    $data['status']        = 'SCHEDULED';
                    $data['scheduled_at']  = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
                    $data['request_data']  = json_encode($service->toArray());
                    UserRequest::create($data);

                    $data                       = [];
                    $data['service_request_id'] = $service->id;
                    $data['user_id']            = $storeOrder->user_id;
                    $data['provider_id']        = $request->input('provider_id');
                    $data['company_id']         = $storeOrder->company_id;
                    $data['total']              = $amount;
                    $data['payable']            = isset($storeOrder->invoice->payable) ? $storeOrder->invoice->payable : 0;
                    $data['cash']               = isset($storeOrder->invoice->cash) ? $storeOrder->invoice->cash : 0;
                    $data['tax']                = isset($storeOrder->invoice->tax_amount) ? $storeOrder->invoice->tax_amount : 0;
                    $data['wallet']             = isset($storeOrder->invoice->wallet_amount) ? $storeOrder->invoice->wallet_amount : 0;
                    $data['tax_percent']        = isset($storeOrder->invoice->tax_per) ? $storeOrder->invoice->tax_per : 0;
                    $data['discount']           = isset($storeOrder->invoice->discount) ? $storeOrder->invoice->discount : 0;
                    $data['payment_mode']       = isset($storeOrder->invoice->payment_mode) ? $storeOrder->invoice->payment_mode : '';
                    ServiceRequestPayment::create($data);
                }
            }

            return Helper::getResponse(['data' => []]);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }
}
