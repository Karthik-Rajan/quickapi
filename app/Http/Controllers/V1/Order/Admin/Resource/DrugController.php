<?php

namespace App\Http\Controllers\V1\Order\Admin\Resource;

use App\Models\Order\StoreDrug;
use Illuminate\Http\Request;
use App\Helpers\Helper;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use App\Traits\Actions;
use Exception;
use Setting;
use Auth;

class DrugController extends Controller
{
    use Actions;

    private $model;
    private $request;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(StoreDrug $model)
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
        $datum = StoreDrug::where('company_id', Auth::user()->company_id);

        if($request->has('search_text') && $request->search_text != null) {
            $datum->Search($request->search_text);
        }

        if($request->has('order_by')) {
            $datum->orderby($request->order_by, $request->order_direction);
        }

        
        if($request->has('page') && $request->page == 'all') {
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
        
         $this->validate($request, [
             'name' => 'required',
             'picture'=> 'required'
        ]);
       
        try{

            $datum = StoreDrug::where('company_id', Auth::user()->company_id)->get();
            foreach($datum as $avail)
            {
    
                $available_name = strtoupper(str_replace(' ', '', $avail->name));
                if(($available_name === strtoupper(str_replace(' ', '', $request->name))) )
                {
                    return Helper::getResponse(['status' => 422, 'message' => ("$request->name Already Exists") ]);
                }
            }

            $drug = new StoreDrug;
            $drug->company_id = Auth::user()->company_id;  
            $drug->name = $request->name;
            $drug->status = $request->status; 
            if($request->hasFile('picture')) {
                $drug['picture'] = Helper::upload_file($request->file('picture'), 'provider/profile');
            }     
            $drug->save();
            return Helper::getResponse(['status' => 200, 'message' => trans('admin.create')]);
        } 

        catch (\Throwable $e) {
            return Helper::getResponse(['status' => 404, 'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
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

            $drug = StoreDrug::findOrFail($id);
            

            return Helper::getResponse(['data' => $drug]);
        } catch (\Throwable $e) {
            return Helper::getResponse(['status' => 404,'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
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
       $this->validate($request, [
             'name' => 'required',
            
        ]);

        try {

            $datum = StoreDrug::where('company_id', Auth::user()->company_id)->get();
            foreach($datum as $avail)
            {
    
                $available_name = strtoupper(str_replace(' ', '', $avail->name));
                if(($available_name === strtoupper(str_replace(' ', '', $request->name)))  && ($avail->id != $request->id) )
                {
                    return Helper::getResponse(['status' => 422, 'message' => ("$request->name Already Exists") ]);
                }
            }
            $drug = StoreDrug::findOrFail($id);
            $drug->name = $request->name;
            $drug->status = $request->status; 
            if($request->hasFile('picture')) {
                $drug['picture'] = Helper::upload_file($request->file('picture'), 'provider/profile');
            }                      
            $drug->update();
           return Helper::getResponse(['status' => 200, 'message' => trans('admin.update')]);
            } catch (\Throwable $e) {
                return Helper::getResponse(['status' => 404,'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
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
        return $this->removeModel($id);
       
    }

    public function updateStatus(Request $request, $id)
    {
        
        try {

            $datum = StoreDrug::findOrFail($id);
            
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


    public function drugList(Request $request) {
        $StoreDrug = StoreDrug::where('company_id',Auth::user()->company_id)->get();

        return Helper::getResponse(['data' => $StoreDrug]);
    }

}
