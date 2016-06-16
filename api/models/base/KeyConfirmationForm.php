<?php
namespace api\models\base;

use common\helpers\Error as E;
use api\validators\EmailActivationKeyValidator;
use common\models\EmailActivation;

class KeyConfirmationForm extends ApiForm {

    public $key;

    private $model;

    public function rules() {
        return [
            // TODO: нужно провалидировать количество попыток ввода кода для определённого IP адреса и в случае чего запросить капчу
            ['key', 'required', 'message' => E::KEY_REQUIRED],
            ['key', EmailActivationKeyValidator::class],
        ];
    }

    /**
     * @return EmailActivation|null
     */
    public function getActivationCodeModel() {
        if ($this->model === null) {
            $this->model = EmailActivation::findOne($this->key);
        }

        return $this->model;
    }

}
