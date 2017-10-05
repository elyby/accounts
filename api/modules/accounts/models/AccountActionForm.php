<?php
namespace api\modules\accounts\models;

use api\models\base\BaseAccountForm;

abstract class AccountActionForm extends BaseAccountForm {

    abstract public function performAction(): bool;

}
