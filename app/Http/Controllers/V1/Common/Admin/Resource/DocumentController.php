<?php

namespace App\Http\Controllers\V1\Common\Admin\Resource;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Traits\Actions;
use App\Models\Common\Document;
use App\Models\Service\ServiceSubcategory;
use App\Helpers\Helper;
use Auth;

class DocumentController extends Controller
{
    use Actions;
    private $model;
    private $request;
    
    public function __construct(Document $model)
    {
        $this->model = $model;
    }

    public function index(Request $request)
    {
        
        $datum = Document::whereHas('service' ,function($query){  
            $query->where('status',1);  
        })->where('company_id', Auth::user()->company_id);

        $all = Document::where('service' ,'ALL')->where('company_id', Auth::user()->company_id)->get()->pluck('id')->toArray();

        $document_ids = $datum->get()->pluck('id')->toArray();
       
        $document_list = array_merge($all, $document_ids);

        $documents = Document::whereIn('id' ,$document_list);

        if($request->has('search_text') && $request->search_text != null) {
            $documents->Search($request->search_text);
        }

        if($request->has('order_by')) {
            $documents->orderby($request->order_by, $request->order_direction);
        }
       
        if($request->has('page') && $request->page == 'all') {
            $data = $documents->get();
        } else {
            $perPage =10;
            $data = $documents->paginate(10);
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
             'type' => 'required|in:TRANSPORT,ORDER,SERVICE,DELIVERY,ALL',
        ]);
        $datum = Document::whereHas('service' ,function($query){  
            $query->where('status',1);  
        })->where('company_id', Auth::user()->company_id)->get();

        foreach($datum as $avail)
        {

            $available_name = strtoupper(str_replace(' ', '', $avail->name));
            if(($available_name === strtoupper(str_replace(' ', '', $request->name))) && ($avail->type === strtoupper($request->type)) )
            {
                return Helper::getResponse(['status' => 422, 'message' => ("$request->name Already Exists") ]);
            }
        }

        try{
            $document = new Document;
            $document->name = $request->name;  
            $document->company_id = Auth::user()->company_id;                    
            $document->type = $request->type;
            $document->service = $request->type;   
            $document->file_type = $request->file_type;
            $document->is_backside = $request->is_backside;
            if($request->has('service_category_id')){
                $document->service_category_id = $request->service_category_id;
            }
            if($request->has('service_subcategory_id')){
                $document->service_subcategory_id = $request->service_subcategory_id;
            }
            $document->save();
            return Helper::getResponse(['status' => 200, 'message' => trans('admin.create')]);
        } 

        catch (\Throwable $e) {
            return Helper::getResponse(['status' => 404, 'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
        }
    }

    public function show($id)
    {
        try {
            $document = Document::with('subcategories')->findOrFail($id);
            if($document->service_category_id)
            {
              $document['service_subcategory_data']=ServiceSubcategory::where("service_category_id",$document->service_category_id)->get();
            }
            
            return Helper::getResponse(['data' => $document]);
        } catch (\Throwable $e) {
            return Helper::getResponse(['status' => 404,'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
        }
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'name' => 'required|max:255',
            'type' => 'required|in:TRANSPORT,ORDER,SERVICE,DELIVERY,ALL',
        ]);

        try {

            $datum = Document::whereHas('service' ,function($query){  
                $query->where('status',1);  
            })->where('company_id', Auth::user()->company_id)->get();
    
            foreach($datum as $avail)
            {
    
                $available_name = strtoupper(str_replace(' ', '', $avail->name));
                if(($available_name === strtoupper(str_replace(' ', '', $request->name))) && ($avail->type === strtoupper($request->type)) && ($avail->id != $request->id) )
                {
                    return Helper::getResponse(['status' => 422, 'message' => ("$request->name Already Exists") ]);
                }
            }

            Document::where('id',$id)->update([
                    'name' => $request->name,
                    'type' => $request->type,
                ]);
            
            $document = Document::where('id',$id)->first();
            $document->name = $request->name;                   
            $document->type = $request->type;  
            $document->service = $request->type;  
            $document->file_type = $request->file_type;
            if($request->has('is_backside')){
                $document->is_backside = $request->is_backside;
            }else{
                $document->is_backside = null;
            }
            if($request->has('service_category_id')){
                $document->service_category_id = $request->service_category_id;
            }
            else{
                $document->service_category_id = null;
            }
            if($request->has('service_subcategory_id')){
                $document->service_subcategory_id = $request->service_subcategory_id;
            }
            else{
                $document->service_subcategory_id = null;
            }
            
            $document->save();

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

            $datum = Document::findOrFail($id);
            
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
