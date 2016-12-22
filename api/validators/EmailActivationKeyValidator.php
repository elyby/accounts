<?php
namespace api\validators;

use common\helpers\Error as E;
use common\models\EmailActivation;
use yii\validators\Validator;

/**
 * Валидатор для проверки полученного от пользователя кода активации.
 * В случае успешной валидации подменяет значение поля на актуальную модель
 */
class EmailActivationKeyValidator extends Validator {

    /**
     * @var int тип ключа. Если не указан, то валидирует по всем ключам.
     */
    public $type;

    public $keyRequired = E::KEY_REQUIRED;

    public $notExist = E::KEY_NOT_EXISTS;

    public $expired = E::KEY_EXPIRE;

    public function validateAttribute($model, $attribute) {
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
            $query->andWhere(['type' => $type]);
        }

        return $query->one();
    }

}
