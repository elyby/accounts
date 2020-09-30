<?php
declare(strict_types=1);

namespace common\tests\unit\notifications;

use Codeception\Test\Unit;
use common\models\OauthSession;
use common\notifications\OAuthSessionRevokedNotification;

/**
 * @covers \common\notifications\OAuthSessionRevokedNotification
 */
class OAuthSessionRevokedNotificationTest extends Unit {

    public function testGetPayloads(): void {
        $oauthSession = new OauthSession();
        $oauthSession->account_id = 1;
        $oauthSession->client_id = 'mock-client';
        $oauthSession->revoked_at = 1601504074;

        $notification = new OAuthSessionRevokedNotification($oauthSession);
        $this->assertSame('oauth2.session_revoked', $notification::getType());
        $this->assertSame([
            'accountId' => 1,
            'clientId' => 'mock-client',
            'revoked' => '2020-09-30T22:14:34+00:00',
        ], $notification->getPayloads());
    }

}
