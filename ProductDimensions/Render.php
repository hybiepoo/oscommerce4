<?php

namespace common\extensions\ProductDimensions;

class Render extends \common\classes\extended\Widget {

    public $params;
    public $template;

    public function run() {
        return $this->render($this->template, $this->params);
    }
}