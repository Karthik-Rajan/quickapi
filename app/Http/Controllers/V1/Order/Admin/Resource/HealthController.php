<?php

namespace App\Http\Controllers\V1\Order\Admin\Resource;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Traits\Actions;
use App\Helpers\Helper;
use App\Models\Service\ServiceCategory;
use App\Models\Service\ServiceSubcategory;
use App\Models\Service\Service;
use App\Models\Service\ServiceRequest;
use App\Models\Service\ServiceCityPrice;
use App\Models\Common\AdminService;
use App\Models\Common\CompanyCountry;
use App\Models\Common\Menu;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Auth;
use Validator;
use App\Models\Common\HealthArticle;
use App\Models\Common\HealthArticleCategory;
use App\Models\Common\HealthArticleSubcategory;

class HealthController extends Controller
{
    use Actions;
    private $model;
    private $request;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct(HealthArticle $model)
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
        $datum = HealthArticle::with('articleCategory')->with('articleSubCategory')->where('company_id', Auth::user()->company_id);
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

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'article_name' => 'required|max:255|regex:/^[a-zA-Z0-9\s]+$/',            
            'article_category_id'=>'required',
            'article_subcategory_id' => 'required',
            //'picture' => 'required|mimes:jpeg,jpg,bmp,png|max:5242880',
            'article_status' => 'required',
        ]);

        try {

            $datum = HealthArticle::with('articleCategory')->with('articleSubCategory')->where('company_id', Auth::user()->company_id)->get();
            foreach($datum as $avail)
            {
    
                $available_name = strtoupper(str_replace(' ', '', $avail->article_name));
                if(($available_name === strtoupper(str_replace(' ', '', $request->article_name))) && ($avail->article_category_id == $request->service_category_id) && ($avail->article_subcategory_id == $request->service_subcategory_id) )
                {
                    return Helper::getResponse(['status' => 422, 'message' => ("$request->article_name Already Exists") ]);
                }
            }

            $SubCategory = new HealthArticle;
            $SubCategory->company_id = Auth::user()->company_id; 
            $SubCategory->article_name = $request->article_name; 
            $SubCategory->article_category_id = $request->article_category_id;            
            $SubCategory->article_subcategory_id = $request->article_subcategory_id;
            $SubCategory->article_status = $request->article_status;

            // if(!empty($request->is_professional))
            //     $SubCategory->is_professional = $request->is_professional;
            // else
            //     $SubCategory->is_professional=0;

            // if(!empty($request->allow_desc))
            //     $SubCategory->allow_desc = $request->allow_desc;
            // else
            //     $SubCategory->allow_desc=0;
                
            // if(!empty($request->allow_before_image))
            //     $SubCategory->allow_before_image = $request->allow_before_image;
            // else
            //     $SubCategory->allow_before_image=0;

            // if(!empty($request->allow_after_image))
            //     $SubCategory->allow_after_image = $request->allow_after_image;
            // else
            //     $SubCategory->allow_after_image=0;
            
            if($request->hasFile('picture')) {
                $SubCategory->picture = Helper::upload_file($request->file('picture'), 'health/article', 'article-'.time().'.png');
            }
            $SubCategory->save();
            return Helper::getResponse(['status' => 200, 'message' => trans('admin.create')]);
        }catch (\Throwable $e){
            return Helper::getResponse(['status' => 404, 'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $ServiceView = HealthArticle::with('articleSubCategory')->findOrFail($id);

            $ServiceView['article_subcategory_data']=HealthArticleSubcategory::where("article_category_id",$ServiceView->article_category_id)->get();

            return Helper::getResponse(['data' => $ServiceView]);
        } catch (\Throwable $e) {
            return Helper::getResponse(['status' => 404, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'article_name' => 'required|max:255|regex:/^[a-zA-Z0-9\s]+$/',            
            'article_category_id'=>'required',
            'article_subcategory_id' => 'required',
            //'picture' => 'mimes:jpeg,jpg,bmp,png|max:5242880',
            'article_status' => 'required',
        ]);
        try{

            $datum = HealthArticle::with('articleCategory')->with('articleSubCategory')->where('company_id', Auth::user()->company_id)->get();
            foreach($datum as $avail)
            {
    
                $available_name = strtoupper(str_replace(' ', '', $avail->article_name));
                if(($available_name === strtoupper(str_replace(' ', '', $request->article_name))) && ($avail->article_category_id == $request->article_category_id) && ($avail->article_subcategory_id == $request->article_subcategory_id) && ($avail->id != $request->id) )
                {
                    return Helper::getResponse(['status' => 422, 'message' => ("$request->service_name Already Exists") ]);
                }
            }

            $ServiceQuery = HealthArticle::findOrFail($id);
            if($ServiceQuery){
                $ServiceQuery->article_name = $request->article_name; 
                $ServiceQuery->article_category_id = $request->article_category_id;            
                $ServiceQuery->article_subcategory_id = $request->article_subcategory_id;
                $ServiceQuery->article_status = $request->article_status;
                // if(!empty($request->is_professional))
                //     $ServiceQuery->is_professional = $request->is_professional;
                // else
                //     $ServiceQuery->is_professional=0;

                // if(!empty($request->allow_desc))
                //     $ServiceQuery->allow_desc = $request->allow_desc;
                // else
                //     $ServiceQuery->allow_desc=0;
                    
                // if(!empty($request->allow_before_image))
                //     $ServiceQuery->allow_before_image = $request->allow_before_image;
                // else
                //     $ServiceQuery->allow_before_image=0;

                // if(!empty($request->allow_after_image))
                //     $ServiceQuery->allow_after_image = $request->allow_after_image;
                // else
                //     $ServiceQuery->allow_after_image=0;
                if($request->hasFile('picture')) {
                    $ServiceQuery->picture = Helper::upload_file($request->file('picture'), 'health/article', 'article-'.time().'.png');
                }
                $ServiceQuery->save();

                //Send message to socket
                $requestData = ['type' => 'SERVICE_SETTING'];
                app('redis')->publish('settingsUpdate', json_encode( $requestData ));

                return Helper::getResponse(['status' => 200, 'message' => trans('admin.update')]);
            } else{
                return Helper::getResponse(['status' => 404, 'message' => trans('admin.not_found')]); 
            }
        }catch (\Throwable $e) {
            return Helper::getResponse(['status' => 404, 'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        // ONLY STATUS UPDATE ADDED INSTEAD OF HARD DELETE // return $this->removeModel($id);
        $SubCategory = HealthArticle::findOrFail($id);
        if($SubCategory){
            $SubCategory->active_status = 2;
            $SubCategory->save();
            return Helper::getResponse(['status' => 200, 'message' => trans('admin.update')]);
        } else{
            return Helper::getResponse(['status' => 404, 'message' => trans('admin.not_found')]); 
        }
    }

    public function subcategoriesList($categoryId)
    {
        $subCategories = HealthArticleSubcategory::select('id','article_subcategory_name','article_subcategory_status')
        ->where(['article_subcategory_status'=>1,'article_category_id'=>$categoryId])->get();
        return Helper::getResponse(['data' => $subCategories]);
    }



    public function updateStatus(Request $request, $id)
    {
        
        try {

            $datum = HealthArticle::findOrFail($id);
            
            if($request->has('status')){
                if($request->status == 1){
                    $datum->article_status = 0;
                }else{
                    $datum->article_status = 1;
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
