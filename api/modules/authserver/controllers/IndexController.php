<?php
namespace api\modules\authserver\controllers;

use api\controllers\Controller;

class IndexController extends Controller {

    // TODO: симулировать для этого модуля обработчик 404 ошибок, как был в фалконе
    public function notFoundAction(): void {
        /*return $this->response
            ->setStatusCode(404, 'Not Found')
            ->setContent('Page not found. Check our <a href="http://docs.ely.by">documentation site</a>.');*/
    }

}
