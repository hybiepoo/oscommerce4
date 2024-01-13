<?php

namespace common\extensions\ProductDimensions\helpers;

class Product {


	public static function getDims($id) {
       $dimensionsRecord = \common\extensions\ProductDimensions\models\Dimensions::find()
                ->where(['products_id' => $id])
                ->one();
       if ($dimensionsRecord instanceof \common\extensions\ProductDimensions\models\Dimensions) {
           return $dimensionsRecord;
       }
        return '';
    }
/*	
   public static function getLength($id) {
       $dimensionsRecord = \common\extensions\ProductDimensions\models\Dimensions::find()
                ->where(['products_id' => $id])
                ->one();
       if ($dimensionsRecord instanceof \common\extensions\ProductDimensions\models\Dimensions) {
           return $dimensionsRecord->length_cm;
       }
        return '';
    }
    public static function getWidth($id) {
       $dimensionsRecord = \common\extensions\ProductDimensions\models\Dimensions::find()
                ->where(['products_id' => $id])
                ->one();
       if ($dimensionsRecord instanceof \common\extensions\ProductDimensions\models\Dimensions) {
           return $dimensionsRecord->width_cm;
       }
        return '';
    }
	public static function getHeight($id) {
       $dimensionsRecord = \common\extensions\ProductDimensions\models\Dimensions::find()
                ->where(['products_id' => $id])
                ->one();
       if ($dimensionsRecord instanceof \common\extensions\ProductDimensions\models\Dimensions) {
           return $dimensionsRecord->height_cm;
       }
        return '';
    } */
}