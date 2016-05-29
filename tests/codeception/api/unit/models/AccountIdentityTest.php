<?php
namespace codeception\api\unit\models;

use api\models\AccountIdentity;
use Codeception\Specify;
use Exception;
use tests\codeception\api\unit\DbTestCase;
use tests\codeception\common\fixtures\AccountFixture;
use Yii;
use yii\web\IdentityInterface;
use yii\web\UnauthorizedHttpException;

/**
 * @property AccountIdentity $accounts
 */
class AccountIdentityTest extends DbTestCase {
    use Specify;

    public function fixtures() {
        return [
            'accounts' => [
                'class' => AccountFixture::class,
                'dataFile' => '@tests/codeception/common/fixtures/data/accounts.php',
            ],
        ];
    }

    public function testFindIdentityByAccessToken() {
        $this->specify('success validate passed jwt token', function() {
            $identity = AccountIdentity::findIdentityByAccessToken($this->generateToken());
            expect($identity)->isInstanceOf(IdentityInterface::class);
            expect($identity->getId())->equals($this->accounts['admin']['id']);
        });

        // TODO: нормально оттестить исключение, если токен истёк
        return;

        $this->specify('get unauthorized with "Token expired message if token valid, but expire"', function() {
            $originalTimezone = date_default_timezone_get();
            date_default_timezone_set('America/Los_Angeles');
            try {
                $token = $this->generateToken();
                date_default_timezone_set($originalTimezone);
                AccountIdentity::findIdentityByAccessToken($token);
            } catch (Exception $e) {
                expect($e)->isInstanceOf(UnauthorizedHttpException::class);
                expect($e->getMessage())->equals('Token expired');
                return;
            }

            expect('if test valid, this should not happened', false)->true();
        });
    }

    protected function generateToken() {
        /** @var \api\components\User\Component $component */
        $component = Yii::$app->user;
        /** @var AccountIdentity $account */
        $account = AccountIdentity::findOne($this->accounts['admin']['id']);

        return $component->getJWT($account);
    }

}
