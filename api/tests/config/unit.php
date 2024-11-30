<?php

use common\config\ConfigLoader;
use yii\helpers\ArrayHelper;

return ArrayHelper::merge(ConfigLoader::load('api'), [
]);
