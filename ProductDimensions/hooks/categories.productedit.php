<?php
if ($ProductDimensions = \common\helpers\Acl::checkExtensionAllowed('ProductDimensions', 'allowed')) {
	$ProductDimensions::saveProductField((int) $pInfo->product_id);
	//$CustomersRank::saveCustomerField((int) $cInfo->customers_id);
}