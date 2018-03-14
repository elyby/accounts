<?php
namespace common\models\amqp;

use yii\base\BaseObject;

class UsernameChanged extends BaseObject {

    public $accountId;

    public $oldUsername;

    public $newUsername;

}
