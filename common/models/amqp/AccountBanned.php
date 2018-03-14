<?php
namespace common\models\amqp;

use yii\base\BaseObject;

class AccountBanned extends BaseObject {

    public $accountId;

    public $duration = -1;

    public $message = '';

}
