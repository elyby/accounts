<?php
declare(strict_types=1);

namespace common\notifications;

use common\models\OauthSession;
use Webmozart\Assert\Assert;

final class OAuthSessionRevokedNotification implements NotificationInterface {

    private OauthSession $oauthSession;

    public function __construct(OauthSession $oauthSession) {
        Assert::notNull($oauthSession->revoked_at, 'OAuth session must be revoked');
        $this->oauthSession = $oauthSession;
    }

    public static function getType(): string {
        return 'oauth2.session_revoked';
    }

    public function getPayloads(): array {
        return [
            'accountId' => $this->oauthSession->account_id,
            'clientId' => $this->oauthSession->client_id,
            'revoked' => date('c', $this->oauthSession->revoked_at),
        ];
    }

}
