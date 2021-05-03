<?php

namespace App\Models\Common;

use App\Models\BaseModel;
use Auth;

class HealthArticle extends BaseModel
{
    protected $connection = 'common';

    protected $hidden = [
     	'company_id','created_at', 'updated_at', 'deleted_at'
     ];


    public function articleCategory()
    {
        return $this->belongsTo('App\Models\Common\HealthArticleCategory');
    }
    public function articleSubCategory()
    {
        return $this->belongsTo('App\Models\Common\HealthArticleSubcategory','article_subcategory_id');
    }
}
