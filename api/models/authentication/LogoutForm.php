<?php
namespace api\models\authentication;

use api\aop\annotations\CollectModelMetrics;
use api\models\base\ApiForm;
use Yii;

class LogoutForm extends ApiForm {

    /**
     * @CollectModelMetrics(prefix="authentication.logout")
     * @return bool
     */
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
