<?php

namespace App\Http\Controllers\V1\Common\Admin\Resource;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Traits\Actions;
use App\Models\Common\Unit;
use App\Helpers\Helper;
use Auth;

class UnitController extends Controller
{
    use Actions;
    private $model;
    private $request;
    
    public function __construct(Unit $model)
    {
        $this->model = $model;
    }

    public function index(Request $request)
    {
        
        $datum = Unit::where('company_id', Auth::user()->company_id);

        $all = Unit::where('company_id', Auth::user()->company_id)->get()->pluck('id')->toArray();

        $unit_ids = $datum->get()->pluck('id')->toArray();
       
        $unit_list = array_merge($all, $unit_ids);

        $units = Unit::whereIn('id' ,$unit_list);

        if($request->has('search_text') && $request->search_text != null) {
            $units->Search($request->search_text);
        }

        if($request->has('order_by')) {
            $units->orderby($request->order_by, $request->order_direction);
        }
       
        if($request->has('page') && $request->page == 'all') {
            $data = $units->get();
        } else {
            $perPage =10;
            $data = $units->paginate(10);
        }
        
        return Helper::getResponse(['data' => $data]);
    }

    public function paginate($items, $perPage = 10, $page = null, $options = [])
    {

        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $items = $items instanceof Collection ? $items : Collection::make($items);

        return new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, $options);

    }

    public function store(Request $request)
    {
        $this->validate($request, [
             'name' => 'required|max:255',
        ]);
        $datum = Unit::where('company_id', Auth::user()->company_id)->get();

        foreach($datum as $avail)
        {

            $available_name = strtoupper(str_replace(' ', '', $avail->name));
            if(($available_name === strtoupper(str_replace(' ', '', $request->name))) && ($avail->type === strtoupper($request->type)) )
            {
                return Helper::getResponse(['status' => 422, 'message' => ("$request->name Already Exists") ]);
            }
        }

        try{
            $unit = new Unit;
            $unit->name = $request->name;  
            $unit->company_id = Auth::user()->company_id;                    
            $unit->save();
            return Helper::getResponse(['status' => 200, 'message' => trans('admin.create')]);
        } 

        catch (\Throwable $e) {
            return Helper::getResponse(['status' => 404, 'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
        }
    }

    public function show($id)
    {
        try {
            $unit = Unit::findOrFail($id);
            return Helper::getResponse(['data' => $unit]);
        } catch (\Throwable $e) {
            return Helper::getResponse(['status' => 404,'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
        }
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'name' => 'required|max:255',
        ]);

    
        try {

            $datum = Unit::where('company_id', Auth::user()->company_id)->get();
    
            foreach($datum as $avail)
            {
    
                $available_name = strtoupper(str_replace(' ', '', $avail->name));
                if(($available_name === strtoupper(str_replace(' ', '', $request->name))) && ($avail->type === strtoupper($request->type)) && ($avail->id != $request->id) )
                {
                    return Helper::getResponse(['status' => 422, 'message' => ("$request->name Already Exists") ]);
                }
            }

            Unit::where('id',$id)->update([
                    'name' => $request->name,
                ]);
            
            $unit = Unit::where('id',$id)->first();
            $unit->name = $request->name;                   
            $unit->save();

            return Helper::getResponse(['status' => 200, 'message' => trans('admin.update')]);
            } catch (\Throwable $e) {
                return Helper::getResponse(['status' => 404,'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
            }
    }

    public function destroy($id)
    {
        return $this->removeModel($id);
    }

    public function updateStatus(Request $request, $id)
    {
        
        try {

            $datum = Unit::findOrFail($id);
            
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
