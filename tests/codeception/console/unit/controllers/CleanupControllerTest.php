<?php
namespace codeception\console\unit\controllers;

use common\models\EmailActivation;
use console\controllers\CleanupController;
use tests\codeception\common\fixtures\EmailActivationFixture;
use tests\codeception\console\unit\TestCase;
use Yii;

class CleanupControllerTest extends TestCase {

    public function _fixtures() {
        return [
            'emailActivations' => EmailActivationFixture::class,
        ];
    }

    public function testActionAccessTokens() {
        /** @var EmailActivation $expiredConfirmation */
        $expiredConfirmation = $this->tester->grabFixture('emailActivations', 'deeplyExpiredConfirmation');

        $controller = new CleanupController('cleanup', Yii::$app);
        $this->assertEquals(0, $controller->actionEmailKeys());

        $this->tester->cantSeeRecord(EmailActivation::className(), ['key' => $expiredConfirmation->key]);
    }

}
