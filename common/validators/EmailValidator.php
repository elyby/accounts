<?php
declare(strict_types=1);

namespace common\validators;

use common\helpers\Error as E;
use common\helpers\StringHelper;
use common\models\Account;
use Ely\Yii2\TempmailValidator;
use yii\base\Model;
use yii\db\QueryInterface;
use yii\validators;
use yii\validators\Validator;

final class EmailValidator extends Validator {

    /**
     * @phpstan-var \Closure(): int the function must return the account id for which the current validation is being performed.
     * Allows you to skip the email uniqueness check for the current account.
     */
    public ?\Closure $accountCallback = null;

    public $skipOnEmpty = false;

    public function validateAttribute($model, $attribute): void {
        $trim = new validators\FilterValidator(['filter' => [StringHelper::class, 'trim']]);

        $required = new validators\RequiredValidator();
        $required->message = E::EMAIL_REQUIRED;

        $length = new validators\StringValidator();
        $length->max = 255;
        $length->tooLong = E::EMAIL_TOO_LONG;

        $email = new validators\EmailValidator();
        $email->checkDNS = true;
        $email->enableIDN = true;
        $email->message = E::EMAIL_INVALID;

        $additionalEmail = new class extends Validator {
            protected function validateValue($value): ?array {
                // Disallow emails starting with slash since Postfix (or someone before?) can't correctly handle it
                if (str_starts_with($value, '/')) {
                    return [E::EMAIL_INVALID, []];
                }

                return null;
            }
        };

        $tempmail = new TempmailValidator();
        $tempmail->message = E::EMAIL_IS_TEMPMAIL;

        $blacklist = new class extends Validator {
            public $hosts = [
                'seznam.cz',
            ];

            protected function validateValue($value): ?array {
                $host = explode('@', $value)[1];
                if (in_array($host, $this->hosts, true)) {
                    return [E::EMAIL_HOST_IS_NOT_ALLOWED, []];
                }

                return null;
            }
        };

        $idnaDomain = new validators\FilterValidator(['filter' => function(string $value): string {
            [$name, $domain] = explode('@', $value);
            return idn_to_ascii($name) . '@' . idn_to_ascii($domain);
        }]);

        $unique = new validators\UniqueValidator();
        $unique->message = E::EMAIL_NOT_AVAILABLE;
        $unique->targetClass = Account::class;
        $unique->targetAttribute = 'email';
        if ($this->accountCallback !== null) {
            $unique->filter = function(QueryInterface $query): void {
                $query->andWhere(['NOT', ['id' => ($this->accountCallback)()]]);
            };
        }

        $this->executeValidation($trim, $model, $attribute)
        && $this->executeValidation($required, $model, $attribute)
        && $this->executeValidation($length, $model, $attribute)
        && $this->executeValidation($email, $model, $attribute)
        && $this->executeValidation($additionalEmail, $model, $attribute)
        && $this->executeValidation($tempmail, $model, $attribute)
        && $this->executeValidation($blacklist, $model, $attribute)
        && $this->executeValidation($idnaDomain, $model, $attribute)
        && $this->executeValidation($unique, $model, $attribute);
    }

    private function executeValidation(Validator $validator, Model $model, string $attribute): bool {
        $validator->validateAttribute($model, $attribute);
        return !$model->hasErrors($attribute);
    }

}
