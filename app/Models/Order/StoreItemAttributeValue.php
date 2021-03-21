<?php


namespace App\Models\Order;

use App\Models\BaseModel;

class StoreItemAttributeValue extends BaseModel
{
  protected $connection = 'order';

    public function attributeValue()
	{
		return $this->belongsTo('App\AttributeValue')->withDefault([
                            'name' => 'No Data',
                            'value' => 'No Data',
                        ]);
	}


}
