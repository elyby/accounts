<?php
namespace api\modules\authserver;

use yii\base\BootstrapInterface;
use yii\base\InvalidConfigException;

class Module extends \yii\base\Module implements BootstrapInterface {

    public $id = 'authserver';

    public $defaultRoute = 'index';

    /**
     * @var string базовый домен, запросы на который этот модуль должен обрабатывать
     */
    public $baseDomain = 'https://authserver.ely.by';

    public function init() {
        parent::init();
        if ($this->baseDomain === null) {
            throw new InvalidConfigException('base domain must be specified');
        }
    }

    /**
     * @param \yii\base\Application $app the application currently running
     */
    public function bootstrap($app) {
        $app->getUrlManager()->addRules([
            $this->baseDomain . '/' . $this->id . '/auth/<action>' => $this->id . '/authentication/<action>',
        ], false);
    }

}
