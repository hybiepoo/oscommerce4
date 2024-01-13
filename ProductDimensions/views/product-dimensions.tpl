{use class="\yii\helpers\Html"}
<div class="widget-header">
	<h4>Product Dimensions</h4>
</div>
<div class="w-line-row w-line-row-1">
    <div class="wl-td">
        <label>Length (cm)</label>
        {Html::input('text', 'length', $length, ['class' => 'form-control-small'])}
    </div>
	<div class="wl-td">
        <label>Width (cm)</label>
        {Html::input('text', 'width', $width, ['class' => 'form-control-small'])}
    </div>
	<div class="wl-td">
        <label>Height (cm)</label>
        {Html::input('text', 'height', $height, ['class' => 'form-control-small'])}
    </div>
</div>