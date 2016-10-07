<?php
namespace api\modules\authserver;

use Yii;
use yii\base\BootstrapInterface;
use yii\base\InvalidConfigException;
use yii\web\NotFoundHttpException;

class Module extends \yii\base\Module implements BootstrapInterface {

    public $id = 'authserver';

    public $defaultRoute = 'index';

    /**
     * @var string базовый домен, запросы на который этот модуль должен обрабатывать
     */
    public $host = 'authserver.ely.by';

    public function init() {
        parent::init();
        if ($this->host === null) {
            throw new InvalidConfigException('base domain must be specified');
        }
    }

    public function beforeAction($action) {
        if (!parent::beforeAction($action)) {
            return false;
        }

        $this->checkHost();

        return true;
    }

    /**
     * @param \yii\base\Application $app the application currently running
     */
    public function bootstrap($app) {
        $app->getUrlManager()->addRules([
            "<protocol:http|https>://$this->host/$this->id/auth/<action>" => "$this->id/authentication/<action>",
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
        if (parse_url(Yii::$app->request->getHostInfo(), PHP_URL_HOST) !== $this->host) {
            throw new NotFoundHttpException();
        }
    }

}
