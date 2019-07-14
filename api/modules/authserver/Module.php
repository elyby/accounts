<?php
declare(strict_types=1);

namespace api\modules\authserver;

use Yii;
use yii\base\BootstrapInterface;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class Module extends \yii\base\Module implements BootstrapInterface {

    public $id = 'authserver';

    public $defaultRoute = 'index';

    public function beforeAction($action): bool {
        if (!parent::beforeAction($action)) {
            return false;
        }

        $this->checkHost();

        return true;
    }

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

    /**
     * Since this is a legacy method and there will be no documentation for it in the new environment,
     * there is no point in displaying the internal API, so we are limiting access only to logons from the old domain.
     *
     * @throws NotFoundHttpException
     */
    protected function checkHost() {
        if (parse_url(Yii::$app->request->getHostInfo(), PHP_URL_HOST) !== Yii::$app->params['authserverHost']) {
            throw new NotFoundHttpException();
        }
    }

}
