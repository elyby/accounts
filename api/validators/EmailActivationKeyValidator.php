<?php
namespace api\validators;

use common\helpers\Error as E;
use common\models\EmailActivation;
use yii\validators\Validator;

class EmailActivationKeyValidator extends Validator {

    public $notExist = E::KEY_NOT_EXISTS;

    public $expired = E::KEY_EXPIRE;

    public function validateValue($value) {
        if (($model = $this->findEmailActivationModel($value)) === null) {
            return [$this->notExist, []];
        }

        if ($model->isExpired()) {
            return [$this->expired, []];
        }

        return null;
    }

    /**
     * @param string $key
     * @return null|EmailActivation
     */
    protected function findEmailActivationModel($key) {
        return EmailActivation::findOne($key);
    }

}
