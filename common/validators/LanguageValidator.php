<?php
declare(strict_types=1);

namespace common\validators;

use common\helpers\Error as E;
use Locale;
use ResourceBundle;
use yii\validators\Validator;

class LanguageValidator extends Validator {

    public $message = E::UNSUPPORTED_LANGUAGE;

    /**
     * The idea of this validator belongs to
     * https://github.com/lunetics/LocaleBundle/blob/1f5ee7f1802/Validator/LocaleValidator.php#L82-L88
     *
     * @param mixed $value
     * @return array|null
     */
    protected function validateValue($value): ?array {
        if (empty($value)) {
            return null;
        }

        $primary = Locale::getPrimaryLanguage($value);
        $region = Locale::getRegion($value);
        $locales = ResourceBundle::getLocales(''); // http://php.net/manual/ru/resourcebundle.locales.php#115965
        if (($region !== '' && strtolower($primary) !== strtolower($region)) && !in_array($value, $locales)) {
            return [$this->message, []];
        }

        return null;
    }

}
