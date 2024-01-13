{if $ext = \common\helpers\Acl::checkExtensionAllowed('ProductDimensions', 'allowed')}
    {$ext::renderDimensionsField($pInfo->products_id)}
{/if}
