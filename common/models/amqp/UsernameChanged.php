<?php
namespace common\models\amqp;

use yii\base\Object;

class UsernameChanged extends Object {

    public $accountId;

    public $oldUsername;

    public $newUsername;

}
