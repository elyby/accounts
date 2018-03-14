<?php
namespace common\models\amqp;

use yii\base\BaseObject;

class EmailChanged extends BaseObject {

    public $accountId;

    public $oldEmail;

    public $newEmail;

}
