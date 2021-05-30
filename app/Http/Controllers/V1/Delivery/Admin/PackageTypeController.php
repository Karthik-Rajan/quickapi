<?php

namespace App\Http\Controllers\V1\Delivery\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Traits\Actions;
use App\Helpers\Helper;
use App\Models\Delivery\PackageType;
use App\Models\Common\AdminService;
use App\Models\Common\Menu;
use Illuminate\Support\Facades\Storage;
use Auth;

class PackageTypeController extends Controller
{
    use Actions;

    private $model;
    private $request;

    public function __construct(PackageType $model)
    {
        $this->model = $model;
    }

    public function index(Request $request) 
    {
        $datum = PackageType::where('company_id', Auth::user()->company_id);
       
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
            'package_name' => 'required|max:255'
        ]);

        try {
                $PackageType = new PackageType;
                $PackageType->company_id = Auth::user()->company_id; 
                $PackageType->package_name = $request->package_name;            
                $PackageType->status = 1;
                $PackageType->save();

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
            $PackageType = PackageType::findOrFail($id);
            return Helper::getResponse(['data' => $PackageType]);
        } catch (\Throwable $e) {
            return Helper::getResponse(['status' => 404, 'error' => $e->getMessage()]);
        }
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'package_name' => 'required|max:255',            
            'status' => 'required',
        ]);

        try{
                $PackageType = PackageType::findOrFail($id);
                if($PackageType)
                {
                    $PackageType->package_name = $request->package_name;
                    $PackageType->status = $request->status;
                    $PackageType->save();
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

            $datum = PackageType::findOrFail($id);
            
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
}
