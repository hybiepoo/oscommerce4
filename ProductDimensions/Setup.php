<?php

namespace common\extensions\ProductDimensions;

class Setup extends \common\classes\modules\SetupExtensions {

    public static function getVersionHistory() 
    {
        return [
            '1.0.0' => ['whats_new' => "Free module for adding product dimensions to help with shipping"],
        ];
    }

    public static function getDescription()
    {
        return 'This function allow you to enter product dimensions that will not be removed every time you save your product details.';
    }
	
	public static function install($platform_id, $migrate)
    {
        $migrate->createTableIfNotExists('pd_dims', [
            'products_id' => $migrate->primaryKey(),
            'length_cm' => $migrate->float(6),
			'width_cm' => $migrate->float(6),
			'height_cm' => $migrate->float(6),
			'length_in' => $migrate->float(6),
			'width_in' => $migrate->float(6),
			'height_in' => $migrate->float(6),
        ]);
    }
    
    public static function getDropDatabasesArray()
    {
        return ['pd_dims'];
    }
}
