<?php

namespace App\Http\Controllers\V1\Delivery\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Traits\Actions;
use App\Helpers\Helper;
use App\Models\Delivery\DeliveryType;
use App\Models\Common\AdminService;
use App\Models\Common\Menu;
use Illuminate\Support\Facades\Storage;
use Auth;

class DeliveryTypeController extends Controller
{
    use Actions;

    private $model;
    private $request;

    public function __construct(DeliveryType $model)
    {
        $this->model = $model;
    }

    public function index(Request $request) 
    {
    	$datum = DeliveryType::where('company_id', Auth::user()->company_id);
        
        if($request->has('search_text') && $request->search_text != null) {
            $datum->Search($request->search_text);
        }

        if($request->has('order_by')) {
            $datum->orderby($request->order_by, $request->order_direction);
        }
        
        if($request->has('page') && $request->page == 'all') {
            $data = $datum->get();
        } else {
            $data = $datum->paginate(10);
        }

        return Helper::getResponse(['data' => $data]);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'delivery_name' => 'required|max:255',
            'delivery_category_id' => 'required',       
            'status' => 'required',
        ]);

        try {
                $DeliveryType = new DeliveryType;
                $DeliveryType->company_id = Auth::user()->company_id; 
                $DeliveryType->delivery_name = $request->delivery_name;   
                $DeliveryType->delivery_category_id = $request->delivery_category_id;         
                $DeliveryType->status = $request->status;
                $DeliveryType->save();

                return Helper::getResponse(['status' => 200, 'message' => trans('admin.create')]);
           } 
           catch (\Throwable $e) 
           {
            return Helper::getResponse(['status' => 404, 'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
           }
     }


    public function show($id)
    {
        try {
            $DeliveryType = DeliveryType::findOrFail($id);
            return Helper::getResponse(['data' => $DeliveryType]);
        } catch (\Throwable $e) {
            return Helper::getResponse(['status' => 404, 'error' => $e->getMessage()]);
        }
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'delivery_name' => 'required|max:255',  
            'delivery_category_id' => 'required',       
            'status' => 'required',
        ]);

        try{
                $DeliveryType = DeliveryType::findOrFail($id);
                if($DeliveryType)
                {
                    $DeliveryType->delivery_name = $request->delivery_name;
                    $DeliveryType->delivery_category_id = $request->delivery_category_id;  
                    $DeliveryType->status = $request->status;
                    $DeliveryType->save();
                    return Helper::getResponse(['status' => 200, 'message' => trans('admin.update')]);
                }
                else
                {
                return Helper::getResponse(['status' => 404, 'message' => trans('admin.not_found')]); 
                }
            } 
            catch (\Throwable $e) {
                return Helper::getResponse(['status' => 404, 'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
            }
    }
 
    public function destroy($id)
    {
        return $this->removeModel($id);
    }



    public function updateStatus(Request $request, $id)
    {
        
        try {

            $datum = DeliveryType::findOrFail($id);
            
            if($request->has('status')){
                if($request->status == 1){
                    $datum->status = 0;
                }else{
                    $datum->status = 1;
                }
            }
            $datum->save();
            $menu=Menu::where('menu_type_id',$id)->where('admin_service','DELIVERY')->where('company_id',Auth::user()->company_id)->first();
            if(!empty($menu)){
                
                $menu->status=$datum->status;
                $menu->save();
            }
           
            return Helper::getResponse(['status' => 200, 'message' => trans('admin.activation_status')]);

        } 

        catch (\Throwable $e) {
            return Helper::getResponse(['status' => 404, 'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
        }
    }

    public function webproviderservice(Request $request,$id)
    {
     
     try{
        $ridetype=DeliveryType::with(array('provideradminservice'=>function($query) use ($id){
            $query->where('provider_id',$id)->with('providervehicle');
        }))->with('servicelist')->where('company_id',Auth::user()->company_id)->get();

        return Helper::getResponse(['data' => $ridetype ]);
    }catch (ModelNotFoundException $e) {
            return Helper::getResponse(['status' => 500, 'error' => $e->getMessage()]);
        }

    }

    
}
