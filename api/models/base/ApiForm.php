<?php
declare(strict_types=1);

namespace api\models\base;

use yii\base\Model;

abstract class ApiForm extends Model {

    public function formName(): string {
        return '';
    }

}
