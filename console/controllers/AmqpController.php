<?php
namespace console\controllers;

use Ely\Amqp\ControllerTrait;
use Exception;
use PhpAmqpLib\Message\AMQPMessage;
use Yii;
use yii\console\Controller;
use yii\db\Exception as YiiDbException;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;

abstract class AmqpController extends Controller {
    use ControllerTrait {
        callback as _callback;
    }

    private $reconnected = false;

    public final function actionIndex() {
        $this->start();
    }

    public function getRoutesMap() {
        return [];
    }

    /**
     * Переопределяем метод callback, чтобы избержать логгирования в консоль ошибок,
     * связанных с обвалом того или иного соединения. Это нормально, PHP рождён умирать,
     * а не работать 24/7 в качестве демона.
     *
     * @param AMQPMessage $msg
     * @throws YiiDbException
     */
    public function callback(AMQPMessage $msg) {
        try {
            $this->_callback($msg);
        } catch (YiiDbException $e) {
            if ($this->reconnected || !$this->isRestorableException($e)) {
                throw $e;
            }

            $this->reconnected = true;
            Yii::$app->db->close();
            Yii::$app->db->open();
            $this->callback($msg);
        }

        $this->reconnected = false;
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

    private function isRestorableException(Exception $e): bool {
        return strpos($e->getMessage(), 'MySQL server has gone away') !== false
            || strcmp($e->getMessage(), 'Error while sending QUERY packet') !== false;
    }

}
