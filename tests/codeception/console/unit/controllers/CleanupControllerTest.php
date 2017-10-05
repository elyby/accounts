<?php
namespace codeception\console\unit\controllers;

use common\models\AccountSession;
use common\models\EmailActivation;
use common\models\MinecraftAccessKey;
use console\controllers\CleanupController;
use tests\codeception\common\fixtures\AccountSessionFixture;
use tests\codeception\common\fixtures\EmailActivationFixture;
use tests\codeception\common\fixtures\MinecraftAccessKeyFixture;
use tests\codeception\console\unit\TestCase;
use Yii;

class CleanupControllerTest extends TestCase {

    public function _fixtures() {
        return [
            'emailActivations' => EmailActivationFixture::class,
            'minecraftSessions' => MinecraftAccessKeyFixture::class,
            'accountsSessions' => AccountSessionFixture::class,
        ];
    }

    public function testActionEmailKeys() {
        /** @var EmailActivation $expiredConfirmation */
        $expiredConfirmation = $this->tester->grabFixture('emailActivations', 'deeplyExpiredConfirmation');

        $controller = new CleanupController('cleanup', Yii::$app);
        $this->assertEquals(0, $controller->actionEmailKeys());

        $this->tester->cantSeeRecord(EmailActivation::class, ['key' => $expiredConfirmation->key]);
    }

    public function testActionMinecraftSessions() {
        /** @var MinecraftAccessKey $expiredSession */
        $expiredSession = $this->tester->grabFixture('minecraftSessions', 'expired-token');

        $controller = new CleanupController('cleanup', Yii::$app);
        $this->assertEquals(0, $controller->actionMinecraftSessions());

        $this->tester->cantSeeRecord(MinecraftAccessKey::class, ['access_token' => $expiredSession->access_token]);
    }

    public function testActionWebSessions() {
        /** @var AccountSession $expiredSession */
        $expiredSession = $this->tester->grabFixture('accountsSessions', 'very-expired-session');
        /** @var AccountSession $notRefreshedSession */
        $notRefreshedSession = $this->tester->grabFixture('accountsSessions', 'not-refreshed-session');
        $totalSessionsCount = AccountSession::find()->count();

        $controller = new CleanupController('cleanup', Yii::$app);
        $this->assertEquals(0, $controller->actionWebSessions());

        $this->tester->cantSeeRecord(AccountSession::class, ['id' => $expiredSession->id]);
        $this->tester->cantSeeRecord(AccountSession::class, ['id' => $notRefreshedSession->id]);
        $this->assertEquals($totalSessionsCount - 2, AccountSession::find()->count());
    }

}
