<?php
declare(strict_types=1);

namespace api\models\authentication;

use api\models\base\ApiForm;
use Yii;

class LogoutForm extends ApiForm {

    public function logout(): bool {
        $component = Yii::$app->user;
        $session = $component->getActiveSession();
        if ($session === null) {
            return true;
        }

        $session->delete();

        return true;
    }

}
