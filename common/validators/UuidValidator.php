<?php
namespace common\validators;

use InvalidArgumentException;
use Ramsey\Uuid\Uuid;
use yii\validators\Validator;

class UuidValidator extends Validator {

    public $skipOnEmpty = false;

    public $message = '{attribute} must be valid uuid';

    public function validateAttribute($model, $attribute) {
        try {
            $uuid = Uuid::fromString($model->$attribute)->toString();
            $model->$attribute = $uuid;
        } catch (InvalidArgumentException $e) {
            $this->addError($model, $attribute, $this->message, []);
        }
    }

}
