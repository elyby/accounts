<?php
namespace console\controllers;

use Ely\Amqp\ControllerTrait;
use Yii;
use yii\console\Controller;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;

abstract class AmqpController extends Controller {
    use ControllerTrait;

    public final function actionIndex() {
        $this->start();
    }

    public function getRoutesMap() {
        return [];
    }

    /**
     * @inheritdoc
     */
    protected function getConnection() {
        return Yii::$app->amqp->getConnection();
    }

    /**
     * @inheritdoc
     */
    protected function buildRouteActionName($route) {
        return ArrayHelper::getValue($this->getRoutesMap(), $route, 'route' . Inflector::camelize($route));
    }

}
