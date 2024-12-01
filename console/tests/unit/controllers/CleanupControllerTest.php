<?php
declare(strict_types=1);

namespace console\tests\unit\controllers;

use common\models\AccountSession;
use common\models\EmailActivation;
use common\models\OauthClient;
use common\tasks\ClearOauthSessions;
use common\tests\fixtures;
use console\controllers\CleanupController;
use console\tests\unit\TestCase;
use Yii;

class CleanupControllerTest extends TestCase {

    public function _fixtures(): array {
        return [
            'emailActivations' => fixtures\EmailActivationFixture::class,
            'accountsSessions' => fixtures\AccountSessionFixture::class,
            'oauthClients' => fixtures\OauthClientFixture::class,
            'oauthSessions' => fixtures\OauthSessionFixture::class,
        ];
    }

    public function testActionEmailKeys() {
        /** @var EmailActivation $expiredConfirmation */
        $expiredConfirmation = $this->tester->grabFixture('emailActivations', 'deeplyExpiredConfirmation');

        $controller = new CleanupController('cleanup', Yii::$app);
        $this->assertSame(0, $controller->actionEmailKeys());

        $this->tester->cantSeeRecord(EmailActivation::class, ['key' => $expiredConfirmation->key]);
    }

    public function testActionWebSessions() {
        /** @var AccountSession $expiredSession */
        $expiredSession = $this->tester->grabFixture('accountsSessions', 'very-expired-session');
        /** @var AccountSession $notRefreshedSession */
        $notRefreshedSession = $this->tester->grabFixture('accountsSessions', 'not-refreshed-session');
        $totalSessionsCount = AccountSession::find()->count();

        $controller = new CleanupController('cleanup', Yii::$app);
        $this->assertSame(0, $controller->actionWebSessions());

        $this->tester->cantSeeRecord(AccountSession::class, ['id' => $expiredSession->id]);
        $this->tester->cantSeeRecord(AccountSession::class, ['id' => $notRefreshedSession->id]);
        $this->assertSame($totalSessionsCount - 2, (int)AccountSession::find()->count());
    }

    public function testActionOauthClients(): void {
        $totalClientsCount = (int)OauthClient::find()->includeDeleted()->count();

        $controller = new CleanupController('cleanup', Yii::$app);
        $this->assertSame(0, $controller->actionOauthClients());

        $this->assertNull(OauthClient::find()->includeDeleted()->andWhere(['id' => 'deleted-oauth-client'])->one());
        $this->assertNotNull(OauthClient::find()->includeDeleted()->andWhere(['id' => 'deleted-oauth-client-with-sessions'])->one());
        $this->assertSame($totalClientsCount - 1, (int)OauthClient::find()->includeDeleted()->count());

        /** @var ClearOauthSessions $job */
        $job = $this->tester->grabLastQueuedJob();
        $this->assertInstanceOf(ClearOauthSessions::class, $job);
        $this->assertSame('deleted-oauth-client-with-sessions', $job->clientId);
        $this->assertNull($job->notSince);
    }

}
