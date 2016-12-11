<?php
namespace common\models\amqp;

use yii\base\Object;

class AccountBanned extends Object {

    public $accountId;

    public $duration = -1;

    public $message = '';

}
