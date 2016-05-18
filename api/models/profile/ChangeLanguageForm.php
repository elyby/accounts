<?php
namespace api\models\profile;

use api\models\base\ApiForm;
use common\models\Account;
use common\validators\LanguageValidator;
use yii\base\ErrorException;

class ChangeLanguageForm extends ApiForm {

    public $lang;

    private $account;

    public function __construct(Account $account, array $config = []) {
        $this->account = $account;
        parent::__construct($config);
    }

    public function rules() {
        return [
            ['lang', 'required'],
            ['lang', LanguageValidator::class],
        ];
    }

    public function applyLanguage() : bool {
        if (!$this->validate()) {
            return false;
        }

        $account = $this->getAccount();
        $account->lang = $this->lang;
        if (!$account->save()) {
            throw new ErrorException('Cannot change user language');
        }

        return true;
    }

    public function getAccount() : Account {
        return $this->account;
    }

}
