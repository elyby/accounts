<?php
namespace common\models;

class OauthScope {

    const OFFLINE_ACCESS = 'offline_access';
    const MINECRAFT_SERVER_SESSION = 'minecraft_server_session';
    const ACCOUNT_INFO = 'account_info';
    const ACCOUNT_EMAIL = 'account_email';

    public static function getScopes() : array {
        return [
            self::OFFLINE_ACCESS,
            self::MINECRAFT_SERVER_SESSION,
            self::ACCOUNT_INFO,
            self::ACCOUNT_EMAIL,
        ];
    }

}
