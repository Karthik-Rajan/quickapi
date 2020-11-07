<?php

namespace App\Http\Controllers\V1\Common\Admin\Resource;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Traits\Actions;
use App\Models\Common\Reason;
use App\Helpers\Helper;
use Auth;

class ReasonController extends Controller
{
    

    use Actions;

    private $model;
    private $request; 

    public function __construct(Reason $model) 
    {
        $this->model = $model;
    }

   

     public function index(Request $request)
    {
        $datum = Reason::whereHas('service' ,function($query){  
            $query->where('status',1);  
        })->where('company_id', Auth::user()->company_id);

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
            'type' => 'required',
            'reason' => 'required', 
            'status' => 'required',   
            'service' => 'required',          
        ]);

        try{

            $datum = Reason::whereHas('service' ,function($query){  
                $query->where('status',1);  
            })->where('company_id', Auth::user()->company_id)->get();

            foreach($datum as $avail)
            {
    
                $available_name = strtoupper(str_replace(' ', '', $avail->reason));
                if(($available_name === strtoupper(str_replace(' ', '', $request->reason))) && ($avail->type === strtoupper($request->type)) && ($avail->service === $request->service) )
                {
                    return Helper::getResponse(['status' => 422, 'message' => ("$request->reason Already Exists") ]);
                }
            }


            $request->request->add(['company_id' => \Auth::user()->company_id]);
            $reason = new reason;
            $reason->company_id = Auth::user()->company_id;  
            $reason->service = $request->service;
            $reason->type = $request->type;
            $reason->reason = $request->reason;
            $reason->status = $request->status;           
            $reason->save();
            //Reason::create($request->all());
            return Helper::getResponse(['status' => 200, 'message' => trans('admin.create')]);
        } 

        catch (\Throwable $e) {
            return Helper::getResponse(['status' => 404, 'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
        }
    }

    public function show($id)
    {
        try {
                $reason = Reason::findOrFail($id);
                    return Helper::getResponse(['data' => $reason]);
        } catch (\Throwable $e) {
            return Helper::getResponse(['status' => 404,'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
        }
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'type' => 'required',
            'reason' => 'required',
            'service' => 'required',         
        ]);

        try {

            $datum = Reason::whereHas('service' ,function($query){  
                $query->where('status',1);  
            })->where('company_id', Auth::user()->company_id)->get();

            foreach($datum as $avail)
            {
    
                $available_name = strtoupper(str_replace(' ', '', $avail->reason));
                if(($available_name === strtoupper(str_replace(' ', '', $request->reason))) && ($avail->type === strtoupper($request->type)) && ($avail->service === $request->service) && ($avail->id != $request->id) )
                {
                    return Helper::getResponse(['status' => 422, 'message' => ("$request->reason Already Exists") ]);
                }
            }

                    $reason = Reason::findOrFail($id);
                    $reason->service = $request->service;
                    $reason->type = $request->type;
                    $reason->reason = $request->reason;
                    $reason->status = $request->status;           
                    $reason->save();
                    return Helper::getResponse(['status' => 200, 'message' => trans('admin.update')]);
            } catch (\Throwable $e) {
                return Helper::getResponse(['status' => 404,'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
            }
    }

    public function destroy($id)
    {
        return $this->removeModel($id);
    }

}
