<?php
namespace api\modules\accounts\models;

use api\exceptions\ThisShouldNotHappenException;
use common\validators\LanguageValidator;

class ChangeLanguageForm extends AccountActionForm {

    public $lang;

    public function rules(): array {
        return [
            ['lang', 'required'],
            ['lang', LanguageValidator::class],
        ];
    }

    public function performAction(): bool {
        if (!$this->validate()) {
            return false;
        }

        $account = $this->getAccount();
        $account->lang = $this->lang;
        if (!$account->save()) {
            throw new ThisShouldNotHappenException('Cannot change user language');
        }

        return true;
    }

}
