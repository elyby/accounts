<?php
declare(strict_types=1);

namespace api\modules\authserver;

use Yii;
use yii\base\BootstrapInterface;
use yii\base\Module as BaseModule;
use yii\web\Response;

class Module extends BaseModule implements BootstrapInterface {

    public $id = 'authserver';

    public $defaultRoute = 'index';

    public function afterAction($action, $result) {
        if ($result === null) {
            Yii::$app->response->format = Response::FORMAT_RAW;
        }

        return parent::afterAction($action, $result);
    }

    /**
     * @param \yii\base\Application $app
     */
    public function bootstrap($app): void {
        $legacyHost = $app->params['authserverHost'];
        $app->getUrlManager()->addRules([
            "//{$legacyHost}/authserver/auth/<action>" => "{$this->id}/authentication/<action>",
        ], false);
    }

    public static function info($message) {
        Yii::info($message, 'legacy-authserver');
    }

    public static function error($message) {
        Yii::info($message, 'legacy-authserver');
    }

}
