<?php

namespace App\Http\Controllers\V1\Delivery\User;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Common\Dispute;
use App\Models\Delivery\DeliveryRequest;
use App\Models\Delivery\DeliveryRequestDispute;
use App\Services\V1\Common\UserServices;
use App\Traits\Actions;
use Auth;
use Illuminate\Http\Request;

class HomeController extends Controller
{

    use Actions;

    public function trips(Request $request)
    {
        try {
            $jsonResponse         = [];
            $jsonResponse['type'] = 'delivery';

            $withCallback = ['user' => function ($query) {$query->select('id', 'first_name', 'last_name', 'rating', 'picture', 'currency_symbol');},
                'provider'              => function ($query) {$query->select('id', 'first_name', 'last_name', 'rating', 'picture', 'mobile');},
                'provider_vehicle'      => function ($query) {$query->select('id', 'provider_id', 'vehicle_make', 'vehicle_model', 'vehicle_no');},
                'deliveries.payment',
                'service'               => function ($query) {$query->select('id', 'vehicle_name', 'vehicle_image');},
                'rating'                => function ($query) {$query->select('id', 'request_id', 'user_rating', 'provider_rating', 'user_comment', 'provider_comment');}];

            $userrequest = DeliveryRequest::select('id', 'booking_id', 'assigned_at', 's_address', 'd_address', 'provider_id', 'user_id', 'timezone', 'delivery_vehicle_id', 'status', 'user_rated', 'provider_rated', 'payment_mode', 'provider_vehicle_id', 'created_at', 'schedule_at');

            $data = (new UserServices())->userHistory($request, $userrequest, $withCallback);

            $jsonResponse['total_records'] = count($data);
            $jsonResponse['delivery']      = $data;
            return Helper::getResponse(['data' => $jsonResponse]);
        } catch (Exception $e) {
            return response()->json(['error' => trans('api.something_went_wrong')]);
        }
    }

    public function gettripdetails(Request $request, $id)
    {
        try {

            $jsonResponse         = [];
            $jsonResponse['type'] = 'delivery';
            $userrequest          = DeliveryRequest::with(['provider', 'deliveries.payment', 'service_type', 'service', 'provider_vehicle', 'dispute' => function ($query) {
                $query->where('dispute_type', 'user');
            }]);
            $request->request->add(['admin_service' => 'TRANSPORT', 'id' => $id]);
            $data                     = (new UserServices())->userTripsDetails($request, $userrequest);
            $jsonResponse['delivery'] = $data;
            return Helper::getResponse(['data' => $jsonResponse]);
        } catch (Exception $e) {
            return response()->json(['error' => trans('api.something_went_wrong')]);
        }
    }

    //Save the dispute details
    public function delivery_request_dispute(Request $request)
    {
        $this->validate($request, [
            'dispute_name' => 'required',
            'dispute_type' => 'required',
            'provider_id'  => 'required',
            'user_id'      => 'required',
            'id'           => 'required',
        ]);
        $ride_request_dispute = DeliveryRequestDispute::where('company_id', Auth::guard('user')->user()->company_id)
            ->where('delivery_request_id', $request->id)
            ->where('dispute_type', 'user')
            ->first();
        $request->request->add(['admin_service' => 'DELIVERY']);

        if (null == $ride_request_dispute) {
            try {
                $disputeRequest = new DeliveryRequestDispute;
                $data           = (new UserServices())->userDisputeCreate($request, $disputeRequest);
                return Helper::getResponse(['status' => 200, 'message' => trans('admin.create')]);
            } catch (\Throwable $e) {
                return Helper::getResponse(['status' => 404, 'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
            }
        } else {
            return Helper::getResponse(['status' => 404, 'message' => trans('Already Dispute Created for the Ride Request')]);
        }
    }

    public function get_delivery_request_dispute(Request $request, $id)
    {
        $ride_request_dispute = DeliveryRequestDispute::with('request')->where('company_id', Auth::guard('user')->user()->company_id)
            ->where('delivery_request_id', $id)
            ->where('dispute_type', 'user')
            ->first();
        if ($ride_request_dispute) {
            $ride_request_dispute->created_time = (\Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $ride_request_dispute->created_at, 'Asia/Kolkata'))->setTimezone($ride_request_dispute->request->timezone)->format('d-m-Y g:i A');
        }
        return Helper::getResponse(['data' => $ride_request_dispute]);
    }

    public function getdispute(Request $request)
    {
        $dispute = Dispute::select('id', 'dispute_name', 'service')->where('service', 'DELIVERY')->where('dispute_type', 'provider')->where('status', 'active')->get();
        return Helper::getResponse(['data' => $dispute]);
    }

    public function getUserdisputedetails(Request $request, $id)
    {
        $dispute = Dispute::select('id', 'dispute_name', 'service')->where('service', 'DELIVERY')->where('dispute_type', 'user')->where('status', 'active')->where('id', $id)->get();
        return Helper::getResponse(['data' => $dispute]);
    }

    public function get_ride_request_dispute(Request $request, $id)
    {
        $ride_request_dispute = DeliveryRequestDispute::with('request')->where('company_id', Auth::guard('user')->user()->company_id)
            ->where('delivery_request_id', $id)
            ->where('dispute_type', 'user')
            ->first();
        if ($ride_request_dispute) {
            $ride_request_dispute->created_time = (\Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $ride_request_dispute->created_at, 'Asia/Kolkata'))->setTimezone($ride_request_dispute->request->timezone)->format('d-m-Y g:i A');
        }
        return Helper::getResponse(['data' => $ride_request_dispute]);
    }
}
