<?php

namespace App\Http\Controllers\V1\Order\Admin\Resource;

use App\Models\Order\Attribute;
use App\Models\Order\AttributeValue;
use Illuminate\Http\Request;
use App\Helpers\Helper;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\Traits\Actions;
use Exception;
use Setting;
use Auth;



class AttributeController extends Controller
{

   use Actions;
    private $model;
    private $request;
     public function __construct(Attribute $model)
    {
        $this->model = $model;
    }
    public function index(Request $request){
    	 $datum = Attribute::where('company_id', Auth::user()->company_id);

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


    public function store(Request $request){

        $this->validate($request, [
            'name' => 'required',
            'values' => 'required',
        ]);	


 try{

        $datum = Attribute::where('company_id', Auth::user()->company_id)->get();
        foreach($datum as $avail)
        {

            $available_name = strtoupper(str_replace(' ', '', $avail->name));
            if(($available_name === strtoupper(str_replace(' ', '', $request->name))) )
            {
                return Helper::getResponse(['status' => 422, 'message' => ("$request->name Already Exists") ]);
            }
        }
        $attribute = new Attribute;
        $attribute->name = $request->name;
        $attribute->company_id = Auth::user()->company_id;  
        $attribute->save();

        if($request->values)
        {
            foreach ($request->values as $value) {
                if(!empty($value)){
                    $attribute_value = new AttributeValue;
                    $attribute_value->attribute_id = $attribute->id;
                    $attribute_value->value = strtoupper($value['attribute']);
                    $attribute_value->save();
                }
            }
        }
        
        
        return Helper::getResponse(['status' => 200, 'message' => trans('admin.create')]);
    }
    catch (\Throwable $e) {
        return Helper::getResponse(['status' => 404, 'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
    }
        

    }
    public function show($id)
    {
        
        try {

            $attribute = Attribute::with('attributeValues')->findOrFail($id);
            

            return Helper::getResponse(['data' => $attribute]);
        } catch (\Throwable $e) {
            return Helper::getResponse(['status' => 404,'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
        }
    }


    public function update(Request $request,$attribute_id){
        $this->validate($request, [
            'name' => 'required',
            'values' => 'required',
        ]);	
        \log::info($request->all());
try {
        $attribute = Attribute::find($attribute_id);
        $attribute->name = ucwords($request->name);
        $attribute->save();

        $values = $request->values;
        if($values){
            foreach ($values as $key => $value) {

                if(!empty($value['id'])){
                    $attribute_value = AttributeValue::findOrFail($value['id']);
                }else{
                    $attribute_value = new AttributeValue;
                }  
                    $attribute_value->attribute_id = $attribute->id;
                    $attribute_value->value = strtoupper($value['attribute']);
                    $attribute_value->save();
                
            }
        }

    
        return Helper::getResponse(['status' => 200, 'message' => trans('admin.update')]);
            } catch (\Throwable $e) {
                return Helper::getResponse(['status' => 404,'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
            }

    }
    public function destroy($id)
    {
        
        $attribute_value = AttributeValue::find($id);

        $attribute_value->delete();
        return Helper::getResponse(['status' => 200, 'message' => trans('admin.delete')]);

       
    }
    public function updateStatus(Request $request, $id)
    {
        
        try {

            $datum = Attribute::findOrFail($id);
            
            if($request->has('status')){
                if($request->status == 1){
                    $datum->status = 0;
                }else{
                    $datum->status = 1;
                }
            }
            $datum->save();
           
            return Helper::getResponse(['status' => 200, 'message' => trans('admin.activation_status')]);

        } 

        catch (\Throwable $e) {
            return Helper::getResponse(['status' => 404, 'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
        }
    }

}
