<?php
namespace api\models;

use api\models\base\BaseAccountForm;
use api\modules\accounts\models\AccountInfo;
use common\models\Account;

class OauthAccountInfo extends BaseAccountForm {

    private $model;

    public function __construct(Account $account, array $config = []) {
        parent::__construct($account, $config);
        $this->model = new AccountInfo($account);
    }

    public function info(): array {
        $response = $this->model->info();

        $response['profileLink'] = $response['elyProfileLink'];
        unset($response['elyProfileLink']);
        $response['preferredLanguage'] = $response['lang'];
        unset($response['lang']);

        return $response;
    }

}
