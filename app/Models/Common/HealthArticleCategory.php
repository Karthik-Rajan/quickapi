<?php

namespace App\Models\Common;

use App\Models\BaseModel;
use Auth;

class HealthArticleCategory extends BaseModel
{
    protected $connection = 'common';
    
    protected $hidden = [
     	'company_id','created_at', 'updated_at', 'deleted_at'
     ];
    
    public function subcategories()
    {
        return $this->hasMany('App\Models\Common\HealthArticleSubcategory', 'article_category_id', 'id');
    }

    public function articles()
    {
        return $this->hasMany('App\Models\Common\HealthArticle');
    }
}
