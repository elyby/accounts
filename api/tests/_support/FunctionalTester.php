<?php
declare(strict_types=1);

namespace api\tests;

use api\tests\_generated\FunctionalTesterActions;
use Codeception\Actor;
use common\models\Account;
use InvalidArgumentException;
use Yii;

class FunctionalTester extends Actor {
    use FunctionalTesterActions;

    public function amAuthenticated(string $asUsername = 'admin') {
        /** @var Account $account */
        $account = Account::findOne(['username' => $asUsername]);
        if ($account === null) {
            throw new InvalidArgumentException("Cannot find account for username \"$asUsername\"");
        }

        $result = Yii::$app->user->createJwtAuthenticationToken($account, false);
        $this->amBearerAuthenticated($result->getJwt());

        return $account->id;
    }

    public function notLoggedIn(): void {
        $this->haveHttpHeader('Authorization', null);
        Yii::$app->user->logout();
    }

    public function canSeeAuthCredentials($expectRefresh = false): void {
        $this->canSeeResponseJsonMatchesJsonPath('$.access_token');
        $this->canSeeResponseJsonMatchesJsonPath('$.expires_in');
        if ($expectRefresh) {
            $this->canSeeResponseJsonMatchesJsonPath('$.refresh_token');
        } else {
            $this->cantSeeResponseJsonMatchesJsonPath('$.refresh_token');
        }
    }

}
