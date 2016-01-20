<?php
namespace api\models;

use common\models\EmailActivation;

class BaseKeyConfirmationForm extends BaseApiForm {

    public $key;

    private $model;

    public function rules() {
        return [
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
