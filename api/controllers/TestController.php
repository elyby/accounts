<?php
declare(strict_types=1);

namespace api\controllers;

final class TestController extends \yii\web\Controller {

    public function actionIndex(): array {
        return [
            'isSecure' => $this->request->getIsSecureConnection(),
            'host' => $this->request->getHostInfo(),
            'headers' => $this->request->headers->toArray(),
        ];
    }

}
