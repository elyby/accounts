<?php
declare(strict_types=1);

namespace api\modules\mojang;

use yii\base\BootstrapInterface;

class Module extends \yii\base\Module implements BootstrapInterface {

    public $id = 'mojang';

    public $defaultRoute = 'api';

    /**
     * @param \yii\base\Application $app
     */
    public function bootstrap($app): void {
        $legacyHost = $app->params['authserverHost'];
        $app->getUrlManager()->addRules([
            "//{$legacyHost}/mojang/api/users/profiles/minecraft/<username>" => "{$this->id}/api/uuid-by-username",
            "//{$legacyHost}/mojang/api/user/profiles/<uuid>/names" => "{$this->id}/api/usernames-by-uuid",
            "POST //{$legacyHost}/mojang/api/profiles/minecraft" => "{$this->id}/api/uuids-by-usernames",
        ]);
    }

}
