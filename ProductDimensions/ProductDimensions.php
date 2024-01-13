<?php

namespace common\extensions\ProductDimensions;

class ProductDimensions extends \common\classes\modules\ModuleExtensions {
	
	public static function getAdminHooks()
    {
        $path = \Yii::getAlias('@common') . DIRECTORY_SEPARATOR . 'extensions' . DIRECTORY_SEPARATOR . 'ProductDimensions' . DIRECTORY_SEPARATOR . 'hooks' . DIRECTORY_SEPARATOR;
        return [
            [
                'page_name' => 'categories/productedit',
                'page_area' => '',
                'extension_file' => $path . 'categories.productedit.php',
            ],
            
            [
                'page_name' => 'categories/productedit',
                'page_area' => 'size/bottom',
                'extension_file' => $path . 'categories.productedit.size-bottom.tpl',
            ],
            
        ];
    }
	public static function renderDimensionsField($id=0) 
    {
		//$length = \common\extensions\ProductDimensions\helpers\Product::getLength($id);
		//$width = \common\extensions\ProductDimensions\helpers\Product::getWidth($id);
		//$height = \common\extensions\ProductDimensions\helpers\Product::getHeight($id);
		$dims = \common\extensions\ProductDimensions\helpers\Product::getDims($id);
		//return \common\extensions\ProductDimensions\Render::widget(['template' => 'product-dimensions', 'params' => ['length' => $length, 'width' => $width, 'height' => $height]]);
		//print_r($dims);
		echo "Product id: " . $id;
		return \common\extensions\ProductDimensions\Render::widget(['template' => 'product-dimensions', 'params' => ['length' => $dims->length_cm, 'width' => $dims->width_cm, 'height' => $dims->height_cm, 'products_id' => $id]]);
    }
	
	public static function saveProductField($id) 
    {
        $length = filter_var( \Yii::$app->request->post('length', ''), FILTER_SANITIZE_STRING);
		$width = filter_var( \Yii::$app->request->post('width', ''), FILTER_SANITIZE_STRING);
		$height = filter_var( \Yii::$app->request->post('height', ''), FILTER_SANITIZE_STRING);
		$id = filter_var( \Yii::$app->request->post('products_id', ''), FILTER_SANITIZE_STRING);
        $dimensionsRecord = \common\extensions\ProductDimensions\models\Dimensions::find()
                ->where(['products_id' => $id])
                ->one();
		exit("ID is " . $id . ", Length is " . $length . ", Width is " . $width . ", Height is " . $height);
		if (!($dimensionsRecord instanceof \common\extensions\ProductDimensions\models\Dimensions)) {
		$dimensionsRecord = new \common\extensions\ProductDimensions\models\Dimensions();
		$dimensionsRecord->products_id = $id;
		}
        $dimensionsRecord->length_cm = $length;
		$dimensionsRecord->width_cm = $width;
		$dimensionsRecord->height_cm = $height;
		$dimensionsRecord->length_in = $height / 2.54;
		$dimensionsRecord->width_in = $width / 2.54;
		$dimensionsRecord->height_in = $height / 2.54;
		$dimensionsRecord->dimensions_in = $id;
        $dimensionsRecord->save();
    }

}