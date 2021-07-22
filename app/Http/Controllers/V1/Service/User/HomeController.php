<?php

namespace App\Http\Controllers\V1\Service\User;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Common\Dispute;
use App\Models\Service\Service;
use App\Models\Service\ServiceCategory;
use App\Models\Service\ServiceCityPrice;
use App\Models\Service\ServiceRequest;
use App\Models\Service\ServiceRequestDispute;
use App\Models\Service\ServiceSubcategory;
use App\Services\V1\Common\UserServices;
use Auth;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    //Service Type
    public function service_category(Request $request)
    {
        $user = Auth::guard('user')->user();

        $company_id = $user ? $user->company_id : 1;

        $service_list = ServiceCategory::where('company_id', $company_id)
            ->get();
        return Helper::getResponse(['data' => $service_list]);
    }

    //Service Sub Category
    public function service_sub_category(Request $request, $id)
    {
        $user = Auth::guard('user')->user();

        $company_id                = $user ? $user->company_id : 1;
        $city_id                   = $user ? $user->city_id : $request->city_id;
        $service_sub_category_list = ServiceSubcategory::where('company_id', $company_id)->where('service_subcategory_status', 1)->whereHas('service.service_city', function ($query) use ($company_id, $city_id) {
            $query->where('city_id', $city_id);
        })->where('service_category_id', $id)->get();
        return Helper::getResponse(['data' => $service_sub_category_list]);
    }

    //Service Sub Category
    public function service(Request $request, $category_id, $subcategory_id)
    {

        $user = Auth::guard('user')->user();

        $company_id = $user ? $user->company_id : 1;

        $city_id = $user ? $user->city_id : $request->city_id;

        $service = Service::with(['service_city' => function ($query) use ($company_id, $city_id) {
            $query->where('city_id', $city_id);
        }])->whereHas('service_city', function ($query) use ($company_id, $city_id) {
            $query->where('city_id', $city_id);
        })->where('company_id', $company_id)
            ->where('service_subcategory_id', $subcategory_id)
            ->where('service_category_id', $category_id)
            ->where('service_status', 1)
            ->get();

        return Helper::getResponse(['data' => $service]);
    }

//Service Sub Category
    public function service_city_price(Request $request, $id)
    {

        $user = Auth::guard('user')->user();

        $company_id = $user ? $user->company_id : 1;

        $city_id = $user ? $user->city_id : $request->city_id;

        $service_city_price = ServiceCityPrice::with('service')->where('company_id', $company_id)
            ->where('fare_type', 'FIXED')
            ->where('city_id', $city_id)->where('service_id', $id)
            ->get();
        return Helper::getResponse(['data' => $service_city_price]);
    }

    public function trips(Request $request)
    {
        try {

            $jsonResponse         = [];
            $jsonResponse['type'] = 'service';
            $withCallback         = ['payment',
                'service'  => function ($query) {$query->select('id', 'service_name');},
                'user'     => function ($query) {$query->select('id', 'first_name', 'last_name', 'rating', 'picture', 'currency_symbol');},
                'provider' => function ($query) {$query->select('id', 'first_name', 'last_name', 'rating', 'picture', 'mobile');},
                'rating'   => function ($query) {$query->select('request_id', 'user_rating', 'provider_rating', 'user_comment', 'provider_comment');},
            ];
            $userrequest                   = ServiceRequest::select('id', 'booking_id', 'user_id', 'provider_id', 'service_id', 'status', 's_address', 'schedule_at', 'assigned_at', 'created_at', 'timezone', 'user_rated', 'provider_rated', 'schedule_at');
            $data                          = (new UserServices())->userHistory($request, $userrequest, $withCallback);
            $jsonResponse['total_records'] = count($data);
            $jsonResponse['service']       = $data;
            return Helper::getResponse(['data' => $jsonResponse]);
        } catch (Exception $e) {
            return response()->json(['error' => trans('api.something_went_wrong')]);
        }
    }

    public function gettripdetails(Request $request, $id)
    {
        try {

            $jsonResponse         = [];
            $jsonResponse['type'] = 'service';
            $request->request->add(['admin_service' => 'SERVICE', 'id' => $id]);
            $userrequest = ServiceRequest::with(['provider', 'payment', 'service.servicesubCategory', 'user', 'service', 'dispute' => function ($query) {
                $query->where('dispute_type', 'user');
            }]);

            $data                    = (new UserServices())->userTripsDetails($request, $userrequest);
            $jsonResponse['service'] = $data;
            $jsonResponse['status']  = \DB::connection('service')->table('service_status_tracking')->where('request_id', $id)->get()->toArray();
            return Helper::getResponse(['data' => $jsonResponse]);
        } catch (Exception $e) {
            return response()->json(['error' => trans('api.something_went_wrong')]);
        }
    }

    //Save the dispute details
    public function service_request_dispute(Request $request)
    {
        $this->validate($request, [
            'dispute_name' => 'required',
            'dispute_type' => 'required',
            'provider_id'  => 'required',
            'user_id'      => 'required',
            'id'           => 'required',
        ]);
        $service_request_dispute = ServiceRequestDispute::where('company_id', Auth::guard('user')->user()->company_id)
            ->where('service_request_id', $request->id)
            ->where('dispute_type', 'user')
            ->first();
        $request->request->add(['admin_service' => 'SERVICE']);

        if (null == $service_request_dispute) {
            try {
                $disputeRequest = new ServiceRequestDispute;
                $data           = (new UserServices())->userDisputeCreate($request, $disputeRequest);
                return Helper::getResponse(['status' => 200, 'message' => trans('admin.create')]);
            } catch (\Throwable $e) {
                return Helper::getResponse(['status' => 404, 'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
            }
        } else {
            return Helper::getResponse(['status' => 404, 'message' => trans('Already Dispute Created for the Ride Request')]);
        }
    }

    public function get_service_request_dispute(Request $request, $id)
    {
        // dd("pavan");
        $service_request_dispute = ServiceRequestDispute::with('request')->where('company_id', Auth::guard('user')->user()->company_id)
            ->where('service_request_id', $id)
            ->where('dispute_type', 'user')
            ->first();
        if ($service_request_dispute) {
            $service_request_dispute->created_time = (\Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $service_request_dispute->created_at, 'UTC'))->setTimezone($service_request_dispute->request->timezone)->format(Helper::dateFormat());
        }
        return Helper::getResponse(['data' => $service_request_dispute]);
    }

    public function getdisputedetails(Request $request)
    {
        $dispute = Dispute::select('id', 'dispute_name', 'service')->where('service', 'SERVICE')->where('dispute_type', 'user')->where('status', 'active')->get();
        return Helper::getResponse(['data' => $dispute]);
    }

    public function getUserdisputedetails(Request $request)
    {
        $dispute = Dispute::select('id', 'dispute_name', 'service')->where('service', 'SERVICE')->where('dispute_type', 'user')->where('status', 'active')->get();
        return Helper::getResponse(['data' => $dispute]);
    }

    public function cmsPages(Request $request)
    {

        $pageName = $request->input('page_name');

        if (!$pageName) {
            $data = CmsPage::get();
        } else {
            $data = CmsPage::where('page_name', $pageName)->first();
        }

        return Helper::getResponse(['data' => $data]);
    }
}
