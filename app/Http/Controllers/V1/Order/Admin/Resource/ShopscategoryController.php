<?php

namespace App\Http\Controllers\V1\Order\Admin\Resource;

use App\Models\Order\StoreCategory;
use Illuminate\Http\Request;
use App\Helpers\Helper;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use App\Traits\Actions;
use Exception;
use Setting;
use Auth;

class ShopscategoryController extends Controller
{
    use Actions;

    private $model;
    private $request;
    public $category_options = "";
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(StoreCategory $model)
    {
        $this->model = $model;

         if(!empty(Auth::user())){          
            $this->company_id = Auth::user()->company_id;
        }
        else{          
            $this->company_id = Auth::guard('shop')->user()->company_id;
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        $datum = StoreCategory::where('company_id', $this->company_id);

        if($request->has('search_text') && $request->search_text != null) {
            $datum->Search($request->search_text);
        }

        if($request->has('order_by')) {
            $datum->orderby($request->order_by, $request->order_direction);
        }

        
        if($request->has('limit')) {
           $data = $datum->paginate($request->limit);
        } else {
            $data = $datum->paginate(10);
        }
        
        return Helper::getResponse(['data' => $data]);
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
              'store_category_name' => 'required',
             'store_category_description'=>'required',
             'picture' => 'mimes:jpeg,jpg,bmp,png|max:5242880'
             
        ]);

       
        try{
            $storecategory = new StoreCategory;
            $storecategory->company_id = $this->company_id;  
            $storecategory->store_category_name = $request->store_category_name;  
            $storecategory->store_category_description = $request->store_category_description; 
              if($request->hasFile('picture')) {
               $storecategory->picture = Helper::upload_file($request->file('picture'), 'shops/category');
                }
            if(!empty($request->category))
                $storecategory->parent_id = $request->category;

            $storecategory->store_category_status = $request->store_category_status;      
            $storecategory->save();
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

            $storecategory = StoreCategory::findOrFail($id);
            
            $parent_categories = StoreCategory::where('store_category_status',1)->whereNull('parent_id')->get();
            $this->category_options = "";
            $this->category_options .= '<option value="">--Select Category--</option>';
            foreach ($parent_categories as $parent_category) {
                if($parent_category->id == $storecategory->parent_id)
                    $this->category_options .= '<option style="font-weight: bold;" selected value='.$parent_category->id.'>'.ucwords($parent_category->store_category_name). '</option>';
                else
                    $this->category_options .= '<option value='.$parent_category->id.'>'.ucwords($parent_category->store_category_name). '</option>';

                $this->getChildCategory($parent_category->id,$storecategory->parent_id);  
            }
            $storecategory->categories=$this->category_options;
            return Helper::getResponse(['data' => $storecategory]);
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
        $storecategory = StoreCategory::findOrFail($id);
       $this->validate($request, [
        'store_category_name' => 'required',
             
             'store_category_description'=>'required',
             
        ]);
    
        try {
            $storecategory = StoreCategory::findOrFail($id);
            $storecategory->store_category_name = $request->store_category_name; 
           
            $storecategory->store_category_description = $request->store_category_description; 
              if($request->hasFile('picture')) {
           $storecategory->picture = Helper::upload_file($request->file('picture'), 'shops/category');
            }
            $storecategory->store_category_status = $request->store_category_status;                
            $storecategory->update();
           return Helper::getResponse(['status' => 200, 'message' => trans('admin.update')]);
            } catch (\Throwable $e) {
                return Helper::getResponse(['status' => 404,'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
            }
    }

      public function categorylist()
  {

  try {

            $categorylist = StoreCategory::with('store.storetype')->where('store_category_status',1)->get();
            return Helper::getResponse(['data' => $categorylist]);
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

            $datum = StoreCategory::findOrFail($id);
            
            if($request->has('status')){
                if($request->status == 1){
                    $datum->store_category_status = 0;
                }else{
                    $datum->store_category_status = 1;
                }
            }
            $datum->save();
           
            return Helper::getResponse(['status' => 200, 'message' => trans('admin.activation_status')]);

        } 

        catch (\Throwable $e) {
            return Helper::getResponse(['status' => 404, 'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
        }
    }
    public function allCategoryList(){
        $parent_categories = StoreCategory::where('store_category_status',1)->whereNull('parent_id')->get();
        $this->category_options = "";
        $this->category_options .= '<option value="">--Select Category--</option>';
        foreach ($parent_categories as $parent_category) {
            $this->category_options .= '<option value='.$parent_category->id.'>'.ucwords($parent_category->store_category_name). '</option>';
            $this->getChildCategory($parent_category->id,0);
        }

        return Helper::getResponse(['data' => $this->category_options]);
       
    }

    // Start of Child category Multiple levels

    public function getChildCategory($category_id,$selected_category_id){
        $category = StoreCategory::find($category_id);

        foreach ($category->childCategories as $child_category) {
            if($child_category->id == $selected_category_id)
                $this->category_options .= '<option style="font-weight: bold;" selected value='.$child_category->id.'>'.$this->getParentCategoryCount($child_category->id).' '.ucwords($child_category->store_category_name). '</option>';
            else
                $this->category_options .= '<option value='.$child_category->id.'>'.$this->getParentCategoryCount($child_category->id).' '.ucwords($child_category->store_category_name). '</option>';


            $this->getChildCategory($child_category->id,$selected_category_id);
            
        }

    }
    public function getParentCategoryCount($category_id){
        $category = StoreCategory::find($category_id);
        if($category){
            $level = "";
            while ($category->parent_id != NULL) {
                $parent_category = $category->parentCategory;
                $level .= "&nbsp;-";  
                $category = $parent_category;
            }

            return $level;
        }
    }

    // End of Child category Multiple levels

}
