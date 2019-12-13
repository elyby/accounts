<?php
namespace api\modules\accounts\models;

use api\aop\annotations\CollectModelMetrics;
use common\validators\LanguageValidator;
use Webmozart\Assert\Assert;

class ChangeLanguageForm extends AccountActionForm {

    public $lang;

    public function rules(): array {
        return [
            ['lang', 'required'],
            ['lang', LanguageValidator::class],
        ];
    }

    /**
     * @CollectModelMetrics(prefix="accounts.switchLanguage")
     */
    public function performAction(): bool {
        if (!$this->validate()) {
            return false;
        }

        $account = $this->getAccount();
        $account->lang = $this->lang;
        Assert::true($account->save(), 'Cannot change user language');

        return true;
    }

}
