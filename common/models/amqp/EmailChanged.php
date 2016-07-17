<?php
namespace common\models\amqp;

use yii\base\Object;

class EmailChanged extends Object {

    public $accountId;

    public $oldEmail;

    public $newEmail;

}
