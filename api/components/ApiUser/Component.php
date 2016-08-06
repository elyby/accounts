<?php
namespace api\components\ApiUser;

use yii\web\User as YiiUserComponent;

/**
 * @property Identity|null $identity
 *
 * @method Identity|null getIdentity()
 */
class Component extends YiiUserComponent {

    public $identityClass = Identity::class;

    public $enableSession = false;

    public $loginUrl = null;

    public function getAccessChecker() {
        return new AuthChecker();
    }

}
