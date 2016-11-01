<?php
namespace common\validators;

use common\helpers\Error as E;
use common\models\Account;
use Ely\Yii2\TempmailValidator;
use yii\base\Model;
use yii\db\QueryInterface;
use yii\validators;
use yii\validators\Validator;

class EmailValidator extends Validator {

    /**
     * @var \Closure функция должна возвращать id аккаунта, относительно которого проводится
     * текущая валидация. Позволяет пропустить проверку email для текущего аккаунта.
     */
    public $accountCallback;

    public $skipOnEmpty = false;

    public function validateAttribute($model, $attribute) {
        $filter = new validators\FilterValidator(['filter' => 'trim']);

        $required = new validators\RequiredValidator();
        $required->message = E::EMAIL_REQUIRED;

        $length = new validators\StringValidator();
        $length->max = 255;
        $length->tooLong = E::EMAIL_TOO_LONG;

        $email = new validators\EmailValidator();
        $email->checkDNS = true;
        $email->enableIDN = true;
        $email->message = E::EMAIL_INVALID;

        $tempmail = new TempmailValidator();
        $tempmail->message = E::EMAIL_IS_TEMPMAIL;

        $unique = new validators\UniqueValidator();
        $unique->message = E::EMAIL_NOT_AVAILABLE;
        $unique->targetClass = Account::class;
        $unique->targetAttribute = 'email';
        if ($this->accountCallback !== null) {
            $unique->filter = function(QueryInterface $query) {
                $query->andWhere(['NOT', ['id' => ($this->accountCallback)()]]);
            };
        }

        $this->executeValidation($filter, $model, $attribute) &&
        $this->executeValidation($required, $model, $attribute) &&
        $this->executeValidation($length, $model, $attribute) &&
        $this->executeValidation($email, $model, $attribute) &&
        $this->executeValidation($tempmail, $model, $attribute) &&
        $this->executeValidation($unique, $model, $attribute);
    }

    protected function executeValidation(Validator $validator, Model $model, string $attribute) {
        $validator->validateAttribute($model, $attribute);

        return !$model->hasErrors($attribute);
    }

}
