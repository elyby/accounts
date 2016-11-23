<?php
namespace console\controllers;

use common\models\OauthAccessToken;
use yii\console\Controller;

class CleanupController extends Controller {

    public function actionAccessTokens() {
        $accessTokens = OauthAccessToken::find()
            ->andWhere(['<', 'expire_time', time()])
            ->each(1000);

        foreach($accessTokens as $token) {
            /** @var OauthAccessToken $token */
            $token->delete();
        }

        return self::EXIT_CODE_NORMAL;
    }

}
