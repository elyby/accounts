<?php
declare(strict_types=1);

namespace api\modules\mojang\behaviors;

use Closure;
use Yii;
use yii\base\Behavior;
use yii\base\Event;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\UnauthorizedHttpException;

final class ServiceErrorConverterBehavior extends Behavior {

    public function events(): array {
        return [
            Response::EVENT_BEFORE_SEND => Closure::fromCallable([$this, 'beforeSend']),
        ];
    }

    private function beforeSend(Event $event): void {
        /** @var Response $response */
        $response = $event->sender;
        $data = $response->data;
        if ($data === null || !isset($data['status'])) {
            return;
        }

        $request = Yii::$app->request;
        $type = $data['type'];
        switch ($type) {
            case UnauthorizedHttpException::class:
                $response->data = [
                    'path' => '/' . $request->getPathInfo(),
                    'errorType' => 'UnauthorizedOperationException',
                    'error' => 'UnauthorizedOperationException',
                    'errorMessage' => 'Unauthorized',
                    'developerMessage' => 'Unauthorized',
                ];
                break;
            case NotFoundHttpException::class:
                $response->data = [
                    'path' => '/' . $request->getPathInfo(),
                    'errorType' => 'NOT_FOUND',
                    'error' => 'NOT_FOUND',
                    'errorMessage' => 'The server has not found anything matching the request URI',
                    'developerMessage' => 'The server has not found anything matching the request URI',
                ];
                break;
        }
    }

}
