<?php

namespace App\Models\Common;

use App\Models\BaseModel;

class CmsPage extends BaseModel
{
    protected $connection = 'common';
    protected $table = 'cms_pages';
}
