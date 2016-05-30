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
            'accounts' => AccountFixture::class,
        ];
    }

    public function testFindIdentityByAccessToken() {
        $this->specify('success validate passed jwt token', function() {
            $identity = AccountIdentity::findIdentityByAccessToken($this->generateToken());
            expect($identity)->isInstanceOf(IdentityInterface::class);
            expect($identity->getId())->equals($this->accounts['admin']['id']);
        });

        $this->specify('get unauthorized exception with "Token expired" message if token valid, but expire', function() {
            $expiredToken = 'eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJodHRwOlwvXC9sb2NhbGhvc3Q6ODA4MCIsImlzcyI6Imh0d' .
                            'HA6XC9cL2xvY2FsaG9zdDo4MDgwIiwiaWF0IjoxNDY0NTkzMTkzLCJleHAiOjE0NjQ1OTY3OTN9.DV' .
                            '8uwh0OQhBYXkrNvxwJeO-kEjb9MQeLr3-6GoHM7RY';

            try {
                AccountIdentity::findIdentityByAccessToken($expiredToken);
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
