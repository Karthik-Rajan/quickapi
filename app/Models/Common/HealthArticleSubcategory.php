<?php

namespace App\Models\Common;

use App\Models\BaseModel;
use Auth;

class HealthArticleSubcategory extends BaseModel
{
    protected $connection = 'common';

    protected $hidden = [
     	'company_id','created_at', 'updated_at', 'deleted_at'
     ];

    public function articleCategory()
    {
        return $this->belongsTo('App\Models\Common\HealthArticleCategory');
    }

    public function article()
    {
        return $this->hasMany('App\Models\Common\HealthArticle', 'article_subcategory_id', 'id');
    }


}
