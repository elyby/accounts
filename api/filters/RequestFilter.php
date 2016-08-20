<?php
namespace api\filters;

use Yii;
use yii\base\ActionFilter;
use yii\web\ForbiddenHttpException;

// TODO: покрыть тестами
class RequestFilter extends ActionFilter {

    /**
     * @var string[] список IP адресов, с которых допустимо запрашивать указанные action'ы.
     * Каждый элемент массива представляет из себя один IP фильтр, который может быть точным IP адресом
     * или задавать маску адресов (например, 192.168.0.*) для покрытия сегмента сети.
     * Стандартным значением является `['127.0.0.1', '::1']`, что значит, что доступ разрешон только
     * с localhost'а.
     */
    public $allowedIPs = ['127.0.0.1', '::1'];

    /**
     * @var string[] список имён хостов, с которых можно запрашивать указанные action'ы.
     * Каждый элемент массива задаёт имя хоста, которое будет преобразовано в IP адрес, который
     * и будет сравниваться с IP адресом пользователя. Это полезно при использовании DNS (DDNS) для
     * организации динамического доступа.
     * Стандартным значением является `[]`, что означает, что хосты не проверяются.
     */
    public $allowedHosts = [];

    public function beforeAction($action) {
        $ip = Yii::$app->getRequest()->getUserIP();
        if ($this->checkIp($ip) || $this->checkByHost($ip)) {
            return true;
        }

        Yii::warning(
            'Access to ' . $action->controller->id . '::' . $action->id .
            ' is denied due to IP address restriction. The requesting IP address is ' . $ip,
            __METHOD__
        );

        throw new ForbiddenHttpException('You are not allowed to access this page.');
    }

    protected function checkIp(string $ip) : bool {
        foreach ($this->allowedIPs as $filter) {
            if ($filter === '*'
             || $filter === $ip
             || (($pos = strpos($filter, '*')) !== false && !strncmp($ip, $filter, $pos))
            ) {
                return true;
            }
        }

        return false;
    }

    protected function checkByHost(string $ip) : bool {
        foreach ($this->allowedHosts as $hostname) {
            $filter = gethostbyname($hostname);
            if ($filter === $ip) {
                return true;
            }
        }

        return false;
    }

}
