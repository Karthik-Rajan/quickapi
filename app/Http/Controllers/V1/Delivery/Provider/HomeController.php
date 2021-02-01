<?php

namespace App\Http\Controllers\V1\Delivery\Provider;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Delivery\DeliveryRequestDispute;
use App\Models\Delivery\DeliveryType;
use App\Models\Common\ProviderVehicle;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use App\Traits\Actions;
use App\Helpers\Helper;
use Carbon\Carbon;
use Auth;
use DB;

class HomeController extends Controller
{
	public function deliverytype(Request $request)
	{
	try{
		$ridetype=DeliveryType::with('providerservice','servicelist')->where('company_id',Auth::guard('provider')->user()->company_id)->where('status',1)->get();
		return Helper::getResponse(['data' => $ridetype ]);
    }catch (ModelNotFoundException $e) {
			return Helper::getResponse(['status' => 500, 'error' => $e->getMessage()]);
		}

	}
}
