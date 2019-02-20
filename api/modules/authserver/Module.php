<?php
declare(strict_types=1);

namespace api\modules\authserver;

use Yii;
use yii\base\BootstrapInterface;
use yii\web\NotFoundHttpException;

class Module extends \yii\base\Module implements BootstrapInterface {

    public $id = 'authserver';

    public $defaultRoute = 'index';

    public function beforeAction($action) {
        if (!parent::beforeAction($action)) {
            return false;
        }

        $this->checkHost();

        return true;
    }

    /**
     * @param \yii\base\Application $app
     */
    public function bootstrap($app) {
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

    /**
     * Поскольку это legacy метод и документации в новой среде для него не будет,
     * нет смысла выставлять на показ внутренние url, так что ограничиваем доступ
     * только для заходов по старому домену
     *
     * @throws NotFoundHttpException
     */
    protected function checkHost() {
        if (parse_url(Yii::$app->request->getHostInfo(), PHP_URL_HOST) !== Yii::$app->params['authserverHost']) {
            throw new NotFoundHttpException();
        }
    }

}
