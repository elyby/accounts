<?php
namespace common\validators;

use Closure;
use common\helpers\Error as E;
use common\helpers\StringHelper;
use common\models\Account;
use yii\base\Model;
use yii\db\QueryInterface;
use yii\validators;
use yii\validators\Validator;

class UsernameValidator extends Validator {

    /**
     * @phpstan-var \Closure(): int the function must return the account id for which the current validation is being performed.
     * Allows you to skip the username check for the current account.
     */
    public ?Closure $accountCallback = null;

    public $skipOnEmpty = false;

    public function validateAttribute($model, $attribute): ?array {
        $filter = new validators\FilterValidator(['filter' => [StringHelper::class, 'trim']]);

        $required = new validators\RequiredValidator();
        $required->message = E::USERNAME_REQUIRED;

        $length = new validators\StringValidator();
        $length->min = 3;
        $length->max = 21;
        $length->tooShort = E::USERNAME_TOO_SHORT;
        $length->tooLong = E::USERNAME_TOO_LONG;

        $pattern = new validators\RegularExpressionValidator(['pattern' => '/^[\p{L}\d\-_.!$%^&*()\[\]:;]+$/u']);
        $pattern->message = E::USERNAME_INVALID;

        $unique = new validators\UniqueValidator();
        $unique->message = E::USERNAME_NOT_AVAILABLE;
        $unique->targetClass = Account::class;
        $unique->targetAttribute = 'username';
        if ($this->accountCallback !== null) {
            $unique->filter = function(QueryInterface $query): void {
                $query->andWhere(['NOT', ['id' => ($this->accountCallback)()]]);
            };
        }

        $this->executeValidation($filter, $model, $attribute)
        && $this->executeValidation($required, $model, $attribute)
        && $this->executeValidation($length, $model, $attribute)
        && $this->executeValidation($pattern, $model, $attribute)
        && $this->executeValidation($unique, $model, $attribute);

        return null;
    }

    protected function executeValidation(Validator $validator, Model $model, string $attribute) {
        $validator->validateAttribute($model, $attribute);

        return !$model->hasErrors($attribute);
    }

}
