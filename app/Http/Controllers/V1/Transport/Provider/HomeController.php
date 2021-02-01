<?php

namespace App\Http\Controllers\V1\Transport\Provider;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Transport\RideType;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use App\Traits\Actions;
use App\Helpers\Helper;
use Carbon\Carbon;
use Auth;
use DB;

class HomeController extends Controller
{

    public function ridetype(Request $request)
	{
	try{
		$ridetype=RideType::with('providerservice','servicelist')->where('company_id',Auth::guard('provider')->user()->company_id)->where('status',1)->get();
		return Helper::getResponse(['data' => $ridetype ]);
    }catch (ModelNotFoundException $e) {
			return Helper::getResponse(['status' => 500, 'error' => $e->getMessage()]);
		}

	}
	

	
    
}
