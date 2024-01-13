<?php

namespace common\extensions\ProductDimensions\models;

use yii\db\ActiveRecord;

class Dimensions extends ActiveRecord
{
    public static function tableName()
    {
        return 'pd_dims';
    }
    
}