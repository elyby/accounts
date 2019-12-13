<?php
namespace api\validators;

use common\helpers\Error as E;
use common\models\EmailActivation;
use yii\validators\Validator;

/**
 * Validator to check the activation code received from the user.
 * In case of success it replaces the field value with the corresponding model.
 */
class EmailActivationKeyValidator extends Validator {

    /**
     * @var int the type of key. If not specified, it validates over all keys.
     */
    public $type;

    public $keyRequired = E::KEY_REQUIRED;

    public $notExist = E::KEY_NOT_EXISTS;

    public $expired = E::KEY_EXPIRE;

    public $skipOnEmpty = false;

    public function validateAttribute($model, $attribute): void {
        $value = $model->$attribute;
        if (empty($value)) {
            $this->addError($model, $attribute, $this->keyRequired);
            return;
        }

        $activation = $this->findEmailActivationModel($value, $this->type);
        if ($activation === null) {
            $this->addError($model, $attribute, $this->notExist);
            return;
        }

        if ($activation->isExpired()) {
            $this->addError($model, $attribute, $this->expired);
            return;
        }

        $model->$attribute = $activation;
    }

    protected function findEmailActivationModel(string $key, int $type = null): ?EmailActivation {
        $query = EmailActivation::find();
        $query->andWhere(['key' => $key]);
        if ($type !== null) {
            $query->withType($type);
        }

        return $query->one();
    }

}
