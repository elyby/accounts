<?php
/**
 * @var array $params
 */
return [
    '/accounts/change-email/initialize' => 'accounts/change-email-initialize',
    '/accounts/change-email/submit-new-email' => 'accounts/change-email-submit-new-email',
    '/accounts/change-email/confirm-new-email' => 'accounts/change-email-confirm-new-email',

    '/oauth2/v1/<action>' => 'oauth/<action>',

    '/account/v1/info' => 'identity-info/index',

    '/minecraft/session/join' => 'session/session/join',
    '/minecraft/session/legacy/join' => 'session/session/join-legacy',
    '/minecraft/session/hasJoined' => 'session/session/has-joined',
    '/minecraft/session/legacy/hasJoined' => 'session/session/has-joined-legacy',
    '/minecraft/session/profile/<uuid>' => 'session/session/profile',

    '/mojang/profiles/<username>' => 'mojang/api/uuid-by-username',
    '/mojang/profiles/<uuid>/names' => 'mojang/api/usernames-by-uuid',
    'POST /mojang/profiles' => 'mojang/api/uuids-by-usernames',

    "http://{$params['authserverHost']}/mojang/api/users/profiles/minecraft/<username>" => 'mojang/api/uuid-by-username',
    "http://{$params['authserverHost']}/mojang/api/user/profiles/<uuid>/names" => 'mojang/api/usernames-by-uuid',
    "POST http://{$params['authserverHost']}/mojang/api/profiles/minecraft" => 'mojang/api/uuids-by-usernames',
];
