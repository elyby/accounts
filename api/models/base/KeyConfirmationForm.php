<?php
namespace api\models\base;

use common\models\EmailActivation;

class KeyConfirmationForm extends ApiForm {

    public $key;

    private $model;

    public function rules() {
        return [
            // TODO: нужно провалидировать количество попыток ввода кода для определённого IP адреса и в случае чего запросить капчу
            ['key', 'required', 'message' => 'error.key_is_required'],
            ['key', 'validateKey'],
        ];
    }

    public function validateKey($attribute) {
        if (!$this->hasErrors()) {
            if ($this->getActivationCodeModel() === null) {
                $this->addError($attribute, "error.{$attribute}_not_exists");
            }
        }
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
