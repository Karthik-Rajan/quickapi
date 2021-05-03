<?php

namespace App\Http\Controllers\V1\Order\Admin\Resource;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Traits\Actions;
use App\Helpers\Helper;
use App\Models\Common\HealthArticleCategory;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Storage;
use Auth;
use Validator;
use DB;
use App\Models\Common\AdminService;
use App\Models\Common\Menu;

class HealthCategoryController extends Controller
{
    use Actions;
    private $model;
    private $request;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct(HealthArticleCategory $model)
    {
        $this->model = $model;
    }
    public function index(Request $request)
    {
        $datum = HealthArticleCategory::where('company_id', Auth::user()->company_id);
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
            'article_category_name' => 'required|unique:common.health_article_categories,article_category_name|max:255|regex:/^[a-zA-Z0-9\s]+$/',
            'article_category_alias_name' => 'required|max:255|regex:/^[a-zA-Z0-9\s]+$/',
            /*'picture' => 'mimes:jpeg,jpg,bmp,png|max:5242880',
            'service_category_order' => 'required|integer|between:0,10',*/
            'article_category_status' => 'required',
        ]);
        try {
            $HealthArticleCategory = new HealthArticleCategory;
            $HealthArticleCategory->company_id = Auth::user()->company_id; 
            $HealthArticleCategory->article_category_name = $request->article_category_name;
            $HealthArticleCategory->alias_name = $request->article_category_alias_name;            
            $HealthArticleCategory->article_category_order = $request->article_category_order;
            $HealthArticleCategory->article_category_status = $request->article_category_status;
           // $HealthArticleCategory->price_choose = $request->price_choose;
            if($request->hasFile('picture')) {
                $HealthArticleCategory->picture = Helper::upload_file($request->file('picture'), 'health/article', 'cat-'.time().'.png');
            }
            $HealthArticleCategory->save();
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
            $HealthArticleCategory = HealthArticleCategory::findOrFail($id);
            return Helper::getResponse(['data' => $HealthArticleCategory]);
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
            'article_category_name' => 'required|max:255|regex:/^[a-zA-Z0-9\s]+$/',
            'article_category_alias_name' => 'required|max:255|regex:/^[a-zA-Z0-9\s]+$/',
            /*'picture' => 'mimes:jpeg,jpg,bmp,png|max:5242880',
            'service_category_order' => 'required|integer|between:0,10',*/
            'article_category_status' => 'required',
        ]);
        try{
            $HealthArticleCategory = HealthArticleCategory::findOrFail($id);
            if($HealthArticleCategory){
                $HealthArticleCategory->article_category_name = $request->article_category_name;
                $HealthArticleCategory->alias_name = $request->article_category_alias_name;           
                $HealthArticleCategory->article_category_order = $request->article_category_order;
                $HealthArticleCategory->article_category_status = $request->article_category_status;
                // $HealthArticleCategory->price_choose = $request->price_choose;
                if($request->hasFile('picture')) {
                    $HealthArticleCategory->picture = Helper::upload_file($request->file('picture'), 'health/article', 'cat-'.time().'.png');
                }
                $HealthArticleCategory->save();
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
        $HealthArticleCategory = HealthArticleCategory::findOrFail($id);
        if($HealthArticleCategory){
            $HealthArticleCategory->article_category_status = 2;
            $HealthArticleCategory->save();
            return Helper::getResponse(['status' => 200, 'message' => trans('admin.update')]);
        } else{
            return Helper::getResponse(['status' => 404, 'message' => trans('admin.not_found')]); 
        }
    }

    public function updateStatus(Request $request, $id)
    {
        
        try {

            $datum = HealthArticleCategory::findOrFail($id);
            
            if($request->has('status') && $request->status == 1){
                
                $datum->article_category_status = 0;
            }else{
                $datum->article_category_status = 1;
            }
            $datum->save();

            return Helper::getResponse(['status' => 200, 'message' => trans('admin.activation_status')]);

        } 

        catch (\Throwable $e) {
            return Helper::getResponse(['status' => 404, 'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
        }
    }
}
